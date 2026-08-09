<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| RGPD — anonymisation et traitement des demandes
|--------------------------------------------------------------------------
*/

return [

    // Valeurs de remplacement écrites dans la base lors d'une anonymisation.
    'anonymised_first_name' => 'Personne anonymisée',
    'anonymised_content' => 'Contenu supprimé à la demande de la personne concernée.',
    'anonymised_email' => 'anonymise@invalide.local',

    'title' => 'Demandes relatives aux données personnelles',
    'intro' => 'Toute personne peut demander à consulter, corriger ou effacer les informations la concernant. Le délai légal de réponse est d’un mois.',

    'identity_check_required' => 'Vérifiez d’abord l’identité de la personne : aucune donnée ne doit être communiquée ni effacée sans cette vérification.',
    'identity_required_help' => 'Un appel au numéro connu, ou une pièce d’identité présentée sur place, suffisent. Ne conservez aucune copie.',
    'identity_verified' => 'Identité vérifiée. Vous pouvez maintenant traiter la demande.',
    'identifier_required' => 'Indiquez au moins une adresse électronique ou un numéro de téléphone, sans quoi aucun enregistrement ne peut être retrouvé.',

    'request_recorded' => 'Demande enregistrée sous la référence :reference.',
    'status_updated' => 'Le statut de la demande a été mis à jour.',
    'deletion_done' => ':count enregistrement(s) rendu(s) anonyme(s).',

    'overdue' => 'Délai légal dépassé',
    'due_in' => 'À traiter avant le :date',

    'exports_title' => 'Demandes d’accès, de correction ou de portabilité',
    'deletions_title' => 'Demandes d’effacement et d’opposition',
    'new_export' => 'Enregistrer une demande d’accès',
    'new_deletion' => 'Enregistrer une demande d’effacement',
    'preview' => 'Voir les données détenues',
    'execute' => 'Rendre les données anonymes',
    'execute_confirm' => 'Cette action est définitive : les informations de cette personne seront remplacées par des valeurs neutres. Confirmez-vous ?',

    'anonymisation_notice' => 'L’effacement se traduit par une anonymisation : les statistiques transmises aux communes restent exactes, mais plus aucune donnée ne permet d’identifier la personne.',

    'scope' => [
        'all' => 'Toutes les données',
        'appointments' => 'Uniquement les demandes de rendez-vous',
        'registrations' => 'Uniquement les inscriptions aux ateliers',
        'contacts' => 'Uniquement les messages et demandes de partenariat',
    ],

    'attributes' => [
        'type' => 'le type de demande',
        'requester_name' => 'le nom du demandeur',
        'requester_email' => 'l’adresse électronique du demandeur',
        'requester_phone' => 'le téléphone du demandeur',
        'details' => 'les précisions',
        'scope' => 'le périmètre',
        'status' => 'le statut',
        'internal_notes' => 'les notes internes',
        'refusal_reason' => 'le motif de refus',
    ],

];
