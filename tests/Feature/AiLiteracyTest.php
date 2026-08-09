<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SkillLevel;
use App\Enums\WorkshopStatus;
use App\Models\Faq;
use App\Models\Page;
use App\Models\PracticalGuide;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Workshop;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Initiation à l'intelligence artificielle.
 *
 * Le volet protecteur doit rester accessible à tous, y compris aux personnes
 * qui n'utiliseront jamais ces outils : ce sont elles qui subissent les
 * arnaques. Le volet pratique, lui, suppose des bases.
 */
class AiLiteracyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function la_famille_de_services_est_publiee(): void
    {
        $category = ServiceCategory::query()
            ->where('slug', 'intelligence-artificielle')
            ->firstOrFail();

        $this->assertTrue($category->status->isPublic());

        $this->get(route('services.show', $category))
            ->assertOk()
            ->assertSee('Intelligence artificielle');
    }

    #[Test]
    public function le_volet_protecteur_est_ouvert_a_tous(): void
    {
        $service = Service::query()
            ->where('slug', 'comprendre-intelligence-artificielle')
            ->firstOrFail();

        // Une personne qui n'a jamais touché un ordinateur doit pouvoir venir.
        $this->assertSame(SkillLevel::Everyone, $service->level);
        $this->assertTrue($service->is_featured);
    }

    #[Test]
    public function le_volet_pratique_annonce_son_niveau(): void
    {
        $service = Service::query()
            ->where('slug', 'utiliser-intelligence-artificielle')
            ->firstOrFail();

        $this->assertSame(SkillLevel::Intermediate, $service->level);
        $this->assertStringContainsString('à l’aise avec un navigateur', $service->description);
    }

    #[Test]
    public function la_fiche_pratique_couvre_le_clonage_de_voix(): void
    {
        $guide = PracticalGuide::query()
            ->where('slug', 'reconnaitre-un-faux-message-ou-une-fausse-voix')
            ->with('steps')
            ->firstOrFail();

        $response = $this->get(route('resources.guide', $guide))->assertOk();

        // Les deux messages qui comptent vraiment : la voix ne prouve rien,
        // et le mot de passe familial est la parade.
        $response->assertSee('la voix ne prouve rien', escape: false);
        $response->assertSee('mot de passe familial', escape: false);

        $this->assertGreaterThanOrEqual(5, $guide->steps->count());
    }

    #[Test]
    public function la_fiche_pratique_porte_un_encart_de_securite(): void
    {
        $guide = PracticalGuide::query()
            ->where('slug', 'reconnaitre-un-faux-message-ou-une-fausse-voix')
            ->firstOrFail();

        $this->assertNotEmpty($guide->safety_notice);

        $this->get(route('resources.guide', $guide))
            ->assertOk()
            ->assertSee('raccrocher et de le rappeler', escape: false);
    }

    #[Test]
    public function la_fiche_pratique_est_imprimable(): void
    {
        $guide = PracticalGuide::query()
            ->where('slug', 'reconnaitre-un-faux-message-ou-une-fausse-voix')
            ->firstOrFail();

        $this->get(route('resources.guide.print', $guide))->assertOk();
    }

    #[Test]
    public function la_page_securite_signale_ce_qui_a_change(): void
    {
        $page = Page::findByKey(Page::KEY_SECURITY);

        $this->assertNotNull($page);
        $this->assertStringContainsString('Ce qui a changé récemment', $page->body);
        $this->assertStringContainsString('faux proche', $page->body);
    }

    #[Test]
    public function les_questions_frequentes_repondent_sur_la_fausse_voix(): void
    {
        $faq = Faq::query()
            ->where('question', 'like', '%voix de mon petit-fils%')
            ->firstOrFail();

        $this->assertTrue($faq->status->isPublic());
        $this->assertStringContainsString('mot de passe', $faq->answer);
    }

    #[Test]
    public function les_questions_frequentes_ecartent_l_usage_pour_les_droits(): void
    {
        $faq = Faq::query()
            ->where('question', 'like', '%droit à une aide%')
            ->firstOrFail();

        // La règle doit être sans ambiguïté : ces outils inventent, et une
        // personne renonçant à une aide sur une réponse fausse, c'est un
        // dégât réel.
        $this->assertStringStartsWith('Non', $faq->answer);
        $this->assertStringContainsString('France Services', $faq->answer);
    }

    #[Test]
    public function l_atelier_modele_reste_en_brouillon(): void
    {
        $workshop = Workshop::query()
            ->where('slug', 'comprendre-intelligence-artificielle-atelier')
            ->firstOrFail();

        // Sa date est un simple remplissage : publié tel quel, il ferait se
        // déplacer quelqu'un pour rien.
        $this->assertSame(WorkshopStatus::Draft, $workshop->status);
        $this->assertFalse($workshop->status->isPublic());

        $this->get(route('workshops.show', $workshop))->assertNotFound();
    }

    #[Test]
    public function l_atelier_modele_n_apparait_pas_dans_l_agenda(): void
    {
        $this->get(route('workshops.index'))
            ->assertOk()
            ->assertDontSee('MODÈLE D’ATELIER', escape: false);
    }
}
