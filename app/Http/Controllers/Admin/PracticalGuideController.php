<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Enums\ContentStatus;
use App\Enums\SkillLevel;
use App\Models\ArticleCategory;
use App\Models\PracticalGuide;

class PracticalGuideController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return PracticalGuide::class;
    }

    protected function routeKey(): string
    {
        return 'guides';
    }

    protected function defaultSort(): string
    {
        return 'title';
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
            'title' => 'Fiches pratiques',
            'singular' => 'la fiche pratique',
            'plural' => 'fiches pratiques',
            'intro' => 'Les étapes de chaque fiche se gèrent depuis le bouton « Étapes » de la liste.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('title', 'Titre de la fiche')->listed()->searchable(),
            Field::text('slug', 'Adresse de la page', ['nullable', 'string', 'max:220']),
            Field::select(
                'article_category_id',
                'Rubrique',
                ArticleCategory::query()->ordered()->pluck('name', 'id')->all(),
                ['nullable', 'integer', 'exists:article_categories,id'],
            ),
            Field::textarea('summary', 'Résumé', ['required', 'string', 'max:400'], rows: 3)->searchable(),
            Field::richText('introduction', 'Introduction', ['nullable', 'string']),
            Field::select('level', 'Niveau', SkillLevel::options(), ['required'])->listed(),
            Field::number('estimated_minutes', 'Durée estimée (minutes)'),
            Field::textarea('prerequisites', 'Ce qu’il faut avoir sous la main',
                ['nullable', 'string'],
                'Exemple : votre téléphone, votre numéro de sécurité sociale.'),
            Field::textarea('safety_notice', 'Encart de sécurité', ['nullable', 'string'],
                'Rappel affiché en évidence. Exemple : « Ne communiquez jamais votre mot de passe ».'),
            Field::richText('conclusion', 'Conclusion', ['nullable', 'string']),
            Field::text('image_alt', 'Description de l’image', ['nullable', 'string', 'max:255']),
            Field::date('published_at', 'Date de publication', ['nullable', 'date']),
            Field::date('reviewed_on', 'Dernière vérification du contenu', ['nullable', 'date'],
                'Les démarches changent : indiquez la date à laquelle vous avez revérifié la procédure.')
                ->listed(),
            Field::boolean('is_featured', 'Mettre en avant'),
            Field::select('status', 'Statut', ContentStatus::options())->listed(),
            Field::text('meta_title', 'Titre pour les moteurs de recherche', ['nullable', 'string', 'max:180']),
            Field::text('meta_description', 'Description pour les moteurs de recherche', ['nullable', 'string', 'max:255']),
        ];
    }
}
