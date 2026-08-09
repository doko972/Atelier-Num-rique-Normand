<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Enums\ContentStatus;
use App\Models\Municipality;
use App\Models\Testimonial;

class TestimonialController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return Testimonial::class;
    }

    protected function routeKey(): string
    {
        return 'testimonials';
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
            'title' => 'Témoignages',
            'singular' => 'le témoignage',
            'plural' => 'témoignages',
            'intro' => 'Un témoignage n’est publié que si la case d’accord de publication est cochée, même si son statut est « publié ».',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::textarea('quote', 'Témoignage', ['required', 'string', 'max:800'], rows: 4)
                ->listed()
                ->searchable(),
            Field::text('author_name', 'Prénom ou initiale', ['nullable', 'string', 'max:100'],
                'Laissez vide pour un témoignage anonyme.')->listed(),
            Field::text('author_context', 'Précision', ['nullable', 'string', 'max:150'],
                'Exemple : « retraitée », « aidant familial ».'),
            Field::select(
                'municipality_id',
                'Commune',
                Municipality::query()->ordered()->pluck('name', 'id')->all(),
                ['nullable', 'integer', 'exists:municipalities,id'],
            ),
            Field::date('collected_on', 'Recueilli le'),
            Field::boolean('publication_consent', 'La personne a donné son accord pour la publication')
                ->listed(),
            Field::boolean('is_featured', 'Mettre en avant'),
            Field::select('status', 'Statut', ContentStatus::options())->listed(),
            Field::number('position', 'Ordre d’affichage'),
        ];
    }
}
