<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Les mentions légales sont la seule page dont le contenu est imposé par la
 * loi (art. 6 et 19 de la LCEN). Une régression y est un manquement, pas un
 * défaut d'affichage : elle mérite d'être verrouillée.
 */
class LegalNoticeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->seed(PageSeeder::class);
    }

    #[Test]
    public function elle_publie_l_identite_de_l_editeur(): void
    {
        $response = $this->get('/mentions-legales');

        $response->assertOk();
        $response->assertSee('David Grougi');
        $response->assertSee('Entreprise individuelle — micro-entrepreneur', escape: false);
        $response->assertSee('939 474 755 00013');
        $response->assertSee('3 route de Vassy');
        $response->assertSee('14110 Condé-en-Normandie', escape: false);
        $response->assertSee('06 16 60 28 98');
    }

    #[Test]
    public function elle_nomme_l_hebergeur(): void
    {
        $this->get('/mentions-legales')->assertSee('Hostinger International Ltd');
    }

    #[Test]
    public function elle_designe_le_directeur_de_la_publication(): void
    {
        $this->get('/mentions-legales')
            ->assertSee('Directeur de la publication')
            ->assertSee('David Grougi');
    }

    #[Test]
    public function elle_ne_laisse_aucune_trame_de_travail_en_ligne(): void
    {
        // Le texte semé disait « À compléter depuis les paramètres du site ».
        // Publié tel quel, il annonce au visiteur que la page est vide.
        $this->get('/mentions-legales')->assertDontSee('À compléter', escape: false);
    }

    #[Test]
    public function le_semoir_remplace_une_trame_restee_en_place(): void
    {
        // Cas d'un site déjà installé : le corps de la page date de la
        // première installation et n'a jamais été relu.
        $page = Page::findByKey(Page::KEY_LEGAL);
        $page->update(['body' => "Hébergement\n\nÀ compléter depuis les paramètres du site."]);

        $this->seed(PageSeeder::class);

        $this->assertStringNotContainsString(
            'À compléter',
            (string) Page::findByKey(Page::KEY_LEGAL)->body,
        );
    }

    #[Test]
    public function le_semoir_preserve_un_texte_relu(): void
    {
        $page = Page::findByKey(Page::KEY_LEGAL);
        $page->update(['body' => 'Texte relu et validé par l’éditeur.']);

        $this->seed(PageSeeder::class);

        $this->assertSame(
            'Texte relu et validé par l’éditeur.',
            Page::findByKey(Page::KEY_LEGAL)->body,
        );
    }

    #[Test]
    public function le_pied_de_page_y_renvoie(): void
    {
        $this->get('/')->assertSee(route('legal'), escape: false);
    }
}
