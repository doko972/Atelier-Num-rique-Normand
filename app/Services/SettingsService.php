<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OpeningHour;
use App\Models\SiteSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Accès aux paramètres administrables du site.
 *
 * L'ensemble est mis en cache : afficher le numéro de téléphone dans l'en-tête
 * de chaque page ne doit pas coûter une requête supplémentaire.
 */
class SettingsService
{
    /** @var Collection<string, mixed>|null */
    protected ?Collection $cache = null;

    /**
     * Valeurs de repli utilisées tant que le paramètre n'a pas été renseigné.
     *
     * @var array<string, mixed>
     */
    protected const array DEFAULTS = [
        'site_name' => 'L’Atelier Numérique Normand',
        'site_tagline' => 'Conseiller numérique à Condé-en-Normandie',
        'adviser_name' => '',
        'phone' => '',
        'phone_display' => '',
        'email' => '',
        'address' => '',
        'postal_code' => '',
        'city' => '',
        'closed_message' => 'Vous pouvez laisser un message avec votre nom et votre numéro. Je vous rappellerai dès que possible.',
        'home_hero_title' => 'Besoin d’aide avec un ordinateur, un smartphone ou une démarche en ligne ?',
        'home_hero_subtitle' => 'Je vous accompagne pas à pas, avec patience et sans jargon, à domicile ou près de chez vous.',
        'home_final_cta' => 'Une question ? Appelez-moi simplement ou demandez un rendez-vous.',
        'meta_description' => 'Aide informatique et démarches en ligne à Condé-en-Normandie et alentour. Je vous accompagne pas à pas, avec patience et sans jargon, à domicile ou près de chez vous.',
        'home_visit_area' => '',
        'siret' => '',
        'legal_status' => '',
        'publication_director' => '',
        'hosting_provider' => '',
        'social_facebook' => '',
    ];

    /**
     * Valeur d'un paramètre, avec repli.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()->get($key);

        if (blank($value)) {
            return $default ?? self::DEFAULTS[$key] ?? null;
        }

        return $value;
    }

    public function string(string $key, string $default = ''): string
    {
        return (string) ($this->get($key) ?? $default);
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * Tous les paramètres, typés et mis en cache.
     *
     * @return Collection<string, mixed>
     */
    public function all(): Collection
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $values = Cache::rememberForever(
            SiteSetting::CACHE_KEY,
            fn (): array => SiteSetting::query()
                ->get()
                ->mapWithKeys(fn (SiteSetting $setting): array => [
                    $setting->key => $setting->typedValue(),
                ])
                ->all(),
        );

        return $this->cache = collect($values);
    }

    /**
     * Numéro de téléphone au format `tel:` (sans espace ni séparateur).
     */
    public function phoneLink(): string
    {
        return 'tel:'.$this->normalizedPhone();
    }

    /**
     * Même numéro, en lien d'envoi de SMS.
     *
     * Le SMS n'est pas un canal secondaire par confort : pour une personne
     * malentendante — la presbyacousie touche une large part des plus de
     * 65 ans —, c'est le téléphone qui est le canal difficile, pas l'écrit.
     */
    public function smsLink(): string
    {
        return 'sms:'.$this->normalizedPhone();
    }

    /**
     * Numéro réduit aux chiffres, seule forme acceptée par `tel:` et `sms:`.
     */
    private function normalizedPhone(): string
    {
        return preg_replace('/[^\d+]/', '', $this->string('phone')) ?? '';
    }

    /**
     * Numéro tel qu'il doit être lu : « 06 12 34 56 78 ».
     */
    public function phoneDisplay(): string
    {
        return $this->string('phone_display') ?: $this->string('phone');
    }

    public function hasPhone(): bool
    {
        return filled($this->string('phone'));
    }

    /**
     * Horaires d'appel, mis en cache.
     *
     * Seules les valeurs brutes sont mises en cache, jamais les modèles
     * sérialisés : un objet stocké en cache dépend de sa classe au moment de
     * la relecture, ce qui casse dès qu'un déploiement modifie le modèle.
     *
     * @return Collection<int, OpeningHour>
     */
    public function openingHours(): Collection
    {
        $rows = Cache::rememberForever(
            OpeningHour::CACHE_KEY,
            fn (): array => OpeningHour::query()->ordered()->get()->map(
                fn (OpeningHour $hour): array => $hour->getAttributes(),
            )->all(),
        );

        return OpeningHour::hydrate($rows);
    }

    /**
     * Le service est-il joignable à l'instant donné ?
     */
    public function isOpenAt(?CarbonImmutable $moment = null): bool
    {
        $moment ??= CarbonImmutable::now();

        return $this->openingHours()->contains(
            fn (OpeningHour $hour): bool => $hour->covers($moment),
        );
    }

    /**
     * Vide le cache après une modification depuis le back-office.
     */
    public function flush(): void
    {
        $this->cache = null;
        Cache::forget(SiteSetting::CACHE_KEY);
        Cache::forget(OpeningHour::CACHE_KEY);
    }
}
