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
 * Métadonnées de la page d'accueil (codex §28).
 *
 * Le titre et la description sont les deux seules choses qu'une personne voit
 * dans un résultat de recherche. Ils sont administrables, et ces tests
 * vérifient qu'ils sortent bien du bon champ : la description destinée aux
 * moteurs est distincte du sous-titre affiché à l'écran.
 */
class HomeMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    protected function meta(string $name): ?string
    {
        $content = $this->get('/')->assertOk()->getContent();

        preg_match('/<meta name="'.preg_quote($name, '/').'" content="(.*?)">/', $content, $matches);

        return $matches[1] ?? null;
    }

    protected function property(string $name): ?string
    {
        $content = $this->get('/')->assertOk()->getContent();

        preg_match('/<meta property="'.preg_quote($name, '/').'" content="(.*?)">/', $content, $matches);

        return $matches[1] ?? null;
    }

    #[Test]
    public function le_titre_place_le_metier_et_la_commune_en_tete(): void
    {
        $content = $this->get('/')->assertOk()->getContent();

        preg_match('/<title>(.*?)<\/title>/', $content, $matches);

        $title = html_entity_decode($matches[1]);

        // Les mots recherchés doivent précéder la marque : c'est le début du
        // titre qui pèse le plus, et qui survit à la troncature.
        $this->assertStringStartsWith(
            SiteSetting::query()->where('key', 'site_tagline')->value('value'),
            $title,
        );

        $this->assertStringContainsString(
            SiteSetting::query()->where('key', 'site_name')->value('value'),
            $title,
        );
    }

    #[Test]
    public function la_description_provient_du_champ_dedie(): void
    {
        $expected = SiteSetting::query()->where('key', 'meta_description')->value('value');

        $this->assertSame($expected, html_entity_decode((string) $this->meta('description')));
    }

    #[Test]
    public function la_description_n_est_pas_le_sous_titre_affiche(): void
    {
        // Les deux champs remplissent des rôles différents : l'un s'adresse
        // aux moteurs, l'autre à la personne qui vient d'arriver.
        SiteSetting::query()
            ->where('key', 'meta_description')
            ->update(['value' => 'Description destinée aux moteurs de recherche.']);

        SiteSetting::query()
            ->where('key', 'home_hero_subtitle')
            ->update(['value' => 'Phrase affichée sous le titre de la page.']);

        Cache::flush();

        $this->assertSame(
            'Description destinée aux moteurs de recherche.',
            html_entity_decode((string) $this->meta('description')),
        );

        $this->get('/')->assertSee('Phrase affichée sous le titre de la page.');
    }

    #[Test]
    public function open_graph_reprend_la_meme_description(): void
    {
        $this->assertSame(
            html_entity_decode((string) $this->meta('description')),
            html_entity_decode((string) $this->property('og:description')),
        );
    }

    #[Test]
    public function open_graph_declare_une_illustration(): void
    {
        $this->assertStringContainsString('og-image.png', (string) $this->property('og:image'));
        $this->assertNotEmpty($this->property('og:image:alt'));
    }

    #[Test]
    public function l_illustration_de_partage_existe_et_est_au_bon_format(): void
    {
        $file = public_path('images/og-image.png');

        $this->assertFileExists($file, 'La balise og:image pointerait vers une adresse vide.');

        // 1200 × 630 est le format attendu pour un grand aperçu. En deçà, les
        // réseaux rognent l'image ou la réduisent à une vignette.
        [$width, $height] = getimagesize($file);

        $this->assertSame(1200, $width);
        $this->assertSame(630, $height);

        $this->assertSame('1200', $this->property('og:image:width'));
        $this->assertSame('630', $this->property('og:image:height'));
        $this->assertSame('summary_large_image', $this->meta('twitter:card'));
    }

    #[Test]
    public function le_monogramme_est_carre_et_leger(): void
    {
        $file = public_path('images/logo-mark.png');

        $this->assertFileExists($file);

        [$width, $height] = getimagesize($file);

        // Une icône doit être carrée : un rapport différent serait déformé
        // par le navigateur dans l'onglet.
        $this->assertSame($width, $height);

        // Le site doit rester utilisable sur une connexion lente (codex §30).
        $this->assertLessThan(
            60 * 1024,
            filesize($file),
            'Le monogramme est chargé sur chaque page : il doit rester léger.',
        );
    }

    #[Test]
    public function l_adresse_canonique_est_declaree(): void
    {
        $this->get('/')->assertSee('<link rel="canonical" href="'.route('home').'">', escape: false);
    }

    #[Test]
    public function les_icones_du_site_sont_declarees(): void
    {
        $response = $this->get('/');

        // Le format ICO n'est plus exigé : tous les navigateurs encore en
        // service acceptent un PNG. Voir BrandingTest pour la déclaration
        // conditionnelle du fichier ICO lorsqu'il est fourni.
        $response->assertSee('rel="icon"', escape: false);
        $response->assertSee('type="image/png"', escape: false);
        $response->assertSee('rel="apple-touch-icon"', escape: false);
    }
}
