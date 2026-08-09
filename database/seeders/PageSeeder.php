<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Pages système : légales, éditoriales et pédagogiques.
 *
 * Les textes juridiques posés ici sont des trames de travail : ils doivent
 * être complétés et relus avant toute mise en ligne réelle.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $position => $page) {
            $existing = Page::query()->where('key', $page['key'])->first();

            if ($existing === null) {
                Page::create([
                    ...$page,
                    'status' => ContentStatus::Published,
                    'is_system' => true,
                    'published_at' => now(),
                    'position' => $position,
                ]);

                continue;
            }

            // La page existe déjà : son contenu a pu être retouché depuis le
            // back-office. Le réécrire à chaque déploiement effacerait ce
            // travail sans prévenir. Seuls les champs structurants — ceux
            // dont dépendent les liens du pied de page — sont réalignés.
            $existing->update([
                'is_system' => true,
                'show_in_footer' => $page['show_in_footer'],
                'position' => $position,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function pages(): array
    {
        return [
            [
                'key' => Page::KEY_ABOUT,
                'slug' => 'a-propos',
                'title' => 'À propos',
                'summary' => 'Qui je suis, comment je travaille, et ce que je m’engage à faire.',
                'show_in_footer' => false,
                'body' => <<<'TEXTE'
                    Je suis conseiller numérique. Mon métier consiste à accompagner les habitants
                    dans l’usage de l’informatique, d’Internet, du téléphone et des démarches en ligne.

                    Mon parcours

                    J’ai travaillé plusieurs années dans l’informatique avant de me consacrer à
                    l’accompagnement des personnes. Ce sont deux métiers très différents : le premier
                    demande de comprendre les machines, le second de comprendre les gens.

                    Ma méthode

                    Je ne fais pas à votre place. Vous tenez la souris, vous tapez sur le clavier, et
                    je vous guide. C’est plus lent au début, mais c’est la seule façon de repartir en
                    sachant refaire seul.

                    Nous avançons une étape à la fois. Vous pouvez me faire répéter autant de fois que
                    nécessaire : c’est normal, et c’est même souhaitable.

                    À la fin de chaque séance, je vous remets une fiche écrite reprenant ce que nous
                    avons vu, pour que vous puissiez la reprendre tranquillement chez vous.

                    Mes engagements

                    Je ne conserve jamais vos mots de passe. Je ne vous demande jamais votre code
                    bancaire. Ce que vous me confiez reste entre nous.

                    Si votre demande dépasse mon domaine — un conseil juridique, fiscal, médical ou
                    financier — je vous le dis franchement et je vous oriente vers la bonne personne.
                    TEXTE,
            ],

            [
                'key' => Page::KEY_ONLINE_PROCEDURES,
                'slug' => 'demarches-en-ligne',
                'title' => 'Démarches en ligne',
                'summary' => 'Ce que je peux faire avec vous, et ce que je ne peux pas faire à votre place.',
                'show_in_footer' => false,
                'body' => <<<'TEXTE'
                    Beaucoup de démarches ne se font plus qu’en ligne. C’est souvent là que le
                    numérique devient pesant. Voici comment nous procédons ensemble.

                    Ce que je peux faire avec vous

                    Vous aider à créer votre espace personnel sur un site administratif.
                    Vous montrer où cliquer, et pourquoi.
                    Vous expliquer ce que le formulaire vous demande, dans des mots courants.
                    Vous aider à retrouver un document que vous avez téléchargé.
                    Vous accompagner pour utiliser FranceConnect.
                    Vous aider à préparer les pièces à joindre.

                    Ce que je ne peux pas faire

                    Je ne peux pas remplir une déclaration sur l’honneur à votre place : la loi vous en
                    fait porter la responsabilité, et vous seul pouvez l’engager.

                    Je ne peux pas me connecter avec vos identifiants en votre absence.

                    Je ne donne pas de conseil juridique, fiscal, médical ni financier. Si votre
                    situation le demande, je vous oriente vers France Services, un travailleur social
                    ou le professionnel compétent.

                    Ce qu’il faut apporter

                    Vos identifiants si vous en avez déjà. Votre numéro de sécurité sociale, votre
                    numéro fiscal ou votre numéro d’allocataire, selon la démarche. Votre téléphone,
                    car certains sites envoient un code par SMS.

                    Et si je ne peux pas vous aider ?

                    Certaines démarches demandent un accompagnement spécialisé. Dans ce cas, je vous
                    dis vers qui vous tourner, et je peux vous aider à prendre le rendez-vous.
                    TEXTE,
            ],

            [
                'key' => Page::KEY_SECURITY,
                'slug' => 'securite-et-arnaques',
                'title' => 'Sécurité et arnaques',
                'summary' => 'Reconnaître les pièges les plus fréquents, et savoir quoi faire en cas de doute.',
                'show_in_footer' => false,
                'body' => <<<'TEXTE'
                    Les arnaques en ligne visent particulièrement les personnes qui débutent. Ce n’est
                    pas une question d’intelligence : elles sont conçues pour tromper tout le monde.

                    Ce qui a changé récemment

                    Pendant longtemps, on reconnaissait une arnaque à ses fautes d’orthographe. Ce
                    repère ne fonctionne plus : les messages frauduleux sont désormais écrits par
                    des machines, dans un français impeccable.

                    Plus grave encore, une voix au téléphone ne prouve plus rien. Quelques secondes
                    suffisent à en fabriquer une imitation convaincante. C’est ainsi qu’opère
                    l’arnaque dite « au faux proche » : la voix d’un enfant ou d’un petit-enfant, en
                    larmes, réclamant de l’argent tout de suite.

                    La parade est simple et ne demande aucune technique : convenez en famille d’un
                    mot que vous seuls connaissez, et demandez-le. Une machine ne le connaît pas.

                    Reconnaître un faux message

                    Un vrai organisme ne vous demandera jamais votre mot de passe par courriel ni par
                    téléphone. Jamais.

                    Méfiez-vous des messages qui vous pressent : « votre compte sera fermé sous
                    24 heures », « dernier avertissement ». L’urgence est l’outil principal des
                    escrocs, parce qu’elle empêche de réfléchir.

                    Regardez l’adresse de l’expéditeur, pas seulement le nom affiché. Un message de
                    « Service des impôts » envoyé depuis une adresse inconnue est un faux.

                    Le faux conseiller bancaire

                    Quelqu’un vous appelle, connaît votre nom et votre banque, et vous demande de
                    valider une opération pour « bloquer une fraude ». C’est l’arnaque. Raccrochez, et
                    rappelez votre banque au numéro figurant sur votre carte.

                    Le faux support technique

                    Un message annonce que votre ordinateur est infecté et vous invite à appeler un
                    numéro. Personne ne peut savoir à distance que votre ordinateur a un problème.
                    Fermez la fenêtre, éteignez l’ordinateur si besoin.

                    Le faux colis

                    Un SMS annonce un colis en attente et réclame quelques euros de frais. L’objectif
                    est de récupérer votre numéro de carte bancaire.

                    Que faire si vous avez été victime

                    Ce n’est pas honteux, et vous n’êtes pas seul : cela arrive à beaucoup de monde.

                    Appelez immédiatement votre banque pour faire opposition. Changez vos mots de
                    passe depuis un autre appareil. Signalez la fraude sur les sites officiels listés
                    plus bas. Puis appelez-moi : je vous aide à sécuriser vos comptes.
                    TEXTE,
            ],

            [
                'key' => Page::KEY_LEGAL,
                'slug' => 'mentions-legales',
                'title' => 'Mentions légales',
                'summary' => 'Qui édite ce site et qui l’héberge.',
                'show_in_footer' => true,
                'body' => <<<'TEXTE'
                    Éditeur du site

                    Les informations d’identification de l’éditeur (nom, forme juridique, adresse,
                    numéro SIRET, téléphone, adresse électronique) se renseignent depuis l’espace
                    d’administration, rubrique « Paramètres du site ».

                    Directeur de la publication

                    À compléter depuis les paramètres du site.

                    Hébergement

                    À compléter depuis les paramètres du site : nom, adresse et téléphone de
                    l’hébergeur, comme l’exige la loi pour la confiance dans l’économie numérique.

                    Propriété intellectuelle

                    Les textes, les fiches pratiques et les visuels de ce site sont la propriété de
                    l’éditeur, sauf mention contraire. Leur reproduction est autorisée à des fins
                    pédagogiques non commerciales, sous réserve d’en citer la source.

                    Liens vers d’autres sites

                    Ce site renvoie vers des sites officiels d’organismes publics. L’éditeur n’a
                    aucun contrôle sur leur contenu et ne saurait en être tenu responsable.
                    TEXTE,
            ],

            [
                'key' => Page::KEY_PRIVACY,
                'slug' => 'politique-de-confidentialite',
                'title' => 'Politique de confidentialité',
                'summary' => 'Quelles informations sont collectées, pourquoi, combien de temps, et comment les faire effacer.',
                'show_in_footer' => true,
                'body' => <<<'TEXTE'
                    Ce site collecte le minimum d’informations nécessaires pour vous répondre.

                    Ce qui est collecté, et pourquoi

                    Quand vous demandez un rendez-vous : votre prénom, votre nom, votre téléphone,
                    éventuellement votre adresse électronique, votre commune et la description de
                    votre besoin. Ces informations servent uniquement à vous recontacter et à préparer
                    le rendez-vous.

                    Quand vous vous inscrivez à un atelier : votre prénom, votre nom, votre téléphone,
                    éventuellement votre adresse électronique et votre commune. Elles servent à gérer
                    votre inscription et à vous prévenir en cas de changement.

                    Quand vous envoyez un message : vos coordonnées et le contenu de votre message.

                    La tranche d’âge est toujours facultative. Elle ne sert qu’à établir des bilans
                    agrégés pour les communes qui financent le service, jamais à vous suivre
                    individuellement.

                    Ce qui n’est jamais collecté

                    Vos mots de passe. Vos codes bancaires. Les codes que vous recevez par SMS. Vos
                    identifiants FranceConnect. Aucune copie de pièce d’identité.

                    Combien de temps

                    Les demandes de rendez-vous et les inscriptions sont conservées trois ans après
                    leur clôture, puis rendues anonymes automatiquement. Les messages envoyés depuis
                    le site sont conservés un an.

                    Rendre anonyme signifie que vos coordonnées sont remplacées par des valeurs
                    neutres : le comptage reste juste, mais plus rien ne permet de vous identifier.

                    Vos droits

                    Vous pouvez demander à consulter les informations vous concernant, à les faire
                    corriger, ou à les faire effacer. Vous pouvez aussi retirer votre accord à tout
                    moment.

                    Il suffit d’appeler, ou d’écrire à l’adresse électronique indiquée en pied de
                    page. Une vérification de votre identité vous sera demandée, afin qu’une autre
                    personne ne puisse pas obtenir vos données à votre place.

                    La réponse vous parvient sous un mois.

                    Mesure d’audience

                    Si une mesure d’audience est activée, elle est configurée sans cookie et sans
                    transmission à un tiers. Vous pouvez la refuser sans perdre aucune fonctionnalité
                    du site.

                    Réclamation

                    Si vous estimez que vos droits ne sont pas respectés, vous pouvez saisir la
                    Commission nationale de l’informatique et des libertés (CNIL).
                    TEXTE,
            ],

            [
                'key' => Page::KEY_COOKIES,
                'slug' => 'gestion-des-cookies',
                'title' => 'Gestion des cookies',
                'summary' => 'Ce site n’utilise aucun cookie publicitaire.',
                'show_in_footer' => true,
                'body' => <<<'TEXTE'
                    Ce site fonctionne sans cookie publicitaire et sans traceur commercial.

                    Ce qui est strictement nécessaire

                    Un cookie de session permet de garder votre formulaire en cours de remplissage et
                    de protéger les envois contre la fraude. Il disparaît à la fermeture du
                    navigateur, et ne demande pas votre accord car le site ne pourrait pas fonctionner
                    sans lui.

                    Vos réglages d’affichage — contraste renforcé, taille du texte, animations
                    réduites — sont conservés sur votre appareil uniquement. Ils ne sont transmis à
                    personne.

                    Mesure d’audience

                    Si une mesure d’audience est activée, elle vous est proposée par un bandeau, et
                    n’est déposée qu’après votre accord explicite. Le refus est aussi simple que
                    l’acceptation, et n’enlève aucune fonctionnalité.

                    Revenir sur votre choix

                    Effacez les données de navigation de ce site depuis les réglages de votre
                    navigateur : le bandeau vous sera de nouveau proposé.
                    TEXTE,
            ],

            [
                'key' => Page::KEY_ACCESSIBILITY,
                'slug' => 'declaration-accessibilite',
                'title' => 'Déclaration d’accessibilité',
                'summary' => 'L’engagement pris sur l’accessibilité de ce site, et comment signaler une difficulté.',
                'show_in_footer' => true,
                'body' => <<<'TEXTE'
                    Ce site a été conçu pour être utilisable par des personnes ayant une vision
                    réduite, une mobilité limitée, ou peu d’habitude des sites web.

                    Ce qui a été mis en place

                    Un contraste élevé entre le texte et le fond, vérifié couple par couple.
                    Un texte de 18 pixels au minimum sur ordinateur, avec un interligne large.
                    Trois réglages d’affichage : contraste renforcé, agrandissement du texte,
                    réduction des animations.
                    Une navigation complète au clavier, avec un repère de focus très visible.
                    Des liens toujours soulignés, jamais signalés par la seule couleur.
                    Des formulaires à labels visibles, avec les erreurs affichées près du champ.
                    Aucun carrousel automatique, aucune vidéo en lecture automatique, aucun captcha.

                    Signaler une difficulté

                    Si une page vous pose problème, appelez-moi ou écrivez-moi. Décrivez simplement ce
                    que vous avez essayé de faire et ce qui s’est passé : cela suffit. Chaque signalement
                    est traité, et vous recevez une réponse.

                    Voie de recours

                    Si vous constatez un défaut d’accessibilité vous empêchant d’accéder à un contenu
                    et que vous n’obtenez pas de réponse satisfaisante, vous pouvez saisir le
                    Défenseur des droits.
                    TEXTE,
            ],
        ];
    }
}
