<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Enums\ContentStatus;
use App\Models\Page;

class PageController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return Page::class;
    }

    protected function routeKey(): string
    {
        return 'pages';
    }

    /**
     * @return array{title: string, singular: string, plural: string, intro: string}
     */
    protected function labels(): array
    {
        return [
            'title' => 'Pages du site',
            'singular' => 'la page',
            'plural' => 'pages',
            'intro' => 'Les pages légales sont marquées « page système » : vous pouvez les modifier, mais pas les supprimer, car le pied de page y renvoie.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('title', 'Titre de la page')->listed()->searchable(),
            Field::text('slug', 'Adresse de la page', ['nullable', 'string', 'max:220']),
            Field::textarea('summary', 'Résumé', ['nullable', 'string', 'max:400'], rows: 3),
            Field::richText('body', 'Contenu'),
            Field::boolean('show_in_footer', 'Afficher le lien dans le pied de page')->listed(),
            Field::boolean('noindex', 'Demander aux moteurs de ne pas indexer cette page'),
            Field::select('status', 'Statut', ContentStatus::options())->listed(),
            Field::number('position', 'Ordre dans le pied de page')->listed(),
            Field::text('meta_title', 'Titre pour les moteurs de recherche', ['nullable', 'string', 'max:180']),
            Field::text('meta_description', 'Description pour les moteurs de recherche', ['nullable', 'string', 'max:255']),
        ];
    }
}
