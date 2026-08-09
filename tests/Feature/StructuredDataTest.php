<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\PracticalGuide;
use App\Models\Service;
use App\Models\SiteSetting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Données structurées Schema.org (codex §29).
 *
 * Ces tests existent à la suite d'un bug silencieux : la clé « @context »
 * écrite dans un fichier Blade était compilée comme la directive `@context`
 * de Laravel, ce qui produisait du JSON invalide sur toutes les pages. Rien
 * ne le signalait — ni erreur, ni page cassée — seul le référencement en
 * pâtissait. D'où une vérification qui analyse réellement le JSON produit.
 */
class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Extrait et décode tous les blocs JSON-LD d'une page.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function structuredBlocks(string $uri): array
    {
        $content = $this->get($uri)->assertOk()->getContent();

        preg_match_all(
            '#<script type="application/ld\+json">(.*?)</script>#s',
            $content,
            $matches,
        );

        return array_map(
            function (string $json) use ($uri): array {
                $decoded = json_decode(trim($json), true);

                $this->assertIsArray(
                    $decoded,
                    "Un bloc de données structurées de {$uri} n'est pas du JSON valide : ".trim($json),
                );

                return $decoded;
            },
            $matches[1],
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pagesWithStructuredData(): array
    {
        return [
            'accueil' => ['/', 'ProfessionalService'],
            'à propos' => ['/a-propos', 'Person'],
            'questions fréquentes' => ['/questions-frequentes', 'FAQPage'],
            'sécurité' => ['/securite-et-arnaques', 'FAQPage'],
        ];
    }

    #[Test]
    #[DataProvider('pagesWithStructuredData')]
    public function les_pages_exposent_le_bon_type_de_donnees_structurees(string $uri, string $type): void
    {
        $blocks = $this->structuredBlocks($uri);

        $this->assertNotEmpty($blocks, "Aucun bloc de données structurées sur {$uri}.");

        $types = array_column($blocks, '@type');

        $this->assertContains($type, $types, "Le type {$type} est absent de {$uri}.");
    }

    #[Test]
    public function chaque_bloc_declare_le_vocabulaire_schema_org(): void
    {
        foreach (['/', '/mes-services', '/questions-frequentes', '/a-propos'] as $uri) {
            foreach ($this->structuredBlocks($uri) as $block) {
                $this->assertArrayHasKey(
                    '@context',
                    $block,
                    "Un bloc de {$uri} ne déclare pas « @context ».",
                );

                $this->assertSame('https://schema.org', $block['@context']);
            }
        }
    }

    #[Test]
    public function le_fil_d_ariane_est_correctement_ordonne(): void
    {
        $blocks = $this->structuredBlocks('/mes-services');

        $breadcrumb = collect($blocks)->firstWhere('@type', 'BreadcrumbList');

        $this->assertNotNull($breadcrumb, 'Le fil d’Ariane doit exposer un BreadcrumbList.');
        $this->assertCount(2, $breadcrumb['itemListElement']);
        $this->assertSame(1, $breadcrumb['itemListElement'][0]['position']);
        $this->assertSame('Accueil', $breadcrumb['itemListElement'][0]['name']);

        // Le dernier élément est la page courante : il n'a pas de lien.
        $this->assertArrayNotHasKey('item', $breadcrumb['itemListElement'][1]);
    }

    #[Test]
    public function une_fiche_pratique_est_decrite_comme_un_mode_d_emploi(): void
    {
        $guide = PracticalGuide::query()->published()->with('steps')->firstOrFail();

        $blocks = $this->structuredBlocks(route('resources.guide', $guide, absolute: false));

        $howTo = collect($blocks)->firstWhere('@type', 'HowTo');

        $this->assertNotNull($howTo);
        $this->assertSame($guide->title, $howTo['name']);
        $this->assertNotEmpty($howTo['step']);
        $this->assertSame('HowToStep', $howTo['step'][0]['@type']);
    }

    #[Test]
    public function un_article_expose_sa_date_de_publication(): void
    {
        $article = Article::query()->published()->firstOrFail();

        $blocks = $this->structuredBlocks(route('resources.article', $article, absolute: false));

        $schema = collect($blocks)->firstWhere('@type', 'Article');

        $this->assertNotNull($schema);
        $this->assertSame($article->title, $schema['headline']);
        $this->assertArrayHasKey('datePublished', $schema);
    }

    #[Test]
    public function un_service_expose_sa_famille(): void
    {
        $service = Service::query()->published()->with('category')->firstOrFail();

        $blocks = $this->structuredBlocks(
            route('services.detail', [$service->category, $service], absolute: false),
        );

        $schema = collect($blocks)->firstWhere('@type', 'Service');

        $this->assertNotNull($schema);
        $this->assertSame($service->category->name, $schema['serviceType']);
    }

    #[Test]
    public function aucun_bloc_n_est_produit_lorsque_la_donnee_manque(): void
    {
        // Sans nom de conseiller renseigné, aucun bloc « Person » vide ne doit
        // être émis : un fragment incomplet nuit plus qu'il n'aide.
        SiteSetting::query()->where('key', 'adviser_name')->update(['value' => '']);
        Cache::flush();

        $types = array_column($this->structuredBlocks('/a-propos'), '@type');

        $this->assertNotContains('Person', $types);
    }
}
