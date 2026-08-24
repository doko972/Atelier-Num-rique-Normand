<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Le pied de page mêle deux sources : quatre liens écrits en dur, qui ont
 * chacun leur route stable, et les pages marquées « à afficher en pied ».
 *
 * Les quatre étant aussi marquées ainsi, elles apparaissaient deux fois.
 */
class FooterLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PageSeeder::class);
    }

    #[Test]
    public function chaque_page_legale_n_apparait_qu_une_fois(): void
    {
        $contenu = $this->get('/')->getContent();

        foreach (['Mentions légales', 'Politique de confidentialité', 'Gestion des cookies', 'Déclaration d’accessibilité'] as $titre) {
            $this->assertSame(
                1,
                mb_substr_count((string) $contenu, $titre),
                "« {$titre} » apparaît plusieurs fois dans le pied de page.",
            );
        }
    }

    #[Test]
    public function les_quatre_liens_restent_presents(): void
    {
        // Écarter les doublons ne doit pas faire disparaître les liens :
        // trois d'entre eux sont exigés par la loi.
        $this->get('/')
            ->assertSee(route('legal'), escape: false)
            ->assertSee(route('privacy'), escape: false)
            ->assertSee(route('cookies'), escape: false)
            ->assertSee(route('accessibility'), escape: false);
    }

    #[Test]
    public function une_page_ajoutee_ensuite_s_affiche_bien(): void
    {
        // La liste dynamique garde son utilité : elle sert aux pages créées
        // depuis le back-office, qui n'ont pas de route dédiée.
        Page::create([
            'title' => 'Conditions générales',
            'slug' => 'conditions-generales',
            'body' => 'Le texte des conditions.',
            'show_in_footer' => true,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $this->get('/')->assertSee('Conditions générales');
    }

    #[Test]
    public function une_page_non_publiee_reste_absente(): void
    {
        Page::create([
            'title' => 'Brouillon interne',
            'slug' => 'brouillon-interne',
            'body' => 'Pas encore relu.',
            'show_in_footer' => true,
            'status' => ContentStatus::Draft,
        ]);

        $this->get('/')->assertDontSee('Brouillon interne');
    }
}
