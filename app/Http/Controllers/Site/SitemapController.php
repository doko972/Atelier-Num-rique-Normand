<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Page;
use App\Models\PracticalGuide;
use App\Models\ServiceCategory;
use App\Models\Workshop;
use Illuminate\Http\Response;

/**
 * Plan du site au format XML et fichier robots.txt (codex §28).
 */
class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect();

        // Pages fixes, avec leur importance relative.
        foreach ([
            ['route' => 'home', 'priority' => '1.0', 'frequency' => 'weekly'],
            ['route' => 'services.index', 'priority' => '0.9', 'frequency' => 'monthly'],
            ['route' => 'workshops.index', 'priority' => '0.9', 'frequency' => 'weekly'],
            ['route' => 'appointments.create', 'priority' => '0.9', 'frequency' => 'monthly'],
            ['route' => 'resources.index', 'priority' => '0.8', 'frequency' => 'weekly'],
            ['route' => 'pricing', 'priority' => '0.7', 'frequency' => 'monthly'],
            ['route' => 'partnership.create', 'priority' => '0.8', 'frequency' => 'monthly'],
            ['route' => 'about', 'priority' => '0.6', 'frequency' => 'yearly'],
            ['route' => 'contact.create', 'priority' => '0.8', 'frequency' => 'yearly'],
            ['route' => 'faq', 'priority' => '0.6', 'frequency' => 'monthly'],
            ['route' => 'procedures', 'priority' => '0.7', 'frequency' => 'monthly'],
            ['route' => 'security', 'priority' => '0.8', 'frequency' => 'weekly'],
        ] as $entry) {
            $urls->push([
                'loc' => route($entry['route']),
                'lastmod' => null,
                'changefreq' => $entry['frequency'],
                'priority' => $entry['priority'],
            ]);
        }

        ServiceCategory::query()->published()->ordered()->get()
            ->each(fn (ServiceCategory $category) => $urls->push([
                'loc' => route('services.show', $category),
                'lastmod' => $category->updated_at?->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ]));

        Workshop::query()->public()->upcoming()->get()
            ->each(fn (Workshop $workshop) => $urls->push([
                'loc' => route('workshops.show', $workshop),
                'lastmod' => $workshop->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ]));

        PracticalGuide::query()->published()->get()
            ->each(fn (PracticalGuide $guide) => $urls->push([
                'loc' => route('resources.guide', $guide),
                'lastmod' => $guide->updated_at?->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ]));

        Article::query()->published()->get()
            ->each(fn (Article $article) => $urls->push([
                'loc' => route('resources.article', $article),
                'lastmod' => $article->updated_at?->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ]));

        // Les pages légales sont indexables sauf mention contraire.
        Page::query()->published()->where('noindex', false)->get()
            ->each(fn (Page $page) => $urls->push([
                'loc' => route('pages.show', $page),
                'lastmod' => $page->updated_at?->toAtomString(),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ]));

        return response()
            ->view('site.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /administration',
            'Disallow: /documents',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines))
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
