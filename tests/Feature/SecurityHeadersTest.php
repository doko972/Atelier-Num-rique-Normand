<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Politique de sécurité du contenu (codex §26).
 *
 * La politique doit rester stricte en production, tout en laissant travailler
 * le serveur de développement Vite : sans cette souplesse, `npm run dev`
 * produit une page sans style ni script, ce qui est très déroutant.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        config()->set('site.security.csp_enabled', true);
    }

    protected function tearDown(): void
    {
        File::delete(public_path('hot'));

        parent::tearDown();
    }

    #[Test]
    public function la_politique_est_stricte_sans_serveur_de_developpement(): void
    {
        File::delete(public_path('hot'));

        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);

        // Aucune origine externe ne doit apparaître.
        $this->assertStringNotContainsString('5173', $csp);
        $this->assertStringNotContainsString('localhost', $csp);
        $this->assertStringNotContainsString('ws://', $csp);
    }

    #[Test]
    public function la_politique_autorise_le_serveur_de_developpement_vite(): void
    {
        // Le fichier « hot » est écrit par Vite au démarrage de `npm run dev`.
        File::put(public_path('hot'), 'http://127.0.0.1:5174');

        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        // Le port est lu dans le fichier, jamais supposé : Vite bascule sur le
        // port suivant quand 5173 est déjà occupé.
        $this->assertStringContainsString('http://127.0.0.1:5174', $csp);
        $this->assertStringContainsString('http://localhost:5174', $csp);

        [$connect] = array_values(array_filter(
            explode('; ', $csp),
            fn (string $directive): bool => str_starts_with($directive, 'connect-src'),
        ));

        // Le WebSocket du rechargement à chaud n'est autorisé que pour les
        // connexions sortantes.
        $this->assertStringContainsString('ws://127.0.0.1:5174', $connect);

        [$script] = array_values(array_filter(
            explode('; ', $csp),
            fn (string $directive): bool => str_starts_with($directive, 'script-src'),
        ));

        $this->assertStringNotContainsString('ws://', $script);
    }

    #[Test]
    public function aucune_adresse_ipv6_litterale_n_est_emise(): void
    {
        // La grammaire d'une source CSP ne prévoit pas les adresses IPv6
        // entre crochets : le navigateur les écarte en silence, puis bloque
        // la ressource. Les émettre donnerait l'illusion d'une politique
        // correcte alors que scripts et styles resteraient bloqués.
        File::put(public_path('hot'), 'http://[::1]:5173');

        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString('[::1]', $csp);

        // La forme IPv4 équivalente, elle, doit bien être autorisée.
        $this->assertStringContainsString('http://127.0.0.1:5173', $csp);
        $this->assertStringContainsString('ws://127.0.0.1:5173', $csp);
    }

    #[Test]
    public function un_nonce_different_est_genere_a_chaque_reponse(): void
    {
        $premier = $this->get('/')->headers->get('Content-Security-Policy');
        $second = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("/'nonce-[A-Za-z0-9]{24}'/", $premier);
        $this->assertNotSame($premier, $second, 'Le nonce doit changer à chaque réponse.');
    }

    #[Test]
    public function les_balises_vite_portent_le_nonce_de_la_reponse(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');

        preg_match("/'nonce-([A-Za-z0-9]{24})'/", $csp, $matches);

        $this->assertNotEmpty($matches, 'La politique doit contenir un nonce.');
        $response->assertSee('nonce="'.$matches[1].'"', escape: false);
    }

    #[Test]
    public function la_politique_peut_etre_desactivee_par_configuration(): void
    {
        config()->set('site.security.csp_enabled', false);

        $this->assertNull($this->get('/')->headers->get('Content-Security-Policy'));
    }
}
