<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La page « À propos » est celle où l'on décide de faire confiance ou non.
 * Son texte est celui du conseiller, pas une trame générique.
 */
class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PageSeeder::class);
    }

    #[Test]
    public function elle_raconte_le_parcours_reel(): void
    {
        $this->get('/a-propos')
            ->assertOk()
            ->assertSee('Armée de Terre', escape: false)
            ->assertSee('technicien informatique')
            ->assertSee('L’Atelier Numérique Normand', escape: false);
    }

    #[Test]
    public function elle_ne_contient_plus_la_trame_generique(): void
    {
        $this->get('/a-propos')
            ->assertDontSee('J’ai travaillé plusieurs années dans l’informatique avant', escape: false);
    }

    #[Test]
    public function les_sections_sont_de_vrais_intertitres(): void
    {
        // Sans balises de titre, un lecteur d'écran ne peut pas sauter d'une
        // section à l'autre : il faut tout écouter.
        $this->get('/a-propos')
            ->assertSee('<h2>Mon parcours</h2>', escape: false)
            ->assertSee('<h2>Ma méthode</h2>', escape: false)
            ->assertSee('<h2>Mes engagements</h2>', escape: false);
    }

    #[Test]
    public function les_marques_de_mise_en_forme_ne_s_affichent_pas(): void
    {
        $contenu = (string) $this->get('/a-propos')->getContent();

        $this->assertStringNotContainsString('## ', $contenu);
        $this->assertStringNotContainsString('**L’Atelier', $contenu);
    }

    #[Test]
    public function le_semoir_remplace_la_trame_restee_en_place(): void
    {
        // Cas du site déjà installé : le texte date de la première mise en
        // service et n'a jamais été relu.
        $page = Page::findByKey(Page::KEY_ABOUT);
        $page->update(['body' => 'J’ai travaillé plusieurs années dans l’informatique avant de me consacrer aux gens.']);

        $this->seed(PageSeeder::class);

        $this->assertStringContainsString(
            'Armée de Terre',
            (string) Page::findByKey(Page::KEY_ABOUT)->body,
        );
    }

    #[Test]
    public function le_semoir_preserve_un_texte_ecrit_par_le_conseiller(): void
    {
        $page = Page::findByKey(Page::KEY_ABOUT);
        $page->update(['body' => 'Ma propre présentation, écrite depuis le back-office.']);

        $this->seed(PageSeeder::class);

        $this->assertSame(
            'Ma propre présentation, écrite depuis le back-office.',
            Page::findByKey(Page::KEY_ABOUT)->body,
        );
    }
}
