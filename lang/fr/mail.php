<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Courriels
|--------------------------------------------------------------------------
|
| Messages courts, sans jargon et sans aucune information sensible : pas de
| mot de passe, pas de code, pas de pièce jointe administrative (codex §34).
|
*/

return [

    'common' => [
        'greeting' => 'Bonjour :name,',
        'salutation' => "À bientôt,\n:name",
        'phone_reminder' => 'Si vous préférez, appelez-moi simplement au :phone.',
        'no_password_reminder' => 'Rappel utile : je ne vous demanderai jamais votre mot de passe, ni par courriel, ni par téléphone.',
        'not_specified' => 'non précisée',
    ],

    'appointment_received' => [
        'subject' => 'Votre demande de rendez-vous est bien arrivée',
        'intro' => 'J’ai bien reçu votre demande de rendez-vous. Merci de m’avoir écrit.',
        'reference' => 'Votre référence est :reference. Gardez-la : elle nous fera gagner du temps au téléphone.',
        'next_step' => 'Je vous rappelle sous deux jours ouvrés pour convenir ensemble d’une date.',
    ],

    'appointment_status' => [
        'subject' => 'Votre demande :reference',
        'intro' => 'Votre demande :reference vient d’évoluer. Son état est maintenant : :status.',
        'scheduled' => 'Le rendez-vous est prévu le :date.',
        'location' => 'Il aura lieu à :location, :address.',
        'change_request' => 'Si cette date ne vous convient pas, appelez-moi : nous en trouverons une autre, sans problème.',
    ],

    'admin_new_appointment' => [
        'subject' => 'Nouvelle demande de rendez-vous :reference',
        'greeting' => 'Bonjour,',
        'intro' => 'Une nouvelle demande vient d’arriver : :type, commune de :municipality.',
        'action' => 'Voir la demande',
        'privacy_note' => 'Les coordonnées et le besoin exprimé ne figurent pas dans ce message : ils restent dans l’administration du site.',
    ],

    'registration_confirmed' => [
        'subject' => 'Votre inscription à l’atelier « :title »',
        'intro' => 'Votre inscription à l’atelier « :title » est enregistrée.',
        'when' => 'Rendez-vous le :date, de :start à :end.',
        'where' => 'À :location, :address.',
        'bring_device' => 'Vous pouvez apporter votre propre ordinateur ou votre téléphone si vous le souhaitez.',
        'reference' => 'Votre référence est :reference.',
        'cancel_note' => 'Si vous ne pouvez pas venir, prévenez-moi : votre place profitera à quelqu’un d’autre.',
    ],

    'waiting_list' => [
        'subject' => 'Liste d’attente pour l’atelier « :title »',
        'intro' => 'L’atelier « :title » est complet pour le moment.',
        'position' => 'Vous êtes en position :position sur la liste d’attente.',
        'next_step' => 'Je vous préviens dès qu’une place se libère. Si une autre date vous conviendrait, dites-le-moi.',
        'reference' => 'Votre référence est :reference.',
    ],

    'seat_available' => [
        'subject' => 'Une place s’est libérée pour « :title »',
        'intro' => 'Bonne nouvelle : une place s’est libérée pour l’atelier « :title ».',
        'when' => 'Il a lieu le :date à :start.',
        'confirm' => 'Votre place est réservée. Appelez-moi seulement si vous ne pouvez plus venir.',
        'reference' => 'Votre référence est :reference.',
    ],

    'workshop_reminder' => [
        'subject' => 'Rappel : atelier « :title »',
        'intro' => 'Petit rappel : l’atelier « :title » a lieu :date à :start.',
        'where' => 'À :location, :address.',
        'equipment_provided' => 'Le matériel est fourni : venez les mains dans les poches.',
        'bring_equipment' => 'Pensez à apporter votre appareil, et son chargeur si possible.',
        'cancel_note' => 'Un imprévu ? Prévenez-moi, votre place profitera à quelqu’un d’autre.',
    ],

    'workshop_cancelled' => [
        'subject' => 'Annulation de l’atelier « :title »',
        'intro' => 'Je dois annuler l’atelier « :title » prévu :date.',
        'reason' => 'Motif : :reason',
        'apology' => 'Je suis désolé pour ce contretemps.',
        'next_step' => 'Une nouvelle date sera proposée prochainement. Appelez-moi si vous souhaitez être prévenu en priorité.',
    ],

    'contact_received' => [
        'subject' => 'Votre message est bien arrivé',
        'intro' => 'J’ai bien reçu votre message. Merci de m’avoir écrit.',
        'reference' => 'Votre référence est :reference.',
        'delay' => 'Je vous réponds sous deux jours ouvrés.',
    ],

    'admin_new_contact' => [
        'subject' => 'Nouveau message :reference',
        'intro' => 'Un nouveau message vient d’arriver, au sujet de : :subject.',
        'action' => 'Voir le message',
    ],

    'partnership_received' => [
        'subject' => 'Votre demande de partenariat est bien arrivée',
        'intro' => 'J’ai bien reçu la demande de :organisation. Merci de votre intérêt.',
        'reference' => 'Votre référence est :reference.',
        'quote' => 'Un devis vous sera adressé avec ma réponse.',
        'delay' => 'Je reviens vers vous sous cinq jours ouvrés.',
    ],

    'admin_new_partnership' => [
        'subject' => 'Nouvelle demande de partenariat :reference',
        'intro' => 'Nouvelle demande de :organisation (:type).',
        'action' => 'Voir la demande',
    ],

];
