<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Models\Municipality;

class MunicipalityController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return Municipality::class;
    }

    protected function routeKey(): string
    {
        return 'municipalities';
    }

    /**
     * @return array{title: string, singular: string, plural: string, intro: string}
     */
    protected function labels(): array
    {
        return [
            'title' => 'Communes couvertes',
            'singular' => 'la commune',
            'plural' => 'communes',
            'intro' => 'Ces communes alimentent les listes déroulantes des formulaires et la zone d’intervention affichée sur la page d’accueil.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('name', 'Nom de la commune')->listed()->searchable(),
            Field::text('slug', 'Adresse', ['nullable', 'string', 'max:170']),
            Field::text('postal_code', 'Code postal', ['required', 'string', 'max:10'])->listed()->searchable(),
            Field::text('insee_code', 'Code INSEE', ['nullable', 'string', 'max:10'],
                'Facultatif, utile pour les bilans transmis aux collectivités.'),
            Field::text('department', 'Département', ['nullable', 'string', 'max:100']),
            Field::number('distance_km', 'Distance depuis la commune de rattachement (km)',
                ['nullable', 'integer', 'min:0', 'max:500'],
                'Sert à déterminer les éventuels frais de déplacement.')->listed(),
            Field::boolean('is_covered', 'Commune desservie', default: true)->listed(),
            Field::boolean('home_visits_available', 'Déplacement à domicile possible', default: true),
            Field::textarea('notes', 'Notes internes', ['nullable', 'string', 'max:1000']),
            Field::number('position', 'Ordre d’affichage'),
        ];
    }
}
