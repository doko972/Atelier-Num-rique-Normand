<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\SkillLevel;
use App\Enums\WorkshopStatus;
use App\Models\ArticleCategory;
use App\Models\Faq;
use App\Models\PracticalGuide;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Workshop;
use App\Models\WorkshopCategory;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Initiation à l’intelligence artificielle.
 *
 * Le sujet est traité en deux volets distincts, et l’ordre compte.
 *
 * Le premier est protecteur : reconnaître ce qu’une machine a fabriqué. Il
 * s’adresse à tout le monde, y compris aux personnes qui n’utiliseront jamais
 * ces outils — car elles en subissent déjà les effets. Les courriels
 * frauduleux n’ont plus de fautes, et une voix au téléphone ne prouve plus
 * l’identité de son interlocuteur.
 *
 * Le second est pratique, et suppose d’être déjà à l’aise avec un navigateur.
 *
 * L’atelier est créé en brouillon : sa date et son lieu se renseignent depuis
 * le back-office, une fois la salle réservée.
 */
class AiLiteracySeeder extends Seeder
{
    public function run(): void
    {
        $category = $this->seedServiceCategory();

        $this->seedServices($category);
        $this->seedGuide();
        $this->seedFaqs();
        $this->seedWorkshopTemplate();
    }

    protected function seedServiceCategory(): ServiceCategory
    {
        return ServiceCategory::updateOrCreate(
            ['slug' => 'intelligence-artificielle'],
            [
                'name' => 'Intelligence artificielle',
                'summary' => 'Comprendre ce que ces outils savent faire, et surtout ce qu’ils ne savent pas.',
                'description' => "L’intelligence artificielle n’est plus un sujet lointain : elle a déjà changé la façon dont les escrocs s’y prennent, et elle apparaît dans les résultats de recherche sans qu’on l’ait demandé.\n\nJe propose deux accompagnements distincts. Le premier apprend à repérer ce qui est fabriqué par une machine — il ne demande aucune connaissance préalable. Le second montre comment se servir de ces outils utilement, pour qui est déjà à l’aise avec un ordinateur.",
                'icon' => 'intelligence',
                'status' => ContentStatus::Published,
                // Placée juste après la sécurité numérique, dont elle prolonge
                // directement le propos.
                'position' => 65,
                'meta_title' => 'Comprendre l’intelligence artificielle — accompagnement près de Condé-en-Normandie',
                'meta_description' => 'Reconnaître un faux message, une fausse voix, une image fabriquée. Et apprendre à utiliser ces outils sans leur faire dire n’importe quoi.',
            ],
        );
    }

    protected function seedServices(ServiceCategory $category): void
    {
        $services = [
            [
                'slug' => 'comprendre-intelligence-artificielle',
                'title' => 'Comprendre l’intelligence artificielle',
                'summary' => 'Reconnaître un message, une image ou une voix fabriqués par une machine.',
                'level' => SkillLevel::Everyone,
                'duration' => 60,
                'featured' => true,
                'position' => 0,
                'description' => "Vous n’avez pas besoin de vous servir de ces outils pour être concerné : ce sont les escrocs qui s’en servent contre vous.\n\nCe qui vous protégeait hier ne suffit plus. Un courriel frauduleux ne comporte plus de fautes d’orthographe. Un faux site administratif est devenu impeccable. Et au téléphone, une voix familière ne prouve plus rien.\n\nNous regardons ensemble de vrais exemples. Vous repartez avec des réflexes simples, et une fiche écrite à garder près du téléphone.",
                'learning_points' => [
                    'Comprendre pourquoi l’orthographe n’est plus un indice fiable',
                    'Savoir qu’une voix au téléphone peut être imitée, et comment s’en prémunir',
                    'Convenir d’un mot de passe familial avec vos proches',
                    'Reconnaître une photo ou une vidéo fabriquée',
                    'Savoir ce que devient ce que vous écrivez dans ces outils',
                ],
            ],
            [
                'slug' => 'utiliser-intelligence-artificielle',
                'title' => 'Utiliser l’intelligence artificielle au quotidien',
                'summary' => 'Se faire aider pour écrire un courrier ou comprendre un document compliqué.',
                'level' => SkillLevel::Intermediate,
                'duration' => 90,
                'featured' => false,
                'position' => 1,
                'description' => "Ces outils peuvent rendre de vrais services : reformuler un courrier, expliquer un document administratif en termes simples, traduire une notice.\n\nNous voyons comment poser une question claire, et surtout comment vérifier la réponse. Car ces machines répondent toujours, avec assurance, y compris quand elles se trompent.\n\nCet accompagnement suppose que vous soyez déjà à l’aise avec un navigateur et un clavier. Si ce n’est pas encore le cas, nous commencerons par là : il n’y a aucune urgence.",
                'learning_points' => [
                    'Poser une question claire et obtenir une réponse utile',
                    'Faire reformuler un courrier administratif en français simple',
                    'Vérifier une réponse avant de s’y fier',
                    'Savoir quelles informations ne jamais y écrire',
                ],
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['slug' => $service['slug']],
                [
                    'service_category_id' => $category->id,
                    'title' => $service['title'],
                    'summary' => $service['summary'],
                    'description' => $service['description'],
                    'learning_points' => $service['learning_points'],
                    'icon' => 'intelligence',
                    'level' => $service['level'],
                    'estimated_duration_minutes' => $service['duration'],
                    'status' => ContentStatus::Published,
                    'is_featured' => $service['featured'],
                    'position' => $service['position'],
                ],
            );
        }
    }

    protected function seedGuide(): void
    {
        $category = ArticleCategory::query()->where('slug', 'securite')->first();

        $guide = PracticalGuide::updateOrCreate(
            ['slug' => 'reconnaitre-un-faux-message-ou-une-fausse-voix'],
            [
                'article_category_id' => $category?->id,
                'title' => 'Reconnaître un faux message ou une fausse voix',
                'summary' => 'Les escrocs se servent désormais de l’intelligence artificielle. Voici ce qui a changé, et les réflexes qui protègent encore.',
                'introduction' => "Pendant longtemps, on reconnaissait une arnaque à ses fautes d’orthographe et à son français bancal. Ce repère ne fonctionne plus : les messages frauduleux sont maintenant écrits par des machines, dans un français impeccable.\n\nLa bonne nouvelle, c’est que les réflexes qui protègent vraiment n’ont jamais été techniques. Les voici.",
                'level' => SkillLevel::Everyone,
                'estimated_minutes' => 15,
                'safety_notice' => 'Aucun proche réellement en difficulté ne vous empêchera de raccrocher et de le rappeler vous-même. Celui qui insiste pour rester en ligne n’est pas votre proche.',
                'conclusion' => "Retenez une seule chose : ne jamais agir dans l’urgence sur la foi d’un message ou d’un appel. Raccrochez, respirez, rappelez au numéro que vous connaissez.\n\nEt si un doute subsiste, appelez-moi. Je préfère cent fois répondre à une fausse alerte que réparer les dégâts d’une vraie.",
                'status' => ContentStatus::Published,
                'is_featured' => true,
                'published_at' => now(),
                'reviewed_on' => now()->toDateString(),
                'meta_title' => 'Reconnaître un faux message ou une fausse voix',
                'meta_description' => 'Les arnaques par intelligence artificielle expliquées simplement : faux courriels sans fautes, clonage de voix, fausses images. Et comment s’en protéger.',
            ],
        );

        $steps = [
            [
                'title' => 'Oubliez l’orthographe : elle ne prouve plus rien',
                'body' => "Un message frauduleux peut aujourd’hui être parfaitement écrit. Le style soigné n’est plus un gage de sérieux, et les fautes ne sont plus le signal d’alarme qu’elles étaient.\n\nCe qui compte désormais, ce n’est plus comment le message est écrit, mais ce qu’il vous demande de faire.",
                'tip' => 'Un message qui vous presse d’agir tout de suite est suspect, quelle que soit la qualité de sa rédaction.',
            ],
            [
                'title' => 'Regardez l’adresse de l’expéditeur, pas le nom affiché',
                'body' => "Le nom qui s’affiche est choisi par celui qui envoie le message : il peut écrire ce qu’il veut.\n\nCliquez ou appuyez sur ce nom pour faire apparaître l’adresse réelle. Un message de « Assurance Maladie » envoyé depuis une adresse inconnue est un faux, même s’il est très bien fait.",
            ],
            [
                'title' => 'Au téléphone, la voix ne prouve rien non plus',
                'body' => "C’est le point le plus important, et le moins connu.\n\nQuelques secondes de voix suffisent aujourd’hui à en fabriquer une imitation convaincante. Il existe des appels où l’on entend la voix d’un enfant ou d’un petit-enfant, en larmes, réclamant de l’argent immédiatement pour un accident ou une garde à vue.\n\nCe n’est pas lui. La détresse dans la voix fait partie du procédé : elle est là pour vous empêcher de réfléchir.",
                'tip' => 'Si l’on vous presse, si l’on pleure, si l’on vous supplie de ne prévenir personne : c’est précisément le moment de raccrocher.',
            ],
            [
                'title' => 'Convenez d’un mot de passe familial',
                'body' => "Voici la parade la plus efficace, et elle ne demande aucune technique.\n\nChoisissez avec vos enfants et petits-enfants un mot que vous seuls connaissez. N’importe lequel : le nom d’un ancien chien, un plat de famille, un lieu de vacances.\n\nSi quelqu’un vous appelle en urgence pour de l’argent, demandez le mot. Une machine ne le connaît pas. Un escroc non plus.",
                'tip' => 'Ne notez ce mot nulle part sur internet, et ne l’envoyez ni par message ni par courriel.',
            ],
            [
                'title' => 'Rappelez vous-même, au numéro que vous connaissez',
                'body' => "Ne rappelez jamais le numéro qui vient de vous appeler, ni celui indiqué dans un message.\n\nRaccrochez. Cherchez le numéro dans votre répertoire, sur un ancien courrier, au dos de votre carte bancaire. Puis appelez.\n\nCette précaution suffit à faire échouer la quasi-totalité de ces arnaques.",
            ],
            [
                'title' => 'Les images et les vidéos se fabriquent aussi',
                'body' => "Une photo n’est plus une preuve. Une vidéo non plus.\n\nOn voit circuler des vidéos de personnes connues recommandant des placements financiers : elles n’ont jamais rien dit de tel. Leur visage et leur voix ont été fabriqués.\n\nRègle simple : aucune proposition de placement, de gain ou de remboursement vue sur un écran ne mérite votre confiance, quelle que soit la personne qui semble la porter.",
                'tip' => 'Regardez les mains, les dents, les reflets dans les lunettes : ce sont les détails que ces outils ratent encore souvent.',
            ],
        ];

        if ($guide->steps()->exists()) {
            return;
        }

        foreach ($steps as $position => $step) {
            $guide->steps()->create([
                'position' => $position + 1,
                'title' => $step['title'],
                'body' => $step['body'],
                'tip' => $step['tip'] ?? null,
            ]);
        }
    }

    protected function seedFaqs(): void
    {
        $faqs = [
            [
                'securite',
                'On m’a appelé avec la voix de mon petit-fils, est-ce possible ?',
                'Oui, malheureusement. Quelques secondes de voix suffisent à en fabriquer une imitation. C’est une arnaque courante, et elle vise en priorité les grands-parents. La parade : convenez en famille d’un mot de passe que vous seuls connaissez, et demandez-le. Et raccrochez toujours pour rappeler vous-même au numéro que vous avez dans votre répertoire.',
            ],
            [
                'securite',
                'Comment savoir si un message a été écrit par une machine ?',
                'Souvent, on ne peut pas — et ce n’est plus la bonne question. Les faux messages n’ont plus de fautes d’orthographe. Regardez plutôt ce qu’on vous demande : un message qui vous presse, qui réclame un mot de passe, un paiement ou un code reçu par SMS est frauduleux, qu’il soit bien écrit ou non.',
            ],
            [
                'securite',
                'Puis-je demander à l’intelligence artificielle si j’ai droit à une aide ?',
                'Non, et c’est important. Ces outils répondent toujours, avec beaucoup d’assurance, y compris quand ils inventent. Sur un montant, un droit, une démarche ou une question de santé, la réponse peut être fausse sans que rien ne le signale. Pour ces questions, adressez-vous à l’organisme concerné, à France Services, ou appelez-moi.',
            ],
            [
                'general',
                'Faut-il que j’apprenne à me servir de l’intelligence artificielle ?',
                'Rien ne vous y oblige. En revanche, savoir la reconnaître vous protège, et cela n’exige aucune connaissance technique : c’est l’objet de l’atelier « Comprendre l’intelligence artificielle », ouvert à tous. Apprendre à s’en servir est une autre étape, plus tard, et seulement si vous en voyez l’utilité.',
            ],
            [
                'securite',
                'Ce que je tape dans ces outils, où est-ce que ça va ?',
                'Sur les serveurs de l’entreprise qui les fournit, et cela peut servir à entraîner ses programmes. N’y écrivez donc jamais votre numéro de sécurité sociale, vos coordonnées bancaires, un mot de passe, ni le contenu d’un document médical. Nous verrons ensemble ce qu’il est raisonnable d’y mettre.',
            ],
        ];

        $lastPosition = (int) Faq::query()->max('position');

        foreach ($faqs as $index => [$category, $question, $answer]) {
            Faq::updateOrCreate(
                ['question' => $question],
                [
                    'answer' => $answer,
                    'category' => $category,
                    'status' => ContentStatus::Published,
                    'position' => $lastPosition + $index + 1,
                ],
            );
        }
    }

    /**
     * Modèle d’atelier, en brouillon.
     *
     * La date sert uniquement à satisfaire la contrainte de la table : elle
     * est à remplacer, comme le lieu, au moment de la programmation. Tant que
     * le statut reste « brouillon », rien n’apparaît sur le site.
     */
    protected function seedWorkshopTemplate(): void
    {
        $category = WorkshopCategory::updateOrCreate(
            ['slug' => 'intelligence-artificielle'],
            [
                'name' => 'Intelligence artificielle',
                'summary' => 'Reconnaître ce qui est fabriqué par une machine, et s’en protéger.',
                'icon' => 'intelligence',
                'status' => ContentStatus::Published,
                'position' => 5,
            ],
        );

        Workshop::updateOrCreate(
            ['slug' => 'comprendre-intelligence-artificielle-atelier'],
            [
                'workshop_category_id' => $category->id,
                'title' => 'Comprendre l’intelligence artificielle et les nouvelles arnaques',
                'description' => "MODÈLE D’ATELIER — renseignez la date, l’horaire et le lieu, puis passez le statut en « Publié ».\n\nCe que les escrocs font désormais avec l’intelligence artificielle, et comment s’en protéger sans rien y connaître.\n\nNous regardons ensemble de vrais faux messages, nous écoutons ce que donne une voix imitée, et nous mettons en place des réflexes simples : le mot de passe familial, le réflexe de rappeler soi-même, les questions à ne jamais poser à une machine.\n\nAucune connaissance préalable n’est nécessaire. Vous n’avez pas besoin d’utiliser ces outils pour être concerné.\n\nVous repartez avec une fiche écrite à garder près du téléphone.",
                'objectives' => [
                    'Comprendre pourquoi l’orthographe ne protège plus',
                    'Savoir qu’une voix au téléphone peut être imitée',
                    'Repartir avec un mot de passe familial convenu',
                    'Connaître les questions à ne jamais poser à une machine',
                ],
                'prerequisites' => 'Aucun. Cet atelier s’adresse aussi bien aux personnes qui n’utilisent pas d’ordinateur.',
                'level' => SkillLevel::Everyone,
                // Date de remplacement : la colonne est obligatoire, mais le
                // statut « brouillon » garde l’atelier hors du site.
                'date' => CarbonImmutable::today()->addMonth(),
                'start_time' => '14:00',
                'end_time' => '16:00',
                'capacity' => 10,
                'waiting_list_enabled' => true,
                'is_accessible' => false,
                'equipment_provided' => true,
                'own_device_allowed' => true,
                'is_free' => true,
                'status' => WorkshopStatus::Draft,
            ],
        );
    }
}
