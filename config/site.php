<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuration métier du site
|--------------------------------------------------------------------------
|
| Ces valeurs sont des réglages techniques ou des valeurs de repli. Tout ce
| que l'administrateur doit pouvoir changer sans développeur (téléphone,
| adresse, horaires, textes d'accueil) vit dans la table `site_settings`.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Sécurité
    |--------------------------------------------------------------------------
    */
    'security' => [
        'csp_enabled' => (bool) env('SECURITY_CSP_ENABLED', true),
        'force_https' => (bool) env('SECURITY_FORCE_HTTPS', false),

        // Nombre d'envois autorisés par heure et par adresse IP sur les
        // formulaires publics. Volontairement bas : une personne remplit
        // rarement plus de quelques formulaires dans l'heure.
        'form_rate_limit' => (int) env('SECURITY_FORM_RATE_LIMIT', 5),

        'login' => [
            'max_attempts' => (int) env('SECURITY_LOGIN_MAX_ATTEMPTS', 5),
            'decay_minutes' => (int) env('SECURITY_LOGIN_DECAY_MINUTES', 15),
        ],

        // Délai minimal, en secondes, entre l'affichage et l'envoi d'un
        // formulaire. Un envoi instantané trahit un automate.
        'min_form_seconds' => 3,

        'uploads' => [
            'max_size_kb' => 8192,
            'allowed_mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'odt', 'docx'],
            'image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RGPD — durées de conservation, en jours
    |--------------------------------------------------------------------------
    |
    | Les demandes closes sont anonymisées, pas supprimées : les bilans
    | agrégés attendus par les partenaires restent exacts (codex §36).
    |
    */
    'retention' => [
        'appointments' => (int) env('RGPD_RETENTION_APPOINTMENTS', 1095),
        'registrations' => (int) env('RGPD_RETENTION_REGISTRATIONS', 1095),
        'contacts' => (int) env('RGPD_RETENTION_CONTACTS', 365),
        'partnerships' => (int) env('RGPD_RETENTION_PARTNERSHIPS', 1095),
        'consent_logs' => (int) env('RGPD_RETENTION_CONSENT_LOGS', 2190),
        'audit_logs' => (int) env('RGPD_RETENTION_AUDIT_LOGS', 1095),
    ],

    'contact' => [
        'admin_email' => env('ADMIN_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS')),
        'rgpd_email' => env('RGPD_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ateliers
    |--------------------------------------------------------------------------
    */
    'workshops' => [
        // Seuil à partir duquel « Il reste N places » passe en alerte.
        'low_seats_threshold' => 3,

        // Nombre de jours avant l'atelier pour l'envoi du rappel.
        'reminder_days_before' => 2,

        // Nombre d'ateliers affichés sur la page d'accueil.
        'home_limit' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mesure d'audience respectueuse de la vie privée
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'enabled' => (bool) env('ANALYTICS_ENABLED', false),
        'matomo_url' => env('ANALYTICS_MATOMO_URL'),
        'matomo_site_id' => env('ANALYTICS_MATOMO_SITE_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'per_page' => [
        'public' => 12,
        'admin' => 25,
    ],
];
