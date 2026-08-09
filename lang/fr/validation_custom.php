<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Messages de validation propres au site
|--------------------------------------------------------------------------
|
| Les messages disent quoi faire, sans reprocher quoi que ce soit à la
| personne (codex §25).
|
*/

return [

    'spam_detected' => 'Votre message n’a pas pu être envoyé. Vous pouvez réessayer, ou m’appeler directement : je remplirai le formulaire avec vous.',

    'email_required_for_preference' => 'Vous avez choisi d’être recontacté par courrier électronique : indiquez votre adresse, ou choisissez le téléphone.',
    'phone_required_for_preference' => 'Vous avez choisi d’être recontacté par téléphone : indiquez votre numéro, ou choisissez le courrier électronique.',
    'contact_channel_required' => 'Indiquez au moins un numéro de téléphone ou une adresse électronique, pour que je puisse vous répondre.',

    /*
    |--------------------------------------------------------------------------
    | Noms des champs
    |--------------------------------------------------------------------------
    |
    | Repris tels quels dans les messages d'erreur : ils doivent se lire
    | naturellement dans une phrase.
    |
    */

    'attributes' => [
        'first_name' => 'le prénom',
        'last_name' => 'le nom',
        'phone' => 'le numéro de téléphone',
        'email' => 'l’adresse électronique',
        'municipality_id' => 'la commune',
        'municipality_name' => 'la commune',
        'type' => 'le type de rendez-vous',
        'need_description' => 'la description de votre besoin',
        'device' => 'l’appareil concerné',
        'availability' => 'vos disponibilités',
        'contact_preference' => 'la façon de vous recontacter',
        'consent' => 'l’accord sur l’utilisation de vos données',
        'consent_confirmed' => 'la confirmation de l’accord recueilli',
        'subject' => 'le sujet',
        'message' => 'le message',
        'age_range' => 'la tranche d’âge',
        'special_needs' => 'le besoin particulier',
        'organisation_name' => 'le nom de la structure',
        'organisation_type' => 'le type de structure',
        'contact_name' => 'le nom du contact',
        'contact_role' => 'la fonction',
        'needs' => 'le type de besoin',
        'audience' => 'le public concerné',
        'estimated_participants' => 'le nombre de participants',
        'desired_period' => 'la période souhaitée',
        'quote_requested' => 'la demande de devis',
    ],

];
