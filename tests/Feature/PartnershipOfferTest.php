<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PartnerType;
use App\Http\Requests\Site\StorePartnershipRequest;
use App\Models\PartnershipRequest;
use App\Models\SiteSetting;
use App\Services\SettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Offre destinée aux collectivités et aux entreprises (codex §15).
 */
class PartnershipOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function la_page_distingue_les_deux_publics(): void
    {
        $response = $this->get(route('partnership.create'))->assertOk();

        $response->assertSee(__('site.partnership.public_title'), escape: false);
        $response->assertSee(__('site.partnership.business_title'), escape: false);

        // Chaque intervention annonce sa durée : c'est la première question
        // que pose un interlocuteur qui doit budgéter.
        $response->assertSee('1 h 30 à 2 heures', escape: false);
    }

    #[Test]
    public function l_offre_de_sensibilisation_a_l_ia_est_proposee(): void
    {
        $this->get(route('partnership.create'))
            ->assertOk()
            ->assertSee('Arnaques par intelligence artificielle', escape: false);

        $this->assertArrayHasKey('ai_awareness', StorePartnershipRequest::needOptions());
        $this->assertArrayHasKey('ai_discovery', StorePartnershipRequest::needOptions());
    }

    #[Test]
    public function les_prestations_ne_se_presentent_pas_comme_de_la_formation(): void
    {
        // « Action de formation professionnelle » est une catégorie encadrée,
        // qui engage des obligations déclaratives. Les prestations sont donc
        // décrites comme des sensibilisations, des ateliers et des
        // découvertes — ce qu'elles sont réellement.
        //
        // Le contrôle porte sur les libellés d'offre, et non sur la page
        // entière : « Organisme de formation » y figure légitimement, comme
        // type de structure cliente possible.
        $offers = collect(['public_offers' => ['permanence', 'workshops', 'scam_prevention', 'staff']])
            ->merge(['business_offers' => ['ai_awareness', 'cybersecurity', 'ai_discovery', 'diagnosis']])
            ->flatMap(fn (array $keys, string $group): array => array_map(
                fn (string $key): string => __("site.partnership.{$group}.{$key}.title")
                    .' '.__("site.partnership.{$group}.{$key}.text"),
                $keys,
            ))
            ->implode(' ');

        $this->assertStringNotContainsString('formation', mb_strtolower($offers));

        // Et les besoins proposés au formulaire suivent la même règle.
        $needs = mb_strtolower(implode(' ', StorePartnershipRequest::needOptions()));

        $this->assertStringNotContainsString('formation', $needs);
        $this->assertStringContainsString('sensibilisation', $needs);
    }

    #[Test]
    public function la_presentation_imprimable_est_accessible(): void
    {
        $response = $this->get(route('partnership.brochure'))->assertOk();

        // Elle doit se suffire à elle-même une fois posée sur le bureau d'un
        // élu : prestations, secteur d'intervention et coordonnées.
        $response->assertSee(__('site.partnership.public_title'), escape: false);
        $response->assertSee(__('site.partnership.business_title'), escape: false);
        $response->assertSee('Me contacter');
        $response->assertSee('Secteur d’intervention', escape: false);
    }

    #[Test]
    public function la_presentation_reprend_les_mentions_d_identification_renseignees(): void
    {
        SiteSetting::query()->where('key', 'siret')->update(['value' => '000 000 000 00000']);
        SiteSetting::query()->where('key', 'legal_status')->update(['value' => 'Micro-entrepreneur']);
        Cache::flush();

        $this->get(route('partnership.brochure'))
            ->assertOk()
            ->assertSee('000 000 000 00000')
            ->assertSee('Micro-entrepreneur');
    }

    #[Test]
    public function aucune_mention_vide_n_est_imprimee(): void
    {
        // Un document professionnel comportant « SIRET : » suivi de rien
        // ferait mauvaise impression : les lignes non renseignées doivent
        // simplement disparaître.
        SiteSetting::query()->where('key', 'siret')->update(['value' => '']);
        SiteSetting::query()->where('key', 'legal_status')->update(['value' => '']);
        Cache::flush();

        $this->get(route('partnership.brochure'))
            ->assertOk()
            ->assertDontSee('SIRET')
            ->assertDontSee('Statut :');
    }

    #[Test]
    public function la_presentation_imprimable_reprend_les_coordonnees_reelles(): void
    {
        $settings = app(SettingsService::class);

        $this->get(route('partnership.brochure'))
            ->assertOk()
            ->assertSee($settings->string('site_name'), escape: false)
            ->assertSee($settings->phoneDisplay());
    }

    #[Test]
    public function la_page_renvoie_vers_la_presentation_imprimable(): void
    {
        $this->get(route('partnership.create'))
            ->assertOk()
            ->assertSee(route('partnership.brochure'), escape: false);
    }

    #[Test]
    public function une_entreprise_peut_demander_une_sensibilisation_a_l_ia(): void
    {
        $this->post(route('partnership.store'), [
            ...$this->antiSpamFields(),
            'organisation_name' => 'Entreprise de test',
            'organisation_type' => PartnerType::Company->value,
            'contact_name' => 'Ambroise Testeur',
            'contact_role' => 'Responsable administratif',
            'email' => 'contact@entreprise.test',
            'needs' => ['ai_awareness', 'cybersecurity'],
            'audience' => 'Équipe comptable',
            'estimated_participants' => 12,
            'quote_requested' => '1',
            'consent' => '1',
        ])->assertRedirect(route('partnership.create'));

        $request = PartnershipRequest::query()->firstOrFail();

        $this->assertSame(PartnerType::Company, $request->organisation_type);
        $this->assertSame(['ai_awareness', 'cybersecurity'], $request->needs);
        $this->assertTrue($request->quote_requested);
    }

    #[Test]
    public function la_presentation_figure_dans_le_plan_du_site_ou_reste_atteignable(): void
    {
        // La page de demande doit être indexée ; la présentation imprimable
        // n'a pas à l'être, mais elle doit rester accessible par son lien.
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('partnership.create'), escape: false);

        $this->get(route('partnership.brochure'))->assertOk();
    }
}
