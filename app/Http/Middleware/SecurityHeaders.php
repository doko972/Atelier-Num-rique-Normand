<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité et politique de sécurité du contenu (codex §26).
 *
 * En production, le site n'appelle aucune ressource externe : polices, styles
 * et scripts sont auto-hébergés. La politique peut donc rester très stricte,
 * ce qui protège aussi la vie privée des visiteurs.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Un nonce par réponse autorise nos rares scripts en ligne sans jamais
        // ouvrir la porte à `unsafe-inline`.
        $nonce = Str::random(24);
        $request->attributes->set('csp_nonce', $nonce);

        // Les balises produites par la directive @vite reçoivent le même
        // nonce : c'est le mécanisme prévu par Laravel, et il évite d'avoir à
        // relâcher la politique pour nos propres scripts.
        Vite::useCspNonce($nonce);

        /** @var Response $response */
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
        ];

        if (config('site.security.force_https')) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        if (config('site.security.csp_enabled')) {
            $headers['Content-Security-Policy'] = $this->contentSecurityPolicy($nonce);
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value, false);
        }

        return $response;
    }

    protected function contentSecurityPolicy(string $nonce): string
    {
        $analytics = (string) config('site.analytics.matomo_url');

        $connect = ["'self'"];
        $script = ["'self'", "'nonce-{$nonce}'"];
        $style = ["'self'", "'unsafe-inline'"];
        $font = ["'self'"];

        // Matomo est la seule origine externe éventuelle en production, et
        // uniquement si l'administrateur l'a configurée puis que le visiteur
        // l'a acceptée.
        if (config('site.analytics.enabled') && filled($analytics)) {
            $connect[] = $analytics;
            $script[] = $analytics;
        }

        // Pendant `npm run dev`, Vite sert les assets depuis son propre
        // serveur et pousse les mises à jour par WebSocket. Ces origines sont
        // ajoutées uniquement quand le serveur de développement tourne : en
        // production, le fichier `hot` n'existe pas et la politique retrouve
        // sa forme stricte.
        $vite = $this->viteDevServerOrigins();

        foreach ($vite['http'] as $origin) {
            $script[] = $origin;
            $style[] = $origin;
            $font[] = $origin;
            $connect[] = $origin;
        }

        // Le WebSocket ne concerne que les connexions sortantes.
        foreach ($vite['websocket'] as $origin) {
            $connect[] = $origin;
        }

        $directives = [
            "default-src 'self'",
            'script-src '.implode(' ', $script),
            // Les styles en ligne restent nécessaires pour les jauges de
            // places restantes, dont la largeur dépend des données.
            'style-src '.implode(' ', $style),
            "img-src 'self' data:",
            'font-src '.implode(' ', $font),
            'connect-src '.implode(' ', $connect),
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        if (config('site.security.force_https')) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }

    /**
     * Origines du serveur de développement Vite, s'il tourne.
     *
     * Le port est lu dans le fichier `hot` plutôt que fixé en dur : Vite passe
     * au port suivant lorsque 5173 est déjà pris.
     *
     * Seules des adresses IPv4 sont émises. La grammaire d'une source CSP ne
     * prévoit pas les adresses IPv6 littérales : un navigateur écarte
     * silencieusement `http://[::1]:5173`, et bloque ensuite la ressource.
     * C'est pourquoi la configuration de Vite force l'écoute en IPv4 — voir
     * `vite.config.js`.
     *
     * @return array{http: array<int, string>, websocket: array<int, string>}
     */
    protected function viteDevServerOrigins(): array
    {
        $empty = ['http' => [], 'websocket' => []];

        if (! Vite::isRunningHot()) {
            return $empty;
        }

        $url = trim((string) @file_get_contents(public_path('hot')));

        if (blank($url)) {
            return $empty;
        }

        $port = parse_url($url, PHP_URL_PORT) ?: 5173;
        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'http';
        $websocket = $scheme === 'https' ? 'wss' : 'ws';

        $hosts = ['127.0.0.1', 'localhost'];

        $host = parse_url($url, PHP_URL_HOST);

        // Une adresse IPv6 littérale est inexploitable dans une politique de
        // sécurité : elle est ignorée plutôt qu'ajoutée, sans quoi la
        // directive contiendrait une source que le navigateur rejette.
        if ($host !== null && $host !== false && ! str_starts_with($host, '[')) {
            $hosts[] = $host;
        }

        $origins = $empty;

        foreach (array_unique($hosts) as $allowed) {
            $origins['http'][] = "{$scheme}://{$allowed}:{$port}";
            $origins['websocket'][] = "{$websocket}://{$allowed}:{$port}";
        }

        return $origins;
    }
}
