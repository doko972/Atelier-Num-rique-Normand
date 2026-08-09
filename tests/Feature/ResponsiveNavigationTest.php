<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SiteSetting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Navigation mobile (codex §6 et §31).
 *
 * Le tiroir repose sur l'amélioration progressive : le serveur rend une
 * navigation complète et dépliée, et c'est le script qui la transforme en
 * panneau latéral. Ces tests vérifient que le rendu serveur reste utilisable
 * sans JavaScript, condition sans laquelle un visiteur pourrait se retrouver
 * devant un menu injoignable.
 */
class ResponsiveNavigationTest extends TestCase
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
    public function le_tiroir_est_rendu_deplie_par_defaut(): void
    {
        $response = $this->get('/');

        // Sans `data-enhanced`, la feuille de style laisse la navigation en
        // flux normal : tous les liens restent visibles et cliquables.
        $response->assertSee('data-drawer', escape: false);
        $response->assertSee('data-open="false"', escape: false);
        $response->assertDontSee('<div class="drawer" id="menu-principal" data-drawer hidden', escape: false);
        $response->assertDontSee('data-enhanced', escape: false);
    }

    #[Test]
    public function tous_les_liens_du_menu_sont_presents_dans_le_rendu_serveur(): void
    {
        $response = $this->get('/');

        foreach ([
            route('services.index'),
            route('workshops.index'),
            route('procedures'),
            route('security'),
            route('resources.index'),
            route('pricing'),
            route('partnership.create'),
            route('about'),
            route('contact.create'),
            route('appointments.create'),
        ] as $url) {
            $response->assertSee('href="'.$url.'"', escape: false);
        }
    }

    #[Test]
    public function le_bouton_de_menu_est_correctement_relie_au_tiroir(): void
    {
        $response = $this->get('/');

        $response->assertSee('aria-controls="menu-principal"', escape: false);
        $response->assertSee('id="menu-principal"', escape: false);
        $response->assertSee('aria-expanded="false"', escape: false);

        // Les deux libellés sont fournis au script : il n'a pas à fabriquer
        // de texte, qui échapperait alors aux fichiers de langue.
        $response->assertSee('data-label-open="Menu"', escape: false);
        $response->assertSee('data-label-close="Fermer"', escape: false);
    }

    #[Test]
    public function le_tiroir_offre_un_bouton_de_fermeture_libelle(): void
    {
        $response = $this->get('/');

        $response->assertSee('data-drawer-close', escape: false);
        // Une croix seule n'est ni comprise de tous, ni facile à viser.
        $response->assertSee('Fermer');
    }

    #[Test]
    public function le_numero_de_telephone_est_visible_en_permanence_sur_mobile(): void
    {
        $response = $this->get('/');

        // La barre d'appel est fixée en bas d'écran : contrairement à
        // l'en-tête, elle ne défile pas avec la page.
        $response->assertSee('call-bar', escape: false);
        $response->assertSee('tel:0612345678', escape: false);
        $response->assertSee('06 12 34 56 78');
    }

    #[Test]
    public function le_numero_est_repris_dans_le_tiroir(): void
    {
        $response = $this->get('/');

        $response->assertSee('drawer__phone', escape: false);
    }

    #[Test]
    public function la_barre_de_reglages_conserve_des_libelles_complets(): void
    {
        $response = $this->get('/');

        // Le complément de libellé est masqué visuellement sur mobile, mais
        // il doit rester dans le nom accessible du bouton.
        $response->assertSee('a11y-bar__btn-extra', escape: false);
        $response->assertSee('renforcé', escape: false);
        $response->assertSee('Réduire les', escape: false);
    }

    #[Test]
    public function la_barre_de_reglages_est_masquee_sans_javascript(): void
    {
        $this->get('/')->assertSee('<noscript>', escape: false);
    }

    #[Test]
    public function le_site_reste_utilisable_sans_numero_de_telephone(): void
    {
        SiteSetting::query()->where('key', 'phone')->update(['value' => '']);
        SiteSetting::query()->where('key', 'phone_display')->update(['value' => '']);

        Cache::flush();

        $response = $this->get('/');

        $response->assertOk();
        // Aucune barre d'appel vide ne doit s'afficher.
        $response->assertDontSee('call-bar__link', escape: false);
        $response->assertDontSee('has-call-bar', escape: false);
    }
}
