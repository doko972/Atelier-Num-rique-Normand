<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Enums\ContentStatus;
use App\Models\WorkshopCategory;

class WorkshopCategoryController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return WorkshopCategory::class;
    }

    protected function routeKey(): string
    {
        return 'workshop-categories';
    }

    /**
     * @return array{title: string, singular: string, plural: string, intro: string}
     */
    protected function labels(): array
    {
        return [
            'title' => 'Thèmes d’ateliers',
            'singular' => 'le thème',
            'plural' => 'thèmes',
            'intro' => 'Les thèmes servent à filtrer l’agenda des ateliers.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('name', 'Nom du thème')->listed()->searchable(),
            Field::text('slug', 'Adresse', ['nullable', 'string', 'max:170']),
            Field::text('summary', 'Résumé', ['nullable', 'string', 'max:255'])->searchable(),
            Field::text('icon', 'Nom de l’icône', ['nullable', 'string', 'max:60']),
            Field::select('status', 'Statut', ContentStatus::options())->listed(),
            Field::number('position', 'Ordre d’affichage')->listed(),
        ];
    }
}
