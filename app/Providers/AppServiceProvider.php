<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ContactRequest;
use App\Models\Faq;
use App\Models\Location;
use App\Models\Municipality;
use App\Models\OfficialLink;
use App\Models\Page;
use App\Models\Partner;
use App\Models\PartnershipRequest;
use App\Models\PracticalGuide;
use App\Models\Pricing;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopCategory;
use App\Models\WorkshopRegistration;
use App\Policies\AppointmentPolicy;
use App\Policies\ContentPolicy;
use App\Policies\RequestPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkshopPolicy;
use App\Policies\WorkshopRegistrationPolicy;
use App\Services\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Modèles partageant la politique de contenu éditorial.
     *
     * @var array<int, class-string<Model>>
     */
    protected const array CONTENT_MODELS = [
        Service::class,
        ServiceCategory::class,
        WorkshopCategory::class,
        Article::class,
        ArticleCategory::class,
        PracticalGuide::class,
        Page::class,
        Faq::class,
        Testimonial::class,
        Pricing::class,
        OfficialLink::class,
        Municipality::class,
        Location::class,
        Partner::class,
    ];

    public function register(): void
    {
        // Les paramètres du site sont lus sur chaque page : une seule
        // instance par requête suffit.
        $this->app->singleton(SettingsService::class);
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureDates();
        $this->configureUrls();
        $this->configureAuthorization();
        $this->configureRateLimiting();

        Paginator::defaultView('components.pagination');
        Paginator::defaultSimpleView('components.pagination');
    }

    /**
     * Réglages stricts : une relation oubliée ou un attribut inexistant doit
     * échouer en développement plutôt que produire une page silencieusement
     * incomplète.
     */
    protected function configureModels(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
    }

    protected function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }

    protected function configureUrls(): void
    {
        if (config('site.security.force_https')) {
            URL::forceScheme('https');
        }
    }

    protected function configureAuthorization(): void
    {
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(Workshop::class, WorkshopPolicy::class);
        Gate::policy(WorkshopRegistration::class, WorkshopRegistrationPolicy::class);
        Gate::policy(ContactRequest::class, RequestPolicy::class);
        Gate::policy(PartnershipRequest::class, RequestPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        foreach (self::CONTENT_MODELS as $model) {
            Gate::policy($model, ContentPolicy::class);
        }

        // Chaque permission devient une capacité vérifiable dans les vues :
        // @can('manage_settings') ... @endcan
        //
        // Aucun Gate::before n'est défini : le super administrateur détient
        // déjà toutes les permissions, et un contournement global annulerait
        // les garde-fous des Policies — par exemple l'interdiction faite à un
        // compte de se supprimer lui-même.
        foreach (Permission::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (User $user): bool => $user->hasPermission($permission),
            );
        }
    }

    /**
     * Limitation de fréquence (codex §25 et §26).
     *
     * Les seuils restent volontairement généreux du point de vue humain : une
     * personne âgée qui reprend son formulaire après une erreur ne doit jamais
     * se retrouver bloquée.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('formulaires', fn (Request $request): Limit => Limit::perHour(
            (int) config('site.security.form_rate_limit'),
        )->by($request->ip())->response(
            fn (): Response => response()->view('errors.429', [], 429),
        ));

        RateLimiter::for('connexion', fn (Request $request): Limit => Limit::perMinutes(
            (int) config('site.security.login.decay_minutes'),
            (int) config('site.security.login.max_attempts'),
        )->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));
    }
}
