<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\SkillLevel;
use App\Models\AccessibilityReport;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Faq;
use App\Models\OfficialLink;
use App\Models\PracticalGuide;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Centre de ressources : rubriques, fiches pratiques, articles, questions
 * fréquentes, témoignages et liens officiels.
 */
class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Courrier électronique', 'slug' => 'courriel'],
            ['name' => 'Démarches administratives', 'slug' => 'demarches'],
            ['name' => 'Sécurité', 'slug' => 'securite'],
            ['name' => 'Photos et souvenirs', 'slug' => 'photos'],
        ])->mapWithKeys(fn (array $category, int $position): array => [
            $category['slug'] => ArticleCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'status' => ContentStatus::Published, 'position' => $position],
            ),
        ]);

        $this->seedGuides($categories);
        $this->seedArticles($categories);
        $this->seedFaqs();
        $this->seedTestimonials();
        $this->seedOfficialLinks();
        $this->seedAccessibilityReport();
    }

    /**
     * @param  Collection<string, ArticleCategory>  $categories
     */
    protected function seedGuides($categories): void
    {
        $guides = [
            [
                'title' => 'Créer une adresse électronique',
                'slug' => 'creer-une-adresse-electronique',
                'category' => 'courriel',
                'summary' => 'Une boîte aux lettres électronique, c’est la clé de presque toutes les démarches en ligne. Voici comment en créer une.',
                'level' => SkillLevel::Beginner,
                'minutes' => 20,
                'prerequisites' => 'Un ordinateur ou un téléphone connecté à Internet, et de quoi noter votre mot de passe sur papier.',
                'safety' => 'Notez votre mot de passe sur un papier que vous rangez chez vous, pas dans votre portefeuille. Ne le communiquez à personne, pas même à moi.',
                'introduction' => 'Une adresse électronique se compose toujours de la même façon : un nom, le signe arobase (@), puis le nom du fournisseur. Par exemple : marie.durand@exemple.fr',
                'conclusion' => 'Votre adresse est créée. Notez-la en majuscules et en minuscules exactement comme vous l’avez saisie : l’ordinateur fait la différence.',
                'steps' => [
                    ['title' => 'Ouvrir le site du fournisseur', 'body' => 'Ouvrez votre navigateur, puis tapez l’adresse du fournisseur dans la barre du haut. Appuyez sur la touche Entrée.'],
                    ['title' => 'Cliquer sur « Créer un compte »', 'body' => 'Le bouton se trouve généralement en haut à droite de la page. Il peut aussi s’appeler « S’inscrire ».'],
                    ['title' => 'Choisir votre adresse', 'body' => 'Prenez quelque chose de simple à dicter au téléphone : votre prénom et votre nom, séparés par un point. Si l’adresse est déjà prise, ajoutez un chiffre.', 'tip' => 'Évitez les surnoms : vous donnerez cette adresse à votre banque et à l’administration.'],
                    ['title' => 'Choisir un mot de passe', 'body' => 'Prenez trois mots sans rapport entre eux, collés ensemble. Par exemple : CerisierLampeVoiture. C’est plus solide et bien plus facile à retenir qu’une suite de symboles.'],
                    ['title' => 'Noter votre mot de passe', 'body' => 'Écrivez-le sur un papier, rangé chez vous, à un endroit dont vous vous souviendrez. Ce n’est pas une mauvaise pratique : c’est bien mieux que de l’oublier.'],
                    ['title' => 'Envoyer un premier message', 'body' => 'Écrivez à un proche pour vérifier que tout fonctionne. Demandez-lui de vous répondre.'],
                ],
            ],
            [
                'title' => 'Reconnaître un faux message',
                'slug' => 'reconnaitre-un-faux-message',
                'category' => 'securite',
                'summary' => 'Quatre vérifications simples pour repérer une tentative d’arnaque, en moins d’une minute.',
                'level' => SkillLevel::Everyone,
                'minutes' => 10,
                'safety' => 'Dans le doute, ne cliquez sur aucun lien. Fermez le message et appelez l’organisme au numéro que vous connaissez déjà.',
                'introduction' => 'Les faux messages imitent très bien les vrais. Mais ils laissent presque toujours des traces. En voici quatre.',
                'conclusion' => 'Si un doute subsiste, appelez-moi ou appelez une personne de confiance avant de faire quoi que ce soit. Prendre cinq minutes ne coûte rien.',
                'steps' => [
                    ['title' => 'Regarder l’adresse de l’expéditeur', 'body' => 'Le nom affiché peut être trompeur. Cliquez dessus pour voir l’adresse réelle. Une adresse bizarre ou inconnue est un signal fort.'],
                    ['title' => 'Se méfier de l’urgence', 'body' => 'Un vrai organisme ne vous menace jamais de fermer votre compte sous 24 heures. L’urgence sert à vous empêcher de réfléchir.'],
                    ['title' => 'Ne jamais donner de mot de passe', 'body' => 'Aucun organisme sérieux ne demande votre mot de passe, ni par courriel, ni par téléphone. Aucun. Sans exception.'],
                    ['title' => 'Vérifier par un autre chemin', 'body' => 'Au lieu de cliquer sur le lien du message, ouvrez votre navigateur et tapez vous-même l’adresse du site que vous connaissez.', 'tip' => 'Gardez le site officiel en favori : vous y accéderez toujours par un chemin sûr.'],
                ],
            ],
            [
                'title' => 'Envoyer une photo à un proche',
                'slug' => 'envoyer-une-photo-a-un-proche',
                'category' => 'photos',
                'summary' => 'Prendre une photo avec votre téléphone et l’envoyer à votre famille, en cinq étapes.',
                'level' => SkillLevel::Beginner,
                'minutes' => 15,
                'prerequisites' => 'Un téléphone avec un appareil photo, et le numéro de la personne enregistré dans vos contacts.',
                'steps' => [
                    ['title' => 'Ouvrir l’appareil photo', 'body' => 'Touchez l’icône en forme d’appareil photo sur l’écran d’accueil.'],
                    ['title' => 'Prendre la photo', 'body' => 'Cadrez ce que vous voulez photographier, puis appuyez sur le gros bouton rond en bas de l’écran.'],
                    ['title' => 'Ouvrir les messages', 'body' => 'Revenez à l’écran d’accueil, puis touchez l’icône des messages.'],
                    ['title' => 'Choisir le destinataire', 'body' => 'Touchez le bouton pour écrire un nouveau message, puis tapez les premières lettres du prénom de la personne.'],
                    ['title' => 'Joindre la photo et envoyer', 'body' => 'Touchez l’icône en forme de trombone ou d’appareil photo, choisissez votre photo, puis appuyez sur la flèche d’envoi.', 'tip' => 'Si l’envoi échoue, vérifiez que vous êtes connecté au Wi-Fi ou que vous avez du réseau.'],
                ],
            ],
        ];

        foreach ($guides as $data) {
            $guide = PracticalGuide::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'article_category_id' => $categories[$data['category']]->id,
                    'title' => $data['title'],
                    'summary' => $data['summary'],
                    'introduction' => $data['introduction'] ?? null,
                    'level' => $data['level'],
                    'estimated_minutes' => $data['minutes'],
                    'prerequisites' => $data['prerequisites'] ?? null,
                    'safety_notice' => $data['safety'] ?? null,
                    'conclusion' => $data['conclusion'] ?? null,
                    'status' => ContentStatus::Published,
                    'is_featured' => true,
                    'published_at' => now(),
                    'reviewed_on' => now()->subMonths(2),
                ],
            );

            if ($guide->steps()->exists()) {
                continue;
            }

            foreach ($data['steps'] as $position => $step) {
                $guide->steps()->create([
                    'position' => $position + 1,
                    'title' => $step['title'],
                    'body' => $step['body'],
                    'tip' => $step['tip'] ?? null,
                ]);
            }
        }
    }

    /**
     * @param  Collection<string, ArticleCategory>  $categories
     */
    protected function seedArticles($categories): void
    {
        $articles = [
            [
                'title' => 'Pourquoi je ne vous demanderai jamais votre mot de passe',
                'slug' => 'jamais-votre-mot-de-passe',
                'category' => 'securite',
                'excerpt' => 'C’est la règle la plus importante de tout ce que je vous enseigne. Voici pourquoi elle ne souffre aucune exception.',
                'body' => "Beaucoup de personnes me tendent spontanément un papier avec leurs identifiants au début d'une séance. C'est un réflexe de confiance, et je le comprends. Mais je le refuse toujours.\n\nLa raison n'est pas que je me méfie de vous. C'est que je veux vous habituer à un réflexe qui vous protégera face à quelqu'un de moins bien intentionné.\n\nSi vous prenez l'habitude de ne jamais donner votre mot de passe, même à une personne de confiance, alors le jour où un faux conseiller bancaire vous le demandera au téléphone, votre première réaction sera de refuser. C'est ce réflexe-là qui vous protégera.\n\nConcrètement, pendant nos séances, c'est vous qui tapez votre mot de passe. Je regarde ailleurs. Si vous l'avez oublié, nous le réinitialisons ensemble : cela prend cinq minutes, et vous saurez le refaire seul.",
            ],
            [
                'title' => 'Trois façons simples de retenir ses mots de passe',
                'slug' => 'retenir-ses-mots-de-passe',
                'category' => 'securite',
                'excerpt' => 'Non, il ne faut pas mémoriser des suites de symboles. Voici trois méthodes qui fonctionnent vraiment.',
                'body' => "On vous a probablement dit qu'un bon mot de passe ressemble à « X7#kL9\$m ». C'est faux, ou du moins ce n'est plus la meilleure recommandation.\n\nLa première méthode : trois mots collés. « CerisierLampeVoiture » est plus difficile à deviner par une machine que « X7#kL9\$m », et infiniment plus facile à retenir pour un être humain.\n\nLa deuxième méthode : le carnet papier. Écrire ses mots de passe dans un carnet rangé chez soi n'est pas une faute. Le risque qu'un cambrioleur cherche votre carnet est bien plus faible que le risque d'utiliser le même mot de passe partout.\n\nLa troisième méthode : le gestionnaire de mots de passe. C'est un coffre-fort dans votre ordinateur, protégé par un seul mot de passe. Nous pouvons l'installer ensemble si cela vous intéresse.\n\nLa vraie règle, celle qui compte : un mot de passe différent pour votre boîte aux lettres électronique et pour votre banque. Ce sont les deux comptes qui donnent accès à tous les autres.",
            ],
            [
                'title' => 'Que faire si vous pensez avoir été victime d’une arnaque',
                'slug' => 'que-faire-apres-une-arnaque',
                'category' => 'securite',
                'excerpt' => 'Les premières heures comptent. Voici les gestes à faire, dans l’ordre.',
                'body' => "D'abord : ce n'est pas honteux. Les arnaques actuelles trompent des gens de tous âges et de tous milieux. Elles sont conçues pour cela.\n\nPremier geste : appelez votre banque et faites opposition. Le numéro figure au dos de votre carte bancaire. Ce geste est prioritaire sur tous les autres.\n\nDeuxième geste : changez le mot de passe de votre boîte aux lettres électronique, depuis un autre appareil si possible. C'est elle qui permet de réinitialiser tous vos autres comptes.\n\nTroisième geste : signalez la fraude sur les sites officiels. Les adresses sont listées sur la page « Sécurité et arnaques » de ce site.\n\nQuatrième geste : déposez plainte au commissariat ou à la gendarmerie, même si vous pensez que cela ne servira à rien. Les signalements permettent de démanteler les réseaux.\n\nEt appelez-moi. Nous ferons ensemble le tour de vos comptes pour vérifier que tout est sécurisé.",
            ],
            [
                'title' => 'Vous n’avez pas d’adresse électronique ? Ce n’est pas un problème',
                'slug' => 'sans-adresse-electronique',
                'category' => 'demarches',
                'excerpt' => 'Vous pouvez prendre rendez-vous et vous inscrire à un atelier avec votre seul numéro de téléphone.',
                'body' => "Beaucoup de sites exigent une adresse électronique pour la moindre démarche. Ce n'est pas le cas ici.\n\nSur ce site, le formulaire de rendez-vous et le formulaire d'inscription aux ateliers fonctionnent avec un simple numéro de téléphone. L'adresse électronique est facultative, et le restera.\n\nSi vous en avez une, vous recevrez une confirmation écrite : c'est pratique pour ne pas oublier la date. Si vous n'en avez pas, je vous rappelle, tout simplement.\n\nEt si vous souhaitez en créer une, nous pouvons le faire ensemble en vingt minutes. La fiche pratique correspondante est disponible dans les conseils pratiques.",
            ],
        ];

        foreach ($articles as $index => $article) {
            Article::updateOrCreate(
                ['slug' => $article['slug']],
                [
                    'article_category_id' => $categories[$article['category']]->id,
                    'title' => $article['title'],
                    'excerpt' => $article['excerpt'],
                    'body' => $article['body'],
                    'status' => ContentStatus::Published,
                    'is_featured' => $index === 0,
                    'published_at' => now()->subWeeks($index),
                ],
            );
        }
    }

    protected function seedFaqs(): void
    {
        $faqs = [
            ['general', 'Je n’ai pas d’adresse électronique, puis-je prendre rendez-vous ?', 'Oui, sans aucun problème. Le formulaire de rendez-vous ne demande qu’un numéro de téléphone. Vous pouvez aussi simplement m’appeler.'],
            ['general', 'Puis-je venir avec mon ordinateur ?', 'C’est même recommandé. Travailler sur votre propre appareil est la meilleure façon de savoir refaire chez vous. Pensez au chargeur.'],
            ['general', 'Intervenez-vous à domicile ?', 'Oui, dans un rayon de vingt kilomètres. Au-delà, des frais de déplacement s’appliquent : ils vous sont annoncés avant le rendez-vous.'],
            ['general', 'Puis-je venir accompagné ?', 'Bien sûr. Beaucoup de personnes viennent avec un enfant, un voisin ou un ami. Cela ne change rien au tarif.'],
            ['general', 'Que dois-je apporter ?', 'Votre appareil si vous en avez un, son chargeur, et vos identifiants si la démarche en demande. Rien d’autre.'],
            ['general', 'Pouvez-vous réparer mon ordinateur ?', 'Non, ce n’est pas mon métier. Je vous accompagne dans l’usage, pas dans la réparation. Si votre appareil est en panne, je peux vous orienter vers un réparateur de confiance.'],
            ['rendez-vous', 'Combien de temps avant êtes-vous disponible ?', 'En général sous une semaine. Pour une démarche urgente, appelez-moi : je fais au mieux.'],
            ['rendez-vous', 'Puis-je annuler un rendez-vous ?', 'Oui, appelez-moi dès que vous le savez. Aucun frais n’est retenu si vous prévenez au moins la veille.'],
            ['ateliers', 'Les ateliers sont-ils adaptés aux débutants ?', 'Le niveau est indiqué sur chaque atelier. Ceux marqués « Je débute » ne supposent aucune connaissance préalable.'],
            ['ateliers', 'Puis-je m’inscrire par téléphone ?', 'Oui. Appelez-moi, je remplis le formulaire avec vous. Cela prend deux minutes.'],
            ['ateliers', 'Combien de personnes par atelier ?', 'Six à dix personnes au maximum. Au-delà, on ne peut plus accompagner chacun correctement.'],
            ['securite', 'Mes mots de passe seront-ils conservés ?', 'Jamais. Je ne les note nulle part, et je ne vous les demanderai jamais. C’est vous qui les saisissez pendant nos séances.'],
            ['securite', 'Pouvez-vous récupérer un mot de passe perdu ?', 'Je ne peux pas le retrouver, mais je peux vous accompagner dans la procédure officielle de réinitialisation. C’est vous qui la réalisez, je vous guide.'],
            ['securite', 'Que faire si je pense avoir été victime d’une arnaque ?', 'Appelez immédiatement votre banque pour faire opposition, puis appelez-moi. La page « Sécurité et arnaques » détaille tous les gestes à faire.'],
            ['securite', 'Pouvez-vous faire une démarche à ma place ?', 'Je vous accompagne, mais c’est vous qui réalisez la démarche. Certaines, comme une déclaration sur l’honneur, ne peuvent légalement être faites que par vous.'],
            ['tarifs', 'Comment puis-je payer ?', 'En espèces, par chèque ou par virement, à l’issue de la séance. Aucun paiement n’est demandé à l’avance.'],
            ['tarifs', 'Y a-t-il des frais de déplacement ?', 'Aucun jusqu’à vingt kilomètres. Au-delà, 0,40 € par kilomètre, toujours annoncé avant le rendez-vous.'],
            ['accessibilite', 'Les locaux sont-ils accessibles aux personnes à mobilité réduite ?', 'Cela dépend du lieu : l’information est indiquée sur chaque atelier. En cas de difficulté, appelez-moi, nous trouverons une solution — y compris un rendez-vous chez vous.'],
            ['accessibilite', 'Travaillez-vous avec les communes ?', 'Oui, avec les mairies, les CCAS, les médiathèques et les associations. La page « Communes et associations » détaille les prestations possibles.'],
        ];

        foreach ($faqs as $position => [$category, $question, $answer]) {
            Faq::updateOrCreate(
                ['question' => $question],
                [
                    'answer' => $answer,
                    'category' => $category,
                    'status' => ContentStatus::Published,
                    'position' => $position,
                    'is_featured' => $position < 4,
                ],
            );
        }
    }

    protected function seedTestimonials(): void
    {
        $testimonials = [
            ['Je n’osais pas toucher à l’ordinateur de peur de tout casser. Maintenant j’envoie des photos à ma fille toutes les semaines.', 'Odette', 'retraitée'],
            ['On m’a expliqué sans me faire sentir bête. C’est la première fois.', 'Robert', '78 ans'],
            ['J’ai enfin réussi ma déclaration d’impôts toute seule. Toute seule !', 'Simone', 'retraitée'],
            ['J’accompagnais ma mère et j’ai appris autant qu’elle. Les explications sont claires.', 'Nathalie', 'aidante familiale'],
        ];

        foreach ($testimonials as $position => [$quote, $name, $context]) {
            Testimonial::updateOrCreate(
                ['quote' => $quote],
                [
                    'author_name' => $name,
                    'author_context' => $context,
                    // Créés en brouillon, sans accord de publication : ces
                    // témoignages sont inventés. Publiés tels quels, ils
                    // constitueraient de faux avis. Ils servent de modèles de
                    // rédaction, à remplacer par de vrais retours.
                    'status' => ContentStatus::Draft,
                    'publication_consent' => false,
                    'is_featured' => false,
                    'position' => $position,
                ],
            );
        }
    }

    protected function seedOfficialLinks(): void
    {
        $links = [
            [OfficialLink::CATEGORY_SECURITY, 'Cybermalveillance.gouv.fr', 'https://www.cybermalveillance.gouv.fr', 'Le site officiel d’assistance aux victimes d’actes de cybermalveillance. Diagnostic gratuit et mise en relation avec des professionnels.'],
            [OfficialLink::CATEGORY_SECURITY, 'Signal Spam', 'https://www.signal-spam.fr', 'Pour signaler un courriel frauduleux reçu dans votre boîte aux lettres.'],
            [OfficialLink::CATEGORY_SECURITY, 'Service 33700 — SMS frauduleux', 'https://www.33700.fr', 'Pour signaler un SMS frauduleux : transférez simplement le message au 33700.'],
            [OfficialLink::CATEGORY_SECURITY, 'Bloctel — liste d’opposition au démarchage', 'https://www.bloctel.gouv.fr', 'Pour ne plus être démarché par téléphone.'],
            [OfficialLink::CATEGORY_PROCEDURES, 'Service-Public.fr', 'https://www.service-public.fr', 'Le site officiel de l’administration française : toutes les démarches expliquées.'],
            [OfficialLink::CATEGORY_PROCEDURES, 'FranceConnect', 'https://franceconnect.gouv.fr', 'Un seul identifiant pour accéder à la plupart des sites de l’administration.'],
            [OfficialLink::CATEGORY_PROCEDURES, 'impots.gouv.fr', 'https://www.impots.gouv.fr', 'Déclaration de revenus, avis d’imposition, paiement.'],
            [OfficialLink::CATEGORY_PROCEDURES, 'ameli.fr', 'https://www.ameli.fr', 'Assurance maladie : remboursements, attestations, carte Vitale.'],
            [OfficialLink::CATEGORY_PROCEDURES, 'info-retraite.fr', 'https://www.info-retraite.fr', 'Votre relevé de carrière et vos droits à la retraite, tous régimes confondus.'],
            [OfficialLink::CATEGORY_PROCEDURES, 'ANTS — cartes et permis', 'https://ants.gouv.fr', 'Carte grise, permis de conduire, carte d’identité, passeport.'],
            [OfficialLink::CATEGORY_SUPPORT, 'France Services', 'https://www.france-services.gouv.fr', 'Des lieux d’accueil pour vous accompagner dans vos démarches administratives, partout en France.'],
            [OfficialLink::CATEGORY_SUPPORT, 'Solidarité Numérique', 'https://www.solidarite-numerique.fr', 'Une aide gratuite par téléphone pour les démarches en ligne.'],
        ];

        foreach ($links as $position => [$category, $label, $url, $description]) {
            OfficialLink::updateOrCreate(
                ['url' => $url],
                [
                    'label' => $label,
                    'description' => $description,
                    'category' => $category,
                    'status' => ContentStatus::Published,
                    'position' => $position,
                ],
            );
        }
    }

    protected function seedAccessibilityReport(): void
    {
        AccessibilityReport::updateOrCreate(
            ['title' => 'Audit interne d’accessibilité'],
            [
                'audited_on' => now()->subMonth(),
                'referential' => 'RGAA 4.1',
                'compliance_rate' => 82.50,
                'level' => AccessibilityReport::LEVEL_PARTIAL,
                'summary' => 'Un audit interne a été réalisé sur les principales pages du site : accueil, services, ateliers, prise de rendez-vous, contact et pages légales. Les contrastes, la navigation au clavier et la structure des titres ont été vérifiés manuellement.',
                'non_conformities' => [
                    'Certaines images ajoutées par l’administration peuvent ne pas avoir de texte alternatif : un champ dédié existe, mais il n’est pas encore obligatoire.',
                    'Les documents PDF téléversés ne sont pas systématiquement balisés pour les lecteurs d’écran.',
                    'Aucun audit externe n’a encore été mené par un organisme tiers.',
                ],
                'improvement_plan' => 'Rendre le texte alternatif obligatoire lors du téléversement d’une image, produire les documents importants en version balisée, et faire réaliser un audit externe.',
                'auditor' => 'Audit interne',
                'is_published' => true,
            ],
        );
    }
}
