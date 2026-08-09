<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Enums\ContentStatus;
use App\Enums\SkillLevel;
use App\Models\Service;
use App\Models\ServiceCategory;

class ServiceController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return Service::class;
    }

    protected function routeKey(): string
    {
        return 'services';
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
            'title' => 'Services proposés',
            'singular' => 'le service',
            'plural' => 'services',
            'intro' => 'Décrivez chaque accompagnement avec des mots simples, comme vous l’expliqueriez de vive voix.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::select(
                'service_category_id',
                'Famille de services',
                ServiceCategory::query()->ordered()->pluck('name', 'id')->all(),
            ),
            Field::text('title', 'Titre du service')->listed()->searchable(),
            Field::text('slug', 'Adresse de la page', ['nullable', 'string', 'max:200']),
            Field::text('summary', 'Résumé', ['required', 'string', 'max:255'],
                'Une phrase, sans jargon. Exemple : « Apprendre à envoyer une photo à vos petits-enfants ».')
                ->searchable(),
            Field::richText('description', 'Description détaillée', ['nullable', 'string']),
            Field::lines('learning_points', 'Ce que vous apprendrez',
                'Une idée par ligne. Exemple : « Ouvrir votre boîte aux lettres électronique ».'),
            Field::select('level', 'Niveau', SkillLevel::options(), ['required']),
            Field::number('estimated_duration_minutes', 'Durée indicative (minutes)'),
            Field::text('icon', 'Nom de l’icône', ['nullable', 'string', 'max:60']),
            Field::text('image_alt', 'Description de l’image', ['nullable', 'string', 'max:255'],
                'Décrivez l’image pour les personnes qui ne la voient pas.'),
            Field::boolean('is_featured', 'Mettre en avant sur la page d’accueil')->listed(),
            Field::select('status', 'Statut', ContentStatus::options())->listed(),
            Field::number('position', 'Ordre d’affichage')->listed(),
            Field::text('meta_title', 'Titre pour les moteurs de recherche', ['nullable', 'string', 'max:180']),
            Field::text('meta_description', 'Description pour les moteurs de recherche', ['nullable', 'string', 'max:255']),
        ];
    }
}
