<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Authentification de l'administration
|--------------------------------------------------------------------------
*/

return [

    'failed' => 'L’adresse électronique ou le mot de passe ne correspond pas.',
    'password' => 'Le mot de passe est incorrect.',
    'throttle' => 'Trop de tentatives de connexion. Réessayez dans :minutes minute(s).',
    'account_disabled' => 'Ce compte est désactivé. Contactez l’administrateur du site.',

    'login' => [
        'title' => 'Connexion à l’administration',
        'intro' => 'Cet espace est réservé au conseiller et aux personnes qui l’assistent.',
        'submit' => 'Se connecter',
        'remember' => 'Rester connecté sur cet appareil',
        'forgot' => 'Mot de passe oublié ?',
        'back_to_site' => 'Revenir au site',
    ],

    'forgot' => [
        'title' => 'Mot de passe oublié',
        'intro' => 'Indiquez votre adresse électronique : vous recevrez un lien pour choisir un nouveau mot de passe.',
        'submit' => 'Envoyer le lien',
        'back' => 'Revenir à la connexion',
    ],

    'reset' => [
        'title' => 'Choisir un nouveau mot de passe',
        'intro' => 'Choisissez un mot de passe d’au moins douze caractères, différent de ceux que vous utilisez ailleurs.',
        'submit' => 'Enregistrer le nouveau mot de passe',
        'confirmation' => 'Confirmez le nouveau mot de passe',
    ],

    'verify' => [
        'title' => 'Vérifiez votre adresse électronique',
        'intro' => 'Un message vient de vous être envoyé. Cliquez sur le lien qu’il contient pour activer votre accès.',
        'resend' => 'Renvoyer le message',
        'logout' => 'Se déconnecter',
    ],

    'email_verified' => 'Votre adresse électronique est vérifiée. Bienvenue.',
    'verification_sent' => 'Un nouveau message de vérification vient de vous être envoyé.',

    'fields' => [
        'email' => 'l’adresse électronique',
        'password' => 'le mot de passe',
    ],

];
