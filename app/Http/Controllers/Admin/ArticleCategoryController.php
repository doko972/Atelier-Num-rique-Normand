<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Enums\ContentStatus;
use App\Models\ArticleCategory;

class ArticleCategoryController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return ArticleCategory::class;
    }

    protected function routeKey(): string
    {
        return 'article-categories';
    }

    /**
     * @return array{title: string, singular: string, plural: string, intro: string}
     */
    protected function labels(): array
    {
        return [
            'title' => 'Rubriques des ressources',
            'singular' => 'la rubrique',
            'plural' => 'rubriques',
            'intro' => 'Les rubriques classent à la fois les articles et les fiches pratiques.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('name', 'Nom de la rubrique')->listed()->searchable(),
            Field::text('slug', 'Adresse de la page', ['nullable', 'string', 'max:170']),
            Field::text('summary', 'Résumé', ['nullable', 'string', 'max:255'])->searchable(),
            Field::select('status', 'Statut', ContentStatus::options())->listed(),
            Field::number('position', 'Ordre d’affichage')->listed(),
        ];
    }
}
