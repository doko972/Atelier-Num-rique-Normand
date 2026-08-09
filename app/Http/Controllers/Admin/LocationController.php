<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Models\Location;
use App\Models\Municipality;

class LocationController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return Location::class;
    }

    protected function routeKey(): string
    {
        return 'locations';
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    /**
     * @return array<int, string>
     */
    protected function listRelations(): array
    {
        return ['municipality'];
    }

    /**
     * @return array{title: string, singular: string, plural: string, intro: string}
     */
    protected function labels(): array
    {
        return [
            'title' => 'Lieux d’accueil',
            'singular' => 'le lieu',
            'plural' => 'lieux',
            'intro' => 'Renseignez soigneusement l’accessibilité : « Les locaux sont-ils accessibles aux personnes à mobilité réduite ? » est l’une des questions les plus posées.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('name', 'Nom du lieu')->listed()->searchable(),
            Field::text('slug', 'Adresse', ['nullable', 'string', 'max:200']),
            Field::select(
                'municipality_id',
                'Commune',
                Municipality::query()->ordered()->pluck('name', 'id')->all(),
                ['nullable', 'integer', 'exists:municipalities,id'],
            ),
            Field::text('address_line', 'Adresse postale', ['nullable', 'string', 'max:255'])->searchable(),
            Field::text('postal_code', 'Code postal', ['nullable', 'string', 'max:10']),
            Field::text('city', 'Ville', ['nullable', 'string', 'max:150'])->listed(),
            Field::text('phone', 'Téléphone du lieu', ['nullable', 'string', 'max:30']),
            Field::boolean('is_accessible', 'Accessible aux personnes à mobilité réduite')->listed(),
            Field::textarea('accessibility_details', 'Précisions sur l’accessibilité',
                ['nullable', 'string', 'max:1000'],
                'Exemple : « Rampe d’accès à l’entrée, ascenseur, toilettes adaptées ».'),
            Field::textarea('access_notes', 'Comment s’y rendre', ['nullable', 'string', 'max:1000'],
                'Arrêt de bus le plus proche, stationnement, étage.'),
            Field::boolean('is_active', 'Lieu actif', default: true)->listed(),
        ];
    }
}
