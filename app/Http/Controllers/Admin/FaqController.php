<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Enums\ContentStatus;
use App\Models\Faq;

class FaqController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return Faq::class;
    }

    protected function routeKey(): string
    {
        return 'faqs';
    }

    /**
     * @return array{title: string, singular: string, plural: string, intro: string}
     */
    protected function labels(): array
    {
        return [
            'title' => 'Questions fréquentes',
            'singular' => 'la question',
            'plural' => 'questions',
            'intro' => 'Reprenez les questions que l’on vous pose vraiment au téléphone, avec les mots des personnes.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('question', 'Question', ['required', 'string', 'max:300'])->listed()->searchable(),
            Field::textarea('answer', 'Réponse', ['required', 'string', 'max:3000'],
                'Répondez en une ou deux phrases, sans terme technique.')->searchable(),
            Field::select('category', 'Regroupement', [
                'general' => 'Questions générales',
                'rendez-vous' => 'Rendez-vous',
                'ateliers' => 'Ateliers',
                'securite' => 'Sécurité et arnaques',
                'tarifs' => 'Tarifs',
                'accessibilite' => 'Accessibilité',
            ])->listed(),
            Field::boolean('is_featured', 'Mettre en avant sur la page d’accueil'),
            Field::select('status', 'Statut', ContentStatus::options())->listed(),
            Field::number('position', 'Ordre d’affichage')->listed(),
        ];
    }
}
