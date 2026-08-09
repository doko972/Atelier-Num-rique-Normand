<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Libellés des énumérations
|--------------------------------------------------------------------------
|
| Chaque clé de premier niveau correspond au nom court d'un enum converti en
| snake_case (App\Enums\WorkshopStatus => workshop_status).
|
| Le vocabulaire est volontairement simple et non technique : ces libellés
| s'affichent aussi bien dans le back-office que sur le site public.
|
*/

return [

    'user_role' => [
        'super_admin' => 'Super administrateur',
        'admin' => 'Administrateur',
        'adviser' => 'Conseiller',
        'editor' => 'Éditeur',
        'viewer' => 'Lecteur',
    ],

    'permission' => [
        'view_dashboard' => 'Consulter le tableau de bord',
        'manage_appointments' => 'Gérer les demandes de rendez-vous',
        'manage_workshops' => 'Gérer les ateliers',
        'manage_registrations' => 'Gérer les inscriptions',
        'manage_content' => 'Gérer les contenus',
        'manage_directory' => 'Gérer les communes, lieux et partenaires',
        'manage_contact_requests' => 'Gérer les messages reçus',
        'manage_partnership_requests' => 'Gérer les demandes de partenariat',
        'manage_users' => 'Gérer les comptes administrateurs',
        'manage_settings' => 'Gérer les paramètres du site',
        'manage_gdpr_requests' => 'Traiter les demandes RGPD',
        'view_audit_log' => 'Consulter le journal des actions',
        'export_data' => 'Exporter les données',
    ],

    'content_status' => [
        'draft' => 'Brouillon',
        'published' => 'Publié',
        'archived' => 'Archivé',
    ],

    'workshop_status' => [
        'draft' => 'Brouillon',
        'published' => 'Publié',
        'full' => 'Complet',
        'cancelled' => 'Annulé',
        'finished' => 'Terminé',
        'archived' => 'Archivé',
    ],

    'registration_status' => [
        'pending' => 'À confirmer',
        'confirmed' => 'Inscription confirmée',
        'waiting_list' => 'Liste d’attente',
        'cancelled' => 'Annulée',
        'attended' => 'Présent',
        'absent' => 'Absent',
    ],

    'appointment_status' => [
        'new' => 'Nouvelle demande',
        'to_call' => 'À rappeler',
        'waiting_information' => 'En attente d’informations',
        'proposed' => 'Rendez-vous proposé',
        'confirmed' => 'Rendez-vous confirmé',
        'done' => 'Réalisé',
        'cancelled' => 'Annulé',
        'no_follow_up' => 'Sans suite',
        'archived' => 'Archivé',
    ],

    'appointment_type' => [
        'individual' => 'Accompagnement individuel',
        'home' => 'Intervention à mon domicile',
        'partner_location' => 'Rendez-vous dans un lieu partenaire',
        'phone' => 'Entretien par téléphone',
        'information' => 'Simple demande d’information',
        'caregiver' => 'Demande pour un proche que j’aide',
        'organisation' => 'Demande d’une commune ou d’une association',
    ],

    'appointment_type_description' => [
        'individual' => 'Nous prenons le temps qu’il faut, sur ce qui vous pose problème.',
        'home' => 'Je me déplace chez vous, avec votre propre matériel.',
        'partner_location' => 'Dans une mairie, une médiathèque ou un centre social proche de chez vous.',
        'phone' => 'Pour une question courte, sans avoir à vous déplacer.',
        'information' => 'Vous souhaitez d’abord savoir si je peux vous aider.',
        'caregiver' => 'Vous accompagnez un parent, un voisin ou un ami.',
        'organisation' => 'Pour organiser une permanence ou un atelier collectif.',
    ],

    'request_status' => [
        'new' => 'Nouveau',
        'in_progress' => 'En cours de traitement',
        'answered' => 'Réponse envoyée',
        'quote_sent' => 'Devis envoyé',
        'accepted' => 'Accepté',
        'declined' => 'Décliné',
        'closed' => 'Clos',
        'archived' => 'Archivé',
    ],

    'skill_level' => [
        'beginner' => 'Je débute',
        'intermediate' => 'J’ai déjà quelques bases',
        'advanced' => 'Je suis à l’aise',
        'everyone' => 'Ouvert à tous',
    ],

    'device_type' => [
        'computer' => 'Ordinateur fixe',
        'laptop' => 'Ordinateur portable',
        'smartphone' => 'Téléphone (smartphone)',
        'tablet' => 'Tablette',
        'printer' => 'Imprimante ou scanner',
        'none' => 'Je n’ai pas d’appareil',
        'other' => 'Autre appareil',
    ],

    'contact_preference' => [
        'phone' => 'Par téléphone',
        'sms' => 'Par SMS',
        'email' => 'Par courrier électronique',
    ],

    'age_range' => [
        'under_60' => 'Moins de 60 ans',
        '60_69' => 'De 60 à 69 ans',
        '70_79' => 'De 70 à 79 ans',
        'over_80' => '80 ans et plus',
        'undisclosed' => 'Je préfère ne pas répondre',
    ],

    'data_request_type' => [
        'access' => 'Accès à mes données',
        'rectification' => 'Correction de mes données',
        'erasure' => 'Effacement de mes données',
        'objection' => 'Opposition au traitement',
        'portability' => 'Portabilité de mes données',
        'consent_withdrawal' => 'Retrait de mon consentement',
    ],

    'data_request_status' => [
        'received' => 'Demande reçue',
        'identity_check' => 'Vérification d’identité',
        'in_progress' => 'En cours de traitement',
        'completed' => 'Traitée',
        'refused' => 'Refusée',
    ],

    'pricing_model' => [
        'hourly' => 'Accompagnement individuel à l’heure',
        'discovery' => 'Forfait découverte',
        'package' => 'Forfait plusieurs séances',
        'home_visit' => 'Intervention à domicile',
        'workshop' => 'Atelier collectif',
        'funded_workshop' => 'Atelier financé par un partenaire',
        'association' => 'Tarif association',
        'local_authority' => 'Tarif collectivité',
        'quote' => 'Devis personnalisé',
        'mileage' => 'Frais kilométriques',
    ],

    'partner_type' => [
        'city_hall' => 'Mairie',
        'social_centre' => 'Centre social',
        'ccas' => 'CCAS (centre communal d’action sociale)',
        'library' => 'Médiathèque ou bibliothèque',
        'association' => 'Association',
        'senior_residence' => 'Résidence seniors',
        'ehpad' => 'EHPAD',
        'neighbourhood_house' => 'Maison de quartier',
        'france_services' => 'Maison France Services',
        'company' => 'Entreprise locale',
        'training_organisation' => 'Organisme de formation',
        'other' => 'Autre structure',
    ],

    'consent_purpose' => [
        'appointment_request' => 'Demande de rendez-vous',
        'workshop_registration' => 'Inscription à un atelier',
        'contact_request' => 'Message envoyé depuis le site',
        'partnership_request' => 'Demande de partenariat',
        'voice_message' => 'Autorisation de laisser un message vocal',
    ],

];
