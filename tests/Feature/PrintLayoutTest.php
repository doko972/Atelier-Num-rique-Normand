<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PracticalGuide;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Gabarit des documents imprimables.
 *
 * La barre d'outils et la feuille partagent la même largeur : un bouton
 * décalé par rapport au document sur lequel il agit trahit un assemblage
 * approximatif, et ces documents sont destinés à des interlocuteurs
 * professionnels.
 */
class PrintLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function documents(): array
    {
        return [
            'présentation professionnelle' => ['partnership.brochure', 'a4'],
            'plaquette grand public' => ['leaflet', 'a5'],
        ];
    }

    #[Test]
    #[DataProvider('documents')]
    public function chaque_document_declare_son_format(string $route, string $format): void
    {
        $this->get(route($route))
            ->assertOk()
            ->assertSee('class="print-page print-page--'.$format.'"', escape: false);
    }

    #[Test]
    #[DataProvider('documents')]
    public function la_barre_d_outils_et_la_feuille_partagent_le_meme_conteneur(
        string $route,
        string $format,
    ): void {
        $content = $this->get(route($route))->assertOk()->getContent();

        $page = strpos($content, 'print-page--'.$format);
        $toolbar = strpos($content, 'print-toolbar');
        $sheet = strpos($content, 'print-sheet');

        $this->assertNotFalse($toolbar, 'La barre d’outils est absente.');
        $this->assertNotFalse($sheet, 'La feuille est absente.');

        // Les deux sont bien à l'intérieur du même bloc, dans cet ordre.
        $this->assertLessThan($toolbar, $page);
        $this->assertLessThan($sheet, $toolbar);
    }

    #[Test]
    #[DataProvider('documents')]
    public function la_barre_d_outils_ne_s_imprime_pas(string $route): void
    {
        $this->get(route($route))
            ->assertOk()
            ->assertSee('class="print-toolbar no-print"', escape: false);
    }

    #[Test]
    public function les_consignes_d_impression_restent_hors_de_la_feuille(): void
    {
        $content = $this->get(route('leaflet'))->assertOk()->getContent();

        $notes = strpos($content, 'print-notes');
        $instructions = strpos($content, 'Comment l’imprimer');

        $this->assertNotFalse($notes);
        $this->assertNotFalse($instructions);

        // Sans quoi la consigne « choisissez 2 pages par feuille » se
        // retrouverait imprimée sur la plaquette distribuée.
        $this->assertLessThan($instructions, $notes);
    }

    #[Test]
    public function une_fiche_pratique_utilise_le_meme_gabarit(): void
    {
        $guide = PracticalGuide::query()->published()->firstOrFail();

        $this->get(route('resources.guide.print', $guide))
            ->assertOk()
            ->assertSee('print-page--a4', escape: false)
            ->assertSee('print-sheet', escape: false);
    }

    #[Test]
    public function les_documents_ne_sont_pas_indexables(): void
    {
        foreach (['partnership.brochure', 'leaflet'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('noindex', escape: false);
        }
    }
}
