<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Support\Branding;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Déclinaisons du logo.
 *
 * Une identité visuelle arrive rarement complète : le monogramme ou la
 * version monochrome peuvent manquer pendant des semaines. Aucune page ne
 * doit afficher d'image cassée pendant ce temps.
 */
class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function le_logo_complet_est_toujours_disponible(): void
    {
        // C'est la seule déclinaison obligatoire : tout le reste s'y rabat.
        $this->assertFileExists(public_path('images/'.Branding::FULL));
    }

    #[Test]
    public function une_declinaison_absente_se_rabat_sur_le_logo_complet(): void
    {
        $url = Branding::logo('logo-inexistant.png');

        $this->assertStringEndsWith('images/'.Branding::FULL, $url);
    }

    #[Test]
    public function une_declinaison_presente_est_bien_servie(): void
    {
        $this->assertStringEndsWith(
            'images/'.Branding::FULL,
            Branding::logo(Branding::FULL),
        );
    }

    #[Test]
    public function toutes_les_adresses_de_logo_pointent_vers_un_fichier_existant(): void
    {
        foreach ([
            Branding::FULL,
            Branding::MARK,
            Branding::MONO_DARK,
            Branding::MONO_LIGHT,
            Branding::MARK_LIGHT,
        ] as $variant) {
            $url = Branding::logo($variant);
            $file = public_path('images/'.basename(parse_url($url, PHP_URL_PATH)));

            $this->assertFileExists($file, "La déclinaison {$variant} renvoie vers un fichier absent.");
        }
    }

    #[Test]
    public function les_monogrammes_sont_carres(): void
    {
        // Une icône non carrée serait déformée par le navigateur dans
        // l'onglet, et par le menu latéral.
        foreach ([Branding::MARK, Branding::MARK_LIGHT] as $variant) {
            if (! Branding::has($variant)) {
                continue;
            }

            [$width, $height] = getimagesize(public_path('images/'.$variant));

            $this->assertSame($width, $height, "La déclinaison {$variant} n’est pas carrée.");
        }
    }

    #[Test]
    public function l_icone_du_navigateur_est_declaree_en_png(): void
    {
        // Le format ICO n'est plus requis : tous les navigateurs en service
        // acceptent un PNG.
        $this->get('/')
            ->assertOk()
            ->assertSee('type="image/png"', escape: false)
            ->assertSee('rel="apple-touch-icon"', escape: false);
    }

    #[Test]
    public function l_icone_ico_n_est_declaree_que_si_le_fichier_existe(): void
    {
        $content = $this->get('/')->assertOk()->getContent();

        $declared = str_contains($content, 'images/favicon.ico');

        $this->assertSame(
            Branding::hasIcoFavicon(),
            $declared,
            'Le fichier ICO ne doit être déclaré que s’il est réellement présent.',
        );
    }

    #[Test]
    public function les_pages_publiques_affichent_un_logo(): void
    {
        foreach (['/', '/mes-services', '/contact'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('class="site-logo', escape: false);
        }
    }

    #[Test]
    public function la_plaquette_utilise_la_version_monochrome(): void
    {
        // La plaquette est imprimée chez soi, en noir et blanc, par paquets
        // de vingt : le logo couleur y deviendrait une bouillie de gris.
        $expected = basename(parse_url(Branding::logo(Branding::MONO_DARK), PHP_URL_PATH));

        $this->get(route('leaflet'))
            ->assertOk()
            ->assertSee('images/'.$expected, escape: false);
    }

    #[Test]
    public function la_presentation_professionnelle_utilise_la_version_couleur(): void
    {
        // Celle-ci part le plus souvent par courriel et se lit à l'écran
        // avant d'être imprimée, parfois en couleur par la collectivité.
        $expected = basename(parse_url(Branding::logo(Branding::FULL), PHP_URL_PATH));

        $this->get(route('partnership.brochure'))
            ->assertOk()
            ->assertSee('images/'.$expected, escape: false);
    }

    #[Test]
    public function le_menu_d_administration_affiche_un_logo_lisible(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->get(route('admin.dashboard'))
            ->assertOk();

        $response->assertSee('admin-sidebar__logo', escape: false);

        // Le menu est bleu foncé : soit le monogramme blanc, soit le
        // monogramme couleur posé sur une pastille claire. Jamais le logo
        // complet, illisible à 32 pixels.
        if (Branding::has(Branding::MARK_LIGHT)) {
            $response->assertSee(Branding::MARK_LIGHT, escape: false);
            $response->assertSee('admin-sidebar__logo--transparent', escape: false);
        } else {
            $response->assertDontSee('admin-sidebar__logo--transparent', escape: false);
        }

        $response->assertDontSee('admin-sidebar__logo" src="'.Branding::logo(Branding::FULL), escape: false);
    }
}
