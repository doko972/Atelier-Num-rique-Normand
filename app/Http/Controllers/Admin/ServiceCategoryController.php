<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Enums\ContentStatus;
use App\Models\ServiceCategory;

class ServiceCategoryController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return ServiceCategory::class;
    }

    protected function routeKey(): string
    {
        return 'service-categories';
    }

    /**
     * @return array{title: string, singular: string, plural: string, intro: string}
     */
    protected function labels(): array
    {
        return [
            'title' => 'Familles de services',
            'singular' => 'la famille de services',
            'plural' => 'familles de services',
            'intro' => 'Regroupez les services par grande famille : « Premiers pas », « Smartphone », « Démarches »…',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('name', 'Nom de la famille')->listed()->searchable(),
            Field::text('slug', 'Adresse de la page', ['nullable', 'string', 'max:170'],
                'Laissez vide : elle sera créée automatiquement à partir du nom.'),
            Field::text('summary', 'Résumé court', ['nullable', 'string', 'max:255'],
                'Une phrase simple affichée sous le titre.')->searchable(),
            Field::textarea('description', 'Description'),
            Field::text('icon', 'Nom de l’icône', ['nullable', 'string', 'max:60'],
                'Parmi : ordinateur, telephone, internet, courriel, administration, famille, securite, photo.'),
            Field::select('status', 'Statut', ContentStatus::options())->listed(),
            Field::number('position', 'Ordre d’affichage', ['nullable', 'integer', 'min:0'],
                'Le plus petit nombre apparaît en premier.')->listed(),
            Field::text('meta_title', 'Titre pour les moteurs de recherche', ['nullable', 'string', 'max:180']),
            Field::text('meta_description', 'Description pour les moteurs de recherche', ['nullable', 'string', 'max:255']),
        ];
    }
}
