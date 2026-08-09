<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mentions de consentement
|--------------------------------------------------------------------------
|
| Ces textes sont enregistrés mot pour mot dans le registre des consentements
| au moment où la personne coche la case. Toute modification doit
| s'accompagner d'une incrémentation de ConsentService::CURRENT_VERSION.
|
*/

return [

    'statements' => [
        'appointment_request' => 'J’accepte que mon nom, mon numéro de téléphone et la description de mon besoin soient conservés afin d’être recontacté et d’organiser mon rendez-vous. Ces informations ne sont transmises à personne d’autre.',

        'workshop_registration' => 'J’accepte que mon nom et mon numéro de téléphone soient conservés afin de gérer mon inscription à cet atelier et de me prévenir en cas de changement. Ces informations ne sont transmises à personne d’autre.',

        'contact_request' => 'J’accepte que mes coordonnées et mon message soient conservés afin de recevoir une réponse. Ces informations ne sont transmises à personne d’autre.',

        'partnership_request' => 'J’accepte que les coordonnées professionnelles indiquées soient conservées afin d’étudier ma demande de partenariat.',

        'voice_message' => 'J’autorise le dépôt d’un message vocal sur mon répondeur si je ne réponds pas.',
    ],

    'labels' => [
        'appointment_request' => 'J’accepte que mes informations soient conservées pour être recontacté',
        'workshop_registration' => 'J’accepte que mes informations soient conservées pour gérer mon inscription',
        'contact_request' => 'J’accepte que mes informations soient conservées pour recevoir une réponse',
        'partnership_request' => 'J’accepte que les coordonnées indiquées soient conservées pour étudier ma demande',
        'voice_message' => 'Vous pouvez laisser un message sur mon répondeur',
    ],

    'privacy_link' => 'Comment vos données sont utilisées',

    'retention_notice' => 'Vos informations sont conservées le temps nécessaire au suivi de votre demande, puis rendues anonymes. Vous pouvez demander leur effacement à tout moment.',

];
