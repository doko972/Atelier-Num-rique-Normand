<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Affichage des pages publiques (codex §37).
 */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function publicRoutes(): array
    {
        return [
            'accueil' => ['/'],
            'services' => ['/mes-services'],
            'ateliers' => ['/ateliers'],
            'prise de rendez-vous' => ['/prendre-rendez-vous'],
            'contact' => ['/contact'],
            'partenariats' => ['/partenariats'],
            'conseils pratiques' => ['/conseils-pratiques'],
            'tarifs' => ['/tarifs'],
            'à propos' => ['/a-propos'],
            'démarches en ligne' => ['/demarches-en-ligne'],
            'sécurité et arnaques' => ['/securite-et-arnaques'],
            'questions fréquentes' => ['/questions-frequentes'],
            'mentions légales' => ['/mentions-legales'],
            'confidentialité' => ['/politique-de-confidentialite'],
            'cookies' => ['/gestion-des-cookies'],
            'accessibilité' => ['/declaration-accessibilite'],
            'plan du site' => ['/sitemap.xml'],
            'robots' => ['/robots.txt'],
        ];
    }

    #[Test]
    #[DataProvider('publicRoutes')]
    public function toutes_les_pages_publiques_repondent(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    #[Test]
    public function la_page_d_accueil_affiche_le_numero_de_telephone(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        // Le téléphone doit être présent dans l'en-tête sur toutes les pages.
        $response->assertSee('site-header__phone', escape: false);
    }

    #[Test]
    public function la_page_d_accueil_contient_les_liens_d_evitement(): void
    {
        $response = $this->get('/');

        $response->assertSee('Aller au contenu principal');
        $response->assertSee('Aller au menu');
    }

    #[Test]
    public function les_pages_internes_affichent_un_fil_d_ariane(): void
    {
        $this->get('/mes-services')
            ->assertOk()
            ->assertSee('Vous êtes ici');
    }

    #[Test]
    public function le_plan_du_site_est_un_document_xml_valide(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml, 'Le plan du site doit être un document XML bien formé.');
        $this->assertGreaterThan(10, $xml->count());
    }

    #[Test]
    public function une_page_inexistante_renvoie_une_erreur_404(): void
    {
        $this->get('/cette-page-n-existe-pas')->assertNotFound();
    }

    #[Test]
    public function les_en_tetes_de_securite_sont_presents(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    #[Test]
    public function l_administration_est_interdite_a_l_indexation(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /administration');
    }
}
