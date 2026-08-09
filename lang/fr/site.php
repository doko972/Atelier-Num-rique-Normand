<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Textes du site public
|--------------------------------------------------------------------------
|
| Ton : français simple, phrases courtes, vouvoiement, aucun sigle non
| expliqué. On n'infantilise pas, on ne culpabilise pas (codex §32).
|
*/

return [

    'skip' => [
        'content' => 'Aller au contenu principal',
        'nav' => 'Aller au menu',
        'footer' => 'Aller au pied de page',
    ],

    'nav' => [
        'label' => 'Menu principal',
        'toggle' => 'Menu',
        'close' => 'Fermer',
        'home' => 'Accueil',
        'services' => 'Mes services',
        'workshops' => 'Ateliers',
        'procedures' => 'Démarches en ligne',
        'security' => 'Sécurité et arnaques',
        'resources' => 'Conseils pratiques',
        'pricing' => 'Tarifs',
        'partnership' => 'Communes et associations',
        'about' => 'À propos',
        'contact' => 'Contact',
        'appointment' => 'Prendre rendez-vous',
        'faq' => 'Questions fréquentes',
    ],

    'a11y' => [
        'label' => 'Réglages d’affichage',
        'contrast' => 'Contraste renforcé',
        'text_size' => 'Taille du texte',
        'text_up' => 'Agrandir',
        'text_down' => 'Réduire',
        'motion' => 'Réduire les animations',
        'help' => 'Ces réglages sont conservés sur cet appareil uniquement.',
    ],

    'call' => [
        'now' => 'Appeler maintenant',
        'label' => 'Appeler le',
        'open' => 'Je réponds au téléphone en ce moment.',
        'closed' => 'Le téléphone n’est pas décroché en ce moment.',
        'hours_title' => 'Horaires d’appel',
    ],

    'breadcrumb' => [
        'label' => 'Vous êtes ici',
        'home' => 'Accueil',
    ],

    'home' => [
        'pledges_title' => 'Ce que je vous promets',
        'pledges' => [
            'pace' => [
                'title' => 'À votre rythme',
                'text' => 'On recommence autant de fois que nécessaire. Il n’y a pas de mauvaise question.',
            ],
            'no_judgement' => [
                'title' => 'Sans jugement',
                'text' => 'Personne ne naît en sachant se servir d’un ordinateur.',
            ],
            'nearby' => [
                'title' => 'Près de chez vous',
                'text' => 'À votre domicile, en mairie, en médiathèque ou dans un centre social.',
            ],
            'privacy' => [
                'title' => 'En toute confidentialité',
                'text' => 'Ce que vous me confiez reste entre nous. Je ne conserve aucun mot de passe.',
            ],
        ],
        'services_title' => 'Ce que je peux faire avec vous',
        'services_intro' => 'Choisissez ce qui vous ressemble le plus. Si vous ne trouvez pas, appelez-moi : nous en parlerons.',
        'services_all' => 'Voir tous les services',
        'how_title' => 'Comment cela se passe',
        'how' => [
            'step1' => [
                'title' => 'Vous expliquez votre besoin',
                'text' => 'Par téléphone ou avec le formulaire. Quelques mots suffisent.',
            ],
            'step2' => [
                'title' => 'Nous choisissons un rendez-vous ou un atelier',
                'text' => 'À la date et à l’endroit qui vous conviennent.',
            ],
            'step3' => [
                'title' => 'Je vous accompagne à votre rythme',
                'text' => 'Vous faites vous-même, je vous guide. Vous repartez avec une fiche écrite.',
            ],
        ],
        'workshops_title' => 'Les prochains ateliers',
        'workshops_intro' => 'Des séances en petit groupe, sur un thème précis.',
        'workshops_all' => 'Voir tous les ateliers',
        'workshops_empty' => 'Aucun atelier n’est programmé pour l’instant. Appelez-moi pour être prévenu du prochain.',
        'home_visit_title' => 'Je me déplace aussi chez vous',
        'home_visit_text' => 'Si vous avez du mal à vous déplacer, ou si votre problème concerne votre propre matériel, je viens à votre domicile.',
        'coverage_title' => 'Communes desservies',
        'testimonials_title' => 'Ils ont été accompagnés',
        'partners_title' => 'Avec le soutien de',
    ],

    'services' => [
        'title' => 'Mes services',
        'intro' => 'Voici les accompagnements que je propose. Tout est expliqué avec des mots simples.',
        'category_services' => 'Ce que nous verrons ensemble',
        'learning_points' => 'À la fin, vous saurez',
        'duration' => 'Durée indicative',
        'level' => 'Niveau',
        'other_categories' => 'Les autres familles de services',
        'empty' => 'Aucun service n’est publié pour le moment.',
    ],

    'workshops' => [
        'title' => 'Ateliers numériques',
        'intro' => 'Des séances en petit groupe, gratuites ou à petit prix, pour apprendre ensemble.',
        'free' => 'Gratuit',
        'seats_remaining' => '{0}Complet|{1}Il reste 1 place|[2,*]Il reste :count places',
        'seats_label' => 'Places',
        'full' => 'Cet atelier est complet.',
        'waiting_list_open' => 'Vous pouvez vous inscrire en liste d’attente : je vous préviens dès qu’une place se libère.',
        'cancelled' => 'Cet atelier a été annulé.',
        'register' => 'M’inscrire à cet atelier',
        'register_waiting' => 'M’inscrire en liste d’attente',
        'registrations_closed' => 'Les inscriptions à cet atelier sont terminées. Appelez-moi, je vous proposerai une autre date.',
        'registered_confirmed' => 'Votre inscription est enregistrée. Votre référence est :reference. Notez-la ou gardez ce message.',
        'registered_waiting' => 'L’atelier est complet : vous êtes en liste d’attente. Votre référence est :reference. Je vous préviens dès qu’une place se libère.',
        'deadline' => 'Inscriptions jusqu’au :date',
        'when' => 'Quand',
        'where' => 'Où',
        'level' => 'Pour qui',
        'objectives' => 'Ce que vous saurez faire',
        'prerequisites' => 'Ce qu’il faut savoir avant',
        'accessible' => 'Locaux accessibles aux personnes à mobilité réduite',
        'not_accessible' => 'Locaux non accessibles aux personnes à mobilité réduite : appelez-moi, nous trouverons une solution.',
        'equipment_provided' => 'Le matériel est fourni.',
        'bring_device' => 'Vous pouvez apporter votre propre appareil.',
        'instructor' => 'Animé par',
        'documents' => 'Documents à télécharger',
        'related' => 'Les autres ateliers à venir',
        'past' => 'Ateliers déjà passés',
        'empty' => 'Aucun atelier ne correspond à votre recherche.',
        'filters' => [
            'title' => 'Affiner la liste',
            'category' => 'Thème',
            'municipality' => 'Commune',
            'all' => 'Tous',
            'submit' => 'Afficher les ateliers',
            'reset' => 'Effacer les filtres',
        ],
        'phone_alternative' => 'Vous préférez vous inscrire par téléphone ? Appelez-moi, je remplis le formulaire avec vous.',
    ],

    'appointments' => [
        'title' => 'Prendre rendez-vous',
        'intro' => 'Remplissez ce formulaire, ou appelez-moi si vous préférez. Je vous rappelle sous deux jours ouvrés.',
        'type_title' => 'De quel type de rendez-vous avez-vous besoin ?',
        'identity_title' => 'Comment vous joindre',
        'need_title' => 'Votre besoin',
        'availability_title' => 'Vos disponibilités',
        'submit' => 'Envoyer ma demande',
        'confirmation_title' => 'Votre demande est bien arrivée',
        'confirmation_reference' => 'Votre référence est :reference. Notez-la : elle nous fera gagner du temps au téléphone.',
        'confirmation_next' => 'Je vous rappelle sous deux jours ouvrés au numéro que vous m’avez laissé.',
        'confirmation_email' => 'Un message de confirmation vient de partir vers votre adresse électronique.',
        'confirmation_no_email' => 'Vous n’avez pas indiqué d’adresse électronique : je vous rappellerai par téléphone.',
        'no_email_note' => 'Vous n’avez pas d’adresse électronique ? Ce n’est pas un problème : laissez simplement votre numéro de téléphone.',
    ],

    'contact' => [
        'title' => 'Contact',
        'intro' => 'Le plus simple reste le téléphone. Mais vous pouvez aussi m’écrire avec ce formulaire.',
        'form_title' => 'M’écrire',
        'submit' => 'Envoyer mon message',
        'sent' => 'Merci, votre message est bien arrivé. Votre référence est :reference.',
        'closed' => 'Fermé',
        'hours_range' => 'de :start à :end',
        'locations_title' => 'Où me rencontrer',
        'open_map' => 'Ouvrir dans une application de cartographie',
        'accessible' => 'Accessible aux personnes à mobilité réduite',
    ],

    'partnership' => [
        'title' => 'Communes, associations et entreprises',
        'intro' => 'Vous représentez une mairie, un CCAS, une médiathèque, une association, une résidence seniors ou une entreprise ? J’interviens chez vous, sur site.',
        'services_title' => 'Ce que je peux mettre en place',
        'form_title' => 'Décrivez votre projet',
        'submit' => 'Envoyer ma demande',
        'sent' => 'Merci, votre demande est bien arrivée. Votre référence est :reference. Je vous réponds sous cinq jours ouvrés.',
        'current_partners' => 'Structures déjà partenaires',

        'brochure' => 'Voir la présentation à imprimer',
        'brochure_help' => 'Une page à imprimer ou à transmettre par courriel, pour présenter les interventions en conseil municipal ou en réunion.',

        // -- Collectivités et associations --------------------------------
        'public_title' => 'Communes, CCAS, médiathèques et associations',
        'public_intro' => 'Pour vos administrés ou vos adhérents, en particulier les personnes âgées et celles que le numérique met en difficulté.',

        'public_offers' => [
            'permanence' => [
                'title' => 'Permanence numérique',
                'duration' => 'Une demi-journée, à intervalle régulier',
                'text' => 'J’accueille vos administrés sur rendez-vous ou sans rendez-vous, dans vos locaux. Chacun repart avec une fiche écrite.',
            ],
            'workshops' => [
                'title' => 'Cycle d’ateliers collectifs',
                'duration' => '2 heures par séance, 6 à 10 personnes',
                'text' => 'Un thème par séance : premiers pas, smartphone, démarches en ligne, arnaques. Le matériel peut être fourni.',
            ],
            'scam_prevention' => [
                'title' => 'Réunion de prévention des arnaques',
                'duration' => '1 h 30, jusqu’à 40 personnes',
                'text' => 'En salle communale ou en club de retraités. Faux courriels, faux conseiller bancaire, et désormais imitation de voix par intelligence artificielle.',
            ],
            'staff' => [
                'title' => 'Sensibilisation de vos agents',
                'duration' => '2 heures, sur site',
                'text' => 'Pour les agents d’accueil qui orientent le public : repérer une difficulté, savoir vers qui renvoyer, ne pas se substituer à l’usager.',
            ],
        ],

        // -- Entreprises ---------------------------------------------------
        'business_title' => 'Entreprises et organisations',
        'business_intro' => 'Vos équipes sont exposées aux mêmes fraudes que les particuliers, avec des montants sans commune mesure. Une matinée bien employée coûte moins cher qu’un virement parti chez un escroc.',

        'business_offers' => [
            'ai_awareness' => [
                'title' => 'Arnaques par intelligence artificielle',
                'duration' => '1 h 30 à 2 heures',
                'text' => 'Courriels sans faute d’orthographe, imitation de la voix d’un dirigeant, faux fournisseurs. Ce qui a changé, et les procédures simples qui protègent.',
            ],
            'cybersecurity' => [
                'title' => 'Hygiène numérique au quotidien',
                'duration' => '2 heures',
                'text' => 'Mots de passe, double authentification, pièces jointes, réseaux publics. Des gestes concrets, sans jargon technique.',
            ],
            'ai_discovery' => [
                'title' => 'Découverte de l’intelligence artificielle',
                'duration' => 'Une demi-journée',
                'text' => 'Ce que ces outils savent faire, ce qu’ils inventent, et ce qu’il ne faut jamais y écrire. Pour des équipes qui commencent à s’en servir.',
            ],
            'diagnosis' => [
                'title' => 'Diagnostic des usages',
                'duration' => 'Sur devis',
                'text' => 'Un état des lieux des pratiques et des points faibles, avec des recommandations classées par priorité.',
            ],
        ],

        'format_title' => 'Comment cela se passe',
        'format' => [
            'onsite' => 'J’interviens dans vos locaux, avec ou sans matériel selon vos moyens.',
            'group' => 'En petit groupe pour les ateliers, en salle pour les réunions de sensibilisation.',
            'materials' => 'Chaque participant repart avec une fiche écrite, réutilisable ensuite.',
            'quote' => 'Un devis vous est adressé avant toute intervention, sans engagement.',
        ],

        'needs' => [
            'permanence' => 'Permanence numérique régulière',
            'workshops' => 'Ateliers collectifs',
            'individual' => 'Accompagnement individuel',
            'scam_prevention' => 'Prévention des arnaques',
            'ai_awareness' => 'Sensibilisation aux arnaques par intelligence artificielle',
            'ai_discovery' => 'Découverte de l’intelligence artificielle pour vos équipes',
            'staff_training' => 'Sensibilisation de vos agents',
            'municipal_services' => 'Aide à l’utilisation de vos services en ligne',
            'materials' => 'Création de supports pédagogiques',
            'diagnosis' => 'Diagnostic des besoins numériques',
            'caregivers' => 'Accompagnement des aidants',
            'cybersecurity' => 'Initiation à la cybersécurité',
        ],
    ],

    'pricing' => [
        'title' => 'Tarifs',
        'intro' => 'Les tarifs sont clairs et annoncés à l’avance. Un devis est possible pour toute demande particulière.',
        'on_quote' => 'Sur devis',
        'includes' => 'Ce qui est inclus',
        'duration' => 'Durée',
        'travel' => 'Frais de déplacement',
        'payment' => 'Moyens de paiement',
        'cancellation' => 'Annulation',
        'empty' => 'Les tarifs sont en cours de mise à jour. Appelez-moi, je vous renseigne.',
        'quote_cta' => 'Demander un devis',
    ],

    'resources' => [
        'title' => 'Conseils pratiques',
        'intro' => 'Des fiches à imprimer et des articles courts, pour retrouver chez vous ce que nous avons vu ensemble.',
        'guides_title' => 'Fiches pratiques',
        'articles_title' => 'Articles',
        'search_label' => 'Rechercher un sujet',
        'search_placeholder' => 'Exemple : mot de passe',
        'search_submit' => 'Rechercher',
        'category_all' => 'Toutes les rubriques',
        'empty' => 'Aucun contenu ne correspond à votre recherche.',
        'reading_time' => ':count minutes de lecture',
        'estimated_time' => 'Environ :count minutes',
        'updated_on' => 'Vérifié le :date',
        'never_reviewed' => 'Date de vérification non renseignée',
        'level' => 'Niveau',
        'prerequisites' => 'Avant de commencer, munissez-vous de',
        'steps_title' => 'Les étapes, une par une',
        'tip' => 'Astuce',
        'safety' => 'À retenir pour votre sécurité',
        'print' => 'Imprimer cette fiche',
        'documents' => 'Documents à télécharger',
        'related' => 'À lire aussi',
        'back' => 'Revenir aux conseils pratiques',
    ],

    'security' => [
        'warning' => 'En cas de doute, ne cliquez pas, ne payez pas et appelez une personne de confiance.',
        'links_title' => 'Les sites officiels pour vérifier ou signaler',
        'faq_title' => 'Ce qu’on me demande souvent',
    ],

    'procedures' => [
        'links_title' => 'Les sites officiels des démarches',
        'rules_title' => 'Les règles que je respecte, et que je vous demande de respecter',
        'rules' => [
            'no_password_email' => 'Ne transmettez jamais votre mot de passe par courrier électronique.',
            'no_bank_code' => 'Ne communiquez jamais votre code bancaire, à personne.',
            'no_sms_code' => 'Ne donnez jamais un code reçu par SMS, même à moi.',
            'stay_actor' => 'Vous restez acteur de votre démarche : vous saisissez vous-même, je vous guide.',
            'personal_only' => 'Certaines démarches ne peuvent être faites que par vous : une déclaration sur l’honneur, par exemple.',
            'no_storage' => 'Je ne conserve aucun de vos documents administratifs sans nécessité.',
        ],
    ],

    'about' => [
        'testimonials_title' => 'Ce qu’en disent les personnes accompagnées',
    ],

    'faq' => [
        'title' => 'Questions fréquentes',
        'intro' => 'Voici les questions que l’on me pose le plus souvent. Si la vôtre n’y est pas, appelez-moi.',
        'empty' => 'Aucune question n’est publiée pour le moment.',
        'groups' => [
            'general' => 'Questions générales',
            'rendez-vous' => 'Rendez-vous',
            'ateliers' => 'Ateliers',
            'securite' => 'Sécurité et arnaques',
            'tarifs' => 'Tarifs',
            'accessibilite' => 'Accessibilité',
        ],
    ],

    'accessibility' => [
        'report_title' => 'État de conformité',
        'audited_on' => 'Audit réalisé le :date',
        'rate' => 'Taux de conformité : :rate %',
        'referential' => 'Référentiel : :referential',
        'non_conformities' => 'Points non conformes connus',
        'improvement_plan' => 'Ce qui est prévu pour améliorer le site',
        'no_report' => 'Aucun audit n’a encore été réalisé sur ce site.',
        'level' => [
            'none' => 'Non conforme',
            'partial' => 'Partiellement conforme',
            'full' => 'Totalement conforme',
        ],
    ],

    'testimonials' => [
        'anonymous' => 'Une personne accompagnée',
    ],

    'cta' => [
        'appointment' => 'Prendre rendez-vous',
        'workshops' => 'Découvrir les ateliers',
        'call' => 'Appeler pour être accompagné',
        'final_title' => 'Une question ?',
        'contact' => 'M’écrire un message',
    ],

    'footer' => [
        'about_title' => 'Le service',
        'contact_title' => 'Me joindre',
        'legal_title' => 'Informations',
        'quick_title' => 'Accès rapide',
        'copyright' => '© :year :name',
        'made_with' => 'Site conçu pour être lisible et utilisable par tous.',
    ],

    'cookies' => [
        'title' => 'Ce site utilise une mesure d’audience',
        'text' => 'Elle nous aide à savoir quelles pages sont utiles. Aucune donnée n’est revendue, et vous pouvez refuser sans perdre aucune fonctionnalité.',
        'accept' => 'Accepter',
        'refuse' => 'Refuser',
        'more' => 'En savoir plus',
    ],

    'common' => [
        'read_more' => 'Lire la suite',
        'learn_more' => 'En savoir plus',
        'back' => 'Retour',
        'required_fields' => 'Les champs suivis d’une étoile sont obligatoires.',
        'optional' => 'facultatif',
        'download' => 'Télécharger',
        'new_window' => 'nouvelle fenêtre',
        'external_link' => 'site externe',
        'phone_fallback_title' => 'Vous préférez le téléphone ?',
        'phone_fallback_text' => 'Appelez-moi, je remplis ce formulaire avec vous. Cela ne prend que quelques minutes.',
        'errors_title' => 'Votre formulaire n’a pas pu être envoyé',
        'errors_intro' => 'Voici ce qu’il faut corriger :',
        'sending' => 'Envoi en cours…',
        'date_at_time' => ':date à :time',
    ],

];
