<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Enums\ContentStatus;
use App\Enums\PricingModel;
use App\Models\Pricing;

class PricingController extends AbstractResourceController
{
    protected function modelClass(): string
    {
        return Pricing::class;
    }

    protected function routeKey(): string
    {
        return 'pricings';
    }

    /**
     * @return array{title: string, singular: string, plural: string, intro: string}
     */
    protected function labels(): array
    {
        return [
            'title' => 'Tarifs',
            'singular' => 'le tarif',
            'plural' => 'tarifs',
            'intro' => 'Indiquez toujours ce qui est inclus, la durée et les éventuels frais de déplacement. N’affichez aucun avantage fiscal sans validation juridique.',
        ];
    }

    /**
     * @return array<int, Field>
     */
    protected function fields(): array
    {
        return [
            Field::text('label', 'Intitulé du tarif')->listed()->searchable(),
            Field::text('slug', 'Adresse', ['nullable', 'string', 'max:200']),
            Field::select('model', 'Type de tarif', PricingModel::options())->listed(),
            Field::number('amount_cents', 'Montant en centimes', ['nullable', 'integer', 'min:0', 'max:1000000'],
                'Exemple : 2500 pour 25,00 €. Laissez vide pour un tarif sur devis.')
                ->listed('money'),
            Field::text('unit', 'Unité', ['nullable', 'string', 'max:80'],
                'Exemple : « par heure », « la séance », « par kilomètre ».'),
            Field::number('duration_minutes', 'Durée (minutes)'),
            Field::textarea('description', 'Description', ['nullable', 'string', 'max:1000']),
            Field::lines('includes', 'Ce qui est inclus', 'Un élément par ligne.'),
            Field::textarea('travel_costs', 'Frais de déplacement', ['nullable', 'string', 'max:600']),
            Field::textarea('payment_methods', 'Moyens de paiement acceptés', ['nullable', 'string', 'max:600']),
            Field::textarea('cancellation_policy', 'Conditions d’annulation', ['nullable', 'string', 'max:600']),
            Field::boolean('is_quote_only', 'Uniquement sur devis'),
            Field::boolean('is_highlighted', 'Mettre en avant'),
            Field::select('status', 'Statut', ContentStatus::options())->listed(),
            Field::number('position', 'Ordre d’affichage')->listed(),
        ];
    }
}
