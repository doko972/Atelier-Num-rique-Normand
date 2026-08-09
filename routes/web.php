<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Site;
use App\Models\Page;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site public
|--------------------------------------------------------------------------
|
| Les adresses sont en français et décrivent leur contenu : une personne doit
| pouvoir les recopier depuis un document papier sans se tromper (codex §28).
|
*/

Route::get('/', Site\HomeController::class)->name('home');

Route::controller(Site\ServiceController::class)->group(function (): void {
    Route::get('/mes-services', 'index')->name('services.index');
    Route::get('/mes-services/{category:slug}', 'show')->name('services.show');
    Route::get('/mes-services/{category:slug}/{service:slug}', 'detail')->name('services.detail');
});

Route::controller(Site\WorkshopController::class)->group(function (): void {
    Route::get('/ateliers', 'index')->name('workshops.index');
    Route::get('/ateliers/{workshop:slug}', 'show')->name('workshops.show');
    Route::get('/ateliers/{workshop:slug}/inscription', 'create')->name('workshops.register');
    Route::post('/ateliers/{workshop:slug}/inscription', 'store')
        ->middleware('throttle:formulaires')
        ->name('workshops.register.store');
});

Route::controller(Site\AppointmentController::class)->group(function (): void {
    Route::get('/prendre-rendez-vous', 'create')->name('appointments.create');
    Route::post('/prendre-rendez-vous', 'store')
        ->middleware('throttle:formulaires')
        ->name('appointments.store');
    Route::get('/prendre-rendez-vous/confirmation', 'confirmation')->name('appointments.confirmation');
});

Route::controller(Site\ContactController::class)->group(function (): void {
    Route::get('/contact', 'create')->name('contact.create');
    Route::post('/contact', 'store')
        ->middleware('throttle:formulaires')
        ->name('contact.store');

    Route::get('/partenariats', 'partnership')->name('partnership.create');
    Route::get('/partenariats/presentation', 'brochure')->name('partnership.brochure');
    Route::get('/plaquette', 'leaflet')->name('leaflet');
    Route::post('/partenariats', 'storePartnership')
        ->middleware('throttle:formulaires')
        ->name('partnership.store');
});

Route::controller(Site\ResourceController::class)->group(function (): void {
    Route::get('/conseils-pratiques', 'index')->name('resources.index');
    Route::get('/conseils-pratiques/articles/{article:slug}', 'article')->name('resources.article');
    Route::get('/conseils-pratiques/fiches/{guide:slug}', 'guide')->name('resources.guide');
    Route::get('/conseils-pratiques/fiches/{guide:slug}/imprimer', 'printGuide')->name('resources.guide.print');
});

Route::controller(Site\PageController::class)->group(function (): void {
    Route::get('/tarifs', 'pricing')->name('pricing');
    Route::get('/a-propos', 'about')->name('about');
    Route::get('/demarches-en-ligne', 'onlineProcedures')->name('procedures');
    Route::get('/securite-et-arnaques', 'security')->name('security');
    Route::get('/questions-frequentes', 'faq')->name('faq');
    Route::get('/declaration-accessibilite', 'accessibility')->name('accessibility');
});

// Pages légales, identifiées par une clé stable côté base de données : leur
// titre peut changer sans casser les liens du pied de page.
Route::get('/mentions-legales', [Site\PageController::class, 'system'])
    ->defaults('key', Page::KEY_LEGAL)
    ->name('legal');
Route::get('/politique-de-confidentialite', [Site\PageController::class, 'system'])
    ->defaults('key', Page::KEY_PRIVACY)
    ->name('privacy');
Route::get('/gestion-des-cookies', [Site\PageController::class, 'system'])
    ->defaults('key', Page::KEY_COOKIES)
    ->name('cookies');

Route::get('/documents/{file}', Site\DownloadController::class)->name('files.download');

Route::get('/sitemap.xml', [Site\SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [Site\SitemapController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Authentification de l'administration
|--------------------------------------------------------------------------
*/

Route::prefix('administration')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/connexion', [Auth\AuthenticatedSessionController::class, 'create'])
            ->name('admin.login');
        Route::post('/connexion', [Auth\AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:connexion')
            ->name('admin.login.store');

        Route::get('/mot-de-passe-oublie', [Auth\PasswordResetController::class, 'requestForm'])
            ->name('password.request');
        Route::post('/mot-de-passe-oublie', [Auth\PasswordResetController::class, 'sendLink'])
            ->middleware('throttle:connexion')
            ->name('password.email');
        Route::get('/reinitialiser/{token}', [Auth\PasswordResetController::class, 'resetForm'])
            ->name('password.reset');
        Route::post('/reinitialiser', [Auth\PasswordResetController::class, 'reset'])
            ->middleware('throttle:connexion')
            ->name('password.update');
    });

    Route::post('/deconnexion', [Auth\AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('admin.logout');

    Route::middleware(['auth', 'active'])->group(function (): void {
        Route::get('/verification-adresse', [Auth\EmailVerificationController::class, 'notice'])
            ->name('verification.notice');
        Route::get('/verification-adresse/{id}/{hash}', [Auth\EmailVerificationController::class, 'verify'])
            ->middleware('signed')
            ->name('verification.verify');
        Route::post('/verification-adresse/renvoyer', [Auth\EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });
});

/*
|--------------------------------------------------------------------------
| Back-office
|--------------------------------------------------------------------------
*/

Route::prefix('administration')
    ->name('admin.')
    ->middleware(['auth', 'active', 'verified'])
    ->group(function (): void {

        Route::get('/', Admin\DashboardController::class)
            ->middleware('permission:'.Permission::ViewDashboard->value)
            ->name('dashboard');

        // -- Profil du compte connecté ------------------------------------
        Route::controller(Admin\ProfileController::class)
            ->prefix('mon-compte')
            ->name('profile.')
            ->group(function (): void {
                Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
                Route::put('/mot-de-passe', 'updatePassword')->name('password');
                Route::post('/revoquer-les-sessions', 'revokeSessions')->name('sessions');
            });

        // -- Demandes de rendez-vous --------------------------------------
        Route::middleware('permission:'.Permission::ManageAppointments->value)
            ->prefix('rendez-vous')
            ->name('appointments.')
            ->controller(Admin\AppointmentController::class)
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('/export', 'export')->name('export');
                Route::get('/{appointment}', 'show')->name('show');
                Route::put('/{appointment}', 'update')->name('update');
                Route::put('/{appointment}/affectation', 'assign')->name('assign');
                Route::post('/{appointment}/note', 'addNote')->name('note');
                Route::post('/{appointment}/anonymiser', 'anonymise')->name('anonymise');
            });

        // -- Ateliers -------------------------------------------------------
        Route::middleware('permission:'.Permission::ManageWorkshops->value)
            ->prefix('ateliers')
            ->name('workshops.')
            ->controller(Admin\WorkshopController::class)
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('/nouveau', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{workshop}/modifier', 'edit')->name('edit');
                Route::put('/{workshop}', 'update')->name('update');
                Route::delete('/{workshop}', 'destroy')->name('destroy');
                Route::post('/{workshop}/annuler', 'cancel')->name('cancel');
                Route::get('/{workshop}/participants', 'participants')->name('participants');
                Route::get('/{workshop}/participants/export', 'exportParticipants')->name('participants.export');
            });

        // -- Inscriptions ---------------------------------------------------
        Route::middleware('permission:'.Permission::ManageRegistrations->value)
            ->prefix('inscriptions')
            ->name('registrations.')
            ->controller(Admin\RegistrationController::class)
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('/export', 'export')->name('export');
                Route::get('/atelier/{workshop}/nouvelle', 'create')->name('create');
                Route::post('/atelier/{workshop}', 'store')->name('store');
                Route::put('/{registration}', 'update')->name('update');
                Route::post('/{registration}/anonymiser', 'anonymise')->name('anonymise');
            });

        // -- Messages reçus ---------------------------------------------------
        Route::middleware('permission:'.Permission::ManageContactRequests->value)
            ->prefix('messages')
            ->name('contact-requests.')
            ->controller(Admin\ContactRequestController::class)
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('/export', 'export')->name('export');
                Route::get('/{contactRequest}', 'show')->name('show');
                Route::put('/{contactRequest}', 'update')->name('update');
                Route::post('/{contactRequest}/anonymiser', 'anonymise')->name('anonymise');
            });

        // -- Demandes de partenariat ------------------------------------------
        Route::middleware('permission:'.Permission::ManagePartnershipRequests->value)
            ->prefix('demandes-partenariat')
            ->name('partnership-requests.')
            ->controller(Admin\PartnershipRequestController::class)
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('/export', 'export')->name('export');
                Route::get('/{partnershipRequest}', 'show')->name('show');
                Route::put('/{partnershipRequest}', 'update')->name('update');
                Route::post('/{partnershipRequest}/anonymiser', 'anonymise')->name('anonymise');
            });

        // -- Contenus éditoriaux -----------------------------------------------
        Route::middleware('permission:'.Permission::ManageContent->value)->group(function (): void {
            // Étapes des fiches pratiques : déclarées avant le CRUD générique
            // pour que « guides/{guide}/etapes » ne soit pas capté par
            // « guides/{record}/modifier ».
            Route::prefix('guides/{guide}/etapes')
                ->name('guides.steps.')
                ->controller(Admin\GuideStepController::class)
                ->group(function (): void {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'store')->name('store');
                    Route::put('/{step}', 'update')->name('update');
                    Route::delete('/{step}', 'destroy')->name('destroy');
                });

            foreach ([
                'service-categories' => Admin\ServiceCategoryController::class,
                'services' => Admin\ServiceController::class,
                'article-categories' => Admin\ArticleCategoryController::class,
                'articles' => Admin\ArticleController::class,
                'guides' => Admin\PracticalGuideController::class,
                'pages' => Admin\PageController::class,
                'faqs' => Admin\FaqController::class,
                'testimonials' => Admin\TestimonialController::class,
                'pricings' => Admin\PricingController::class,
                'official-links' => Admin\OfficialLinkController::class,
                'workshop-categories' => Admin\WorkshopCategoryController::class,
            ] as $segment => $controller) {
                Route::prefix($segment)
                    ->name("{$segment}.")
                    ->controller($controller)
                    ->group(function (): void {
                        Route::get('/', 'index')->name('index');
                        Route::get('/nouveau', 'create')->name('create');
                        Route::post('/', 'store')->name('store');
                        Route::get('/{record}/modifier', 'edit')->name('edit');
                        Route::put('/{record}', 'update')->name('update');
                        Route::delete('/{record}', 'destroy')->name('destroy');
                        Route::post('/{id}/restaurer', 'restore')->name('restore');
                    });
            }
        });

        // -- Communes, lieux et partenaires --------------------------------------
        Route::middleware('permission:'.Permission::ManageDirectory->value)->group(function (): void {
            foreach ([
                'municipalities' => Admin\MunicipalityController::class,
                'locations' => Admin\LocationController::class,
                'partners' => Admin\PartnerController::class,
            ] as $segment => $controller) {
                Route::prefix($segment)
                    ->name("{$segment}.")
                    ->controller($controller)
                    ->group(function (): void {
                        Route::get('/', 'index')->name('index');
                        Route::get('/nouveau', 'create')->name('create');
                        Route::post('/', 'store')->name('store');
                        Route::get('/{record}/modifier', 'edit')->name('edit');
                        Route::put('/{record}', 'update')->name('update');
                        Route::delete('/{record}', 'destroy')->name('destroy');
                        Route::post('/{id}/restaurer', 'restore')->name('restore');
                    });
            }
        });

        // -- Comptes d'administration ----------------------------------------------
        Route::middleware('permission:'.Permission::ManageUsers->value)
            ->prefix('comptes')
            ->name('users.')
            ->controller(Admin\UserController::class)
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('/nouveau', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{user}/modifier', 'edit')->name('edit');
                Route::put('/{user}', 'update')->name('update');
                Route::post('/{user}/activation', 'toggle')->name('toggle');
                Route::delete('/{user}', 'destroy')->name('destroy');
            });

        // -- Paramètres --------------------------------------------------------------
        Route::middleware('permission:'.Permission::ManageSettings->value)
            ->prefix('parametres')
            ->name('settings.')
            ->controller(Admin\SettingController::class)
            ->group(function (): void {
                Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
                Route::put('/horaires', 'updateHours')->name('hours');
            });

        // -- Journal des actions -------------------------------------------------------
        Route::get('/journal', [Admin\AuditLogController::class, 'index'])
            ->middleware('permission:'.Permission::ViewAuditLog->value)
            ->name('audit-logs.index');

        // -- Demandes RGPD ---------------------------------------------------------------
        Route::middleware('permission:'.Permission::ManageGdprRequests->value)
            ->prefix('rgpd')
            ->name('gdpr.')
            ->controller(Admin\GdprController::class)
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::post('/acces', 'storeExport')->name('exports.store');
                Route::post('/effacement', 'storeDeletion')->name('deletions.store');
                Route::get('/acces/{exportRequest}/apercu', 'previewExport')->name('exports.preview');
                Route::post('/effacement/{deletionRequest}/executer', 'execute')->name('deletions.execute');
                Route::post('/{type}/{id}/identite', 'verifyIdentity')->name('identity');
                Route::put('/{type}/{id}/statut', 'updateStatus')->name('status');
            });
    });

/*
|--------------------------------------------------------------------------
| Page éditoriale libre
|--------------------------------------------------------------------------
|
| Déclarée en dernier : elle ne capte que les adresses qu'aucune route
| précédente n'a réclamées.
|
*/

Route::get('/{page:slug}', [Site\PageController::class, 'show'])->name('pages.show');
