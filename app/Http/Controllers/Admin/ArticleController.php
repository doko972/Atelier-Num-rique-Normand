<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\ArticleCategory;

class ArticleController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return Article::class;
    }

    protected function routeKey(): string
    {
        return 'articles';
    }

    protected function defaultSort(): string
    {
        return 'published_at';
    }

    protected function defaultSortDirection(): string
    {
        return 'desc';
    }

    /**
     * @return array<int, string>
     */
    protected function listRelations(): array
    {
        return ['category'];
    }

    /**
     * @return array{title: string, singular: string, plural: string, intro: string}
     */
    protected function labels(): array
    {
        return [
            'title' => 'Articles de conseils',
            'singular' => 'l’article',
            'plural' => 'articles',
            'intro' => 'Des textes courts, écrits en français simple, une idée à la fois.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('title', 'Titre')->listed()->searchable(),
            Field::text('slug', 'Adresse de la page', ['nullable', 'string', 'max:220']),
            Field::select(
                'article_category_id',
                'Rubrique',
                ArticleCategory::query()->ordered()->pluck('name', 'id')->all(),
                ['nullable', 'integer', 'exists:article_categories,id'],
            ),
            Field::textarea('excerpt', 'Résumé', ['required', 'string', 'max:400'],
                'Affiché dans la liste des articles. Deux phrases suffisent.', rows: 3)->searchable(),
            Field::richText('body', 'Contenu de l’article'),
            Field::text('image_alt', 'Description de l’image', ['nullable', 'string', 'max:255']),
            Field::number('reading_minutes', 'Durée de lecture (minutes)', ['nullable', 'integer', 'min:1', 'max:120'],
                'Laissez vide : elle sera estimée automatiquement.'),
            Field::date('published_at', 'Date de publication', ['nullable', 'date'])->listed(),
            Field::boolean('is_featured', 'Mettre en avant'),
            Field::select('status', 'Statut', ContentStatus::options())->listed(),
            Field::text('meta_title', 'Titre pour les moteurs de recherche', ['nullable', 'string', 'max:180']),
            Field::text('meta_description', 'Description pour les moteurs de recherche', ['nullable', 'string', 'max:255']),
        ];
    }
}
