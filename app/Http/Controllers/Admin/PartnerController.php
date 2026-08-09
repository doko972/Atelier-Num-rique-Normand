<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Enums\PartnerType;
use App\Models\Municipality;
use App\Models\Partner;

class PartnerController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return Partner::class;
    }

    protected function routeKey(): string
    {
        return 'partners';
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
            'title' => 'Partenaires',
            'singular' => 'le partenaire',
            'plural' => 'partenaires',
            'intro' => 'N’affichez un logo qu’avec l’accord de la structure, et renseignez toujours sa description pour les lecteurs d’écran.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('name', 'Nom de la structure')->listed()->searchable(),
            Field::text('slug', 'Adresse', ['nullable', 'string', 'max:200']),
            Field::select('type', 'Type de structure', PartnerType::options())->listed(),
            Field::select(
                'municipality_id',
                'Commune',
                Municipality::query()->ordered()->pluck('name', 'id')->all(),
                ['nullable', 'integer', 'exists:municipalities,id'],
            ),
            Field::url('website', 'Site internet'),
            Field::text('logo_alt', 'Description du logo', ['nullable', 'string', 'max:255'],
                'Exemple : « Logo de la mairie de Verson ».'),
            Field::textarea('description', 'Présentation', ['nullable', 'string', 'max:1000']),
            Field::date('partnership_started_on', 'Partenariat depuis le'),
            Field::boolean('is_published', 'Afficher sur le site', default: true)->listed(),
            Field::number('position', 'Ordre d’affichage')->listed(),
        ];
    }
}
