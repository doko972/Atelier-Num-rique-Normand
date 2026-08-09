<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Enums\ContentStatus;
use App\Models\OfficialLink;

class OfficialLinkController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return OfficialLink::class;
    }

    protected function routeKey(): string
    {
        return 'official-links';
    }

    /**
     * @return array{title: string, singular: string, plural: string, intro: string}
     */
    protected function labels(): array
    {
        return [
            'title' => 'Liens vers les organismes officiels',
            'singular' => 'le lien',
            'plural' => 'liens',
            'intro' => 'Vérifiez régulièrement que ces adresses fonctionnent encore : un lien mort renvoie les personnes vers un moteur de recherche, où prospèrent les faux sites.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('label', 'Nom de l’organisme')->listed()->searchable(),
            Field::url('url', 'Adresse du site', ['required', 'url', 'max:255'])->listed(),
            Field::textarea('description', 'À quoi sert ce site ?', ['nullable', 'string', 'max:400'], rows: 3),
            Field::select('category', 'Rubrique', [
                OfficialLink::CATEGORY_SECURITY => 'Sécurité et signalement',
                OfficialLink::CATEGORY_PROCEDURES => 'Démarches administratives',
                OfficialLink::CATEGORY_SUPPORT => 'Aide et accompagnement',
            ])->listed(),
            Field::select('status', 'Statut', ContentStatus::options())->listed(),
            Field::number('position', 'Ordre d’affichage')->listed(),
        ];
    }
}
