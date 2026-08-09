<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Documents à imprimer.
 *
 * Deux supports, deux publics : la plaquette A5 se dépose en mairie ou en
 * salle d'attente et s'adresse aux habitants ; la présentation s'adresse aux
 * élus et aux entreprises. Tous deux puisent dans les paramètres du site,
 * afin de ne jamais afficher des coordonnées périmées.
 */
class PrintableDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        SiteSetting::query()->where('key', 'phone')->update(['value' => '0612345678']);
        SiteSetting::query()->where('key', 'phone_display')->update(['value' => '06 12 34 56 78']);

        Cache::flush();
    }

    #[Test]
    public function la_plaquette_est_accessible(): void
    {
        $this->get(route('leaflet'))
            ->assertOk()
            ->assertSee('leaflet__phone-number', escape: false);
    }

    #[Test]
    public function le_numero_est_l_element_principal_de_la_plaquette(): void
    {
        $response = $this->get(route('leaflet'))->assertOk();

        // C'est la seule action attendue d'un lecteur : elle doit être
        // impossible à manquer, et cliquable si la plaquette est lue à l'écran.
        $response->assertSee('06 12 34 56 78');
        $response->assertSee('tel:0612345678', escape: false);
        $response->assertSee('Appelez-moi, c’est le plus simple', escape: false);
    }

    #[Test]
    public function la_plaquette_annonce_le_secteur_d_intervention(): void
    {
        $this->get(route('leaflet'))
            ->assertOk()
            ->assertSee('Je me déplace à', escape: false)
            ->assertSee('Condé-en-Normandie', escape: false);
    }

    #[Test]
    public function la_plaquette_porte_les_mentions_d_identification(): void
    {
        SiteSetting::query()->where('key', 'siret')->update(['value' => '000 000 000 00000']);
        Cache::flush();

        $this->get(route('leaflet'))
            ->assertOk()
            ->assertSee('SIRET 000 000 000 00000');
    }

    #[Test]
    public function la_plaquette_n_est_pas_indexee(): void
    {
        // Ce sont des outils de travail, pas des pages de destination.
        $this->get(route('leaflet'))
            ->assertOk()
            ->assertSee('noindex', escape: false);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee(route('leaflet'), escape: false);
    }

    #[Test]
    public function la_plaquette_explique_comment_l_imprimer(): void
    {
        // Deux plaquettes par feuille A4 : sans cette consigne, on obtient
        // une A5 centrée sur une page entière.
        $this->get(route('leaflet'))
            ->assertOk()
            ->assertSee('2 pages par feuille', escape: false);
    }

    #[Test]
    public function le_site_reste_coherent_sans_numero_de_telephone(): void
    {
        SiteSetting::query()->where('key', 'phone')->update(['value' => '']);
        SiteSetting::query()->where('key', 'phone_display')->update(['value' => '']);
        Cache::flush();

        // Aucun cadre téléphonique vide ne doit être imprimé.
        $this->get(route('leaflet'))
            ->assertOk()
            ->assertDontSee('leaflet__phone-number', escape: false);
    }

    #[Test]
    public function les_deux_documents_sont_atteignables_depuis_le_tableau_de_bord(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->get(route('admin.dashboard'))
            ->assertOk();

        $response->assertSee(route('leaflet'), escape: false);
        $response->assertSee(route('partnership.brochure'), escape: false);
        $response->assertSee(__('admin.dashboard.documents_title'));
    }
}
