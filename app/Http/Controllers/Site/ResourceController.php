<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\PracticalGuide;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Centre de ressources : articles et fiches pratiques (codex §14).
 */
class ResourceController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'rubrique' => ['nullable', 'string', 'exists:article_categories,slug'],
            'recherche' => ['nullable', 'string', 'max:100'],
        ]);

        $category = $validated['rubrique'] ?? null;
        $search = $validated['recherche'] ?? null;

        $guides = PracticalGuide::query()
            ->published()
            ->with('category')
            ->when($category, fn ($query, string $slug) => $query->whereHas(
                'category',
                fn ($relation) => $relation->where('slug', $slug),
            ))
            ->when($search, fn ($query, string $terms) => $query->where(function ($query) use ($terms): void {
                $query->where('title', 'like', "%{$terms}%")
                    ->orWhere('summary', 'like', "%{$terms}%");
            }))
            ->orderByDesc('is_featured')
            ->orderBy('title')
            ->get();

        $articles = Article::query()
            ->published()
            ->with('category')
            ->when($category, fn ($query, string $slug) => $query->whereHas(
                'category',
                fn ($relation) => $relation->where('slug', $slug),
            ))
            ->when($search, fn ($query, string $terms) => $query->where(function ($query) use ($terms): void {
                $query->where('title', 'like', "%{$terms}%")
                    ->orWhere('excerpt', 'like', "%{$terms}%");
            }))
            ->recent()
            ->paginate((int) config('site.per_page.public'))
            ->withQueryString();

        return view('site.resources.index', [
            'guides' => $guides,
            'articles' => $articles,
            'categories' => ArticleCategory::query()->published()->ordered()->get(),
            'filters' => $validated,
        ]);
    }

    public function article(Article $article): View
    {
        abort_unless($article->status->isPublic(), 404);

        return view('site.resources.article', [
            'article' => $article->load(['category', 'files']),
            'related' => Article::query()
                ->published()
                ->where('article_category_id', $article->article_category_id)
                ->whereKeyNot($article->getKey())
                ->recent()
                ->limit(3)
                ->get(),
        ]);
    }

    public function guide(PracticalGuide $guide): View
    {
        abort_unless($guide->status->isPublic(), 404);

        return view('site.resources.guide', [
            'guide' => $guide->load(['category', 'steps', 'files']),
            'related' => PracticalGuide::query()
                ->published()
                ->where('article_category_id', $guide->article_category_id)
                ->whereKeyNot($guide->getKey())
                ->limit(3)
                ->get(),
        ]);
    }

    /**
     * Version imprimable d'une fiche pratique.
     *
     * Cette page n'affiche ni menu ni encart : elle est pensée pour être
     * remise sur papier à la fin d'un accompagnement.
     */
    public function printGuide(PracticalGuide $guide): View
    {
        abort_unless($guide->status->isPublic(), 404);

        return view('site.resources.guide-print', [
            'guide' => $guide->load('steps'),
        ]);
    }
}
