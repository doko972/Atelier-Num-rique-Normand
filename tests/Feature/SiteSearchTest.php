<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\MissedSearch;
use App\Models\SearchSynonym;
use App\Services\SiteSearch;
use App\Support\SearchText;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Recherche du site public.
 *
 * Les visiteurs écrivent comme ils parlent, sans accents, avec des mots du
 * quotidien et quelques fautes. Une recherche qui ne répond qu'au mot exact
 * leur ferait croire que le site ne peut rien pour eux.
 */
class SiteSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * @return array<int, string>
     */
    protected function titlesFor(string $query): array
    {
        return app(SiteSearch::class)->search($query)->pluck('title')->all();
    }

    #[Test]
    public function la_mise_en_forme_ignore_accents_majuscules_et_pluriel(): void
    {
        $this->assertSame('impot photo', SearchText::normalize('Impôts PHOTOS'));
        $this->assertSame(['ordi', 'lent'], SearchText::keywords('Mon ordi est lent ?'));
    }

    #[Test]
    public function une_demande_faite_de_mots_courants_reste_cherchee(): void
    {
        $this->assertSame(['aide'], SearchText::keywords('aide'));
    }

    #[Test]
    public function un_synonyme_du_quotidien_trouve_le_vocabulaire_du_site(): void
    {
        // « mail » n'apparaît pas dans les titres : « adresse électronique » oui.
        $this->assertContains('Créer et utiliser une adresse électronique', $this->titlesFor('envoyer un mail'));
    }

    #[Test]
    public function le_debut_d_un_mot_suffit(): void
    {
        $this->assertContains('Reconnaître les arnaques', $this->titlesFor('arnaq'));
    }

    #[Test]
    public function une_faute_de_frappe_est_toleree(): void
    {
        $this->assertContains('Faire un appel vidéo', $this->titlesFor('apel vidéo'));
    }

    #[Test]
    public function les_questions_d_argent_menent_aux_tarifs(): void
    {
        $this->assertSame('Tarifs', $this->titlesFor('combien ça coûte')[0] ?? null);
    }

    #[Test]
    public function un_synonyme_ajoute_depuis_le_back_office_sert_aussitot(): void
    {
        $this->assertNotContains('Faire un appel vidéo', $this->titlesFor('mamie en direct'));

        SearchSynonym::create(['label' => 'appel vidéo', 'words' => ['mamie en direct']]);

        $this->assertContains('Faire un appel vidéo', $this->titlesFor('mamie en direct'));
    }

    #[Test]
    public function la_page_de_recherche_affiche_les_resultats(): void
    {
        $this->get(route('search', ['q' => 'arnaque sms']))
            ->assertOk()
            ->assertSee('Reconnaître les arnaques', escape: false)
            ->assertSee('noindex', escape: false);
    }

    #[Test]
    public function la_page_vide_propose_des_exemples(): void
    {
        $this->get(route('search'))
            ->assertOk()
            ->assertSee('Exemples de recherches', escape: false);
    }

    #[Test]
    public function une_recherche_sans_resultat_oriente_vers_le_telephone_et_est_relevee(): void
    {
        $this->get(route('search', ['q' => 'Zorglub']))
            ->assertOk()
            ->assertSee('Je n’ai rien trouvé', escape: false);

        $this->get(route('search', ['q' => 'zorglub']));

        $this->assertSame(2, MissedSearch::query()->where('query', 'zorglub')->value('hits'));
    }

    #[Test]
    public function une_recherche_contenant_une_coordonnee_n_est_pas_relevee(): void
    {
        $this->get(route('search', ['q' => 'odette 0612345678']));
        $this->get(route('search', ['q' => 'odette@example.test']));

        $this->assertSame(0, MissedSearch::query()->count());
    }

    #[Test]
    public function les_recherches_anciennes_sont_purgees(): void
    {
        MissedSearch::query()->create(['query' => 'ancienne', 'hits' => 1, 'last_searched_at' => now()->subMonths(13)]);
        MissedSearch::query()->create(['query' => 'recente', 'hits' => 1, 'last_searched_at' => now()->subMonth()]);

        $this->artisan('model:prune', ['--model' => [MissedSearch::class]]);

        $this->assertSame(['recente'], MissedSearch::query()->pluck('query')->all());
    }

    #[Test]
    public function l_en_tete_mene_a_la_recherche(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('search'), escape: false);
    }

    #[Test]
    public function un_editeur_consulte_les_recherches_sans_reponse_et_les_synonymes(): void
    {
        MissedSearch::record('zorglub');
        $editor = $this->userWithRole(UserRole::Editor);

        $this->actingAs($editor)
            ->get(route('admin.missed-searches.index'))
            ->assertOk()
            ->assertSee('zorglub');

        $this->actingAs($editor)
            ->get(route('admin.search-synonyms.create', ['mot' => 'zorglub']))
            ->assertOk()
            ->assertSee('zorglub');

        $this->actingAs($editor)
            ->get(route('admin.search-synonyms.index'))
            ->assertOk()
            ->assertSee('courriel');
    }

    #[Test]
    public function un_conseiller_n_accede_pas_aux_reglages_de_recherche(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->get(route('admin.missed-searches.index'))
            ->assertForbidden();
    }
}
