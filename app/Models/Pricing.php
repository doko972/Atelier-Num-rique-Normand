<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\PricingModel;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\PricingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Number;

/**
 * Tarif administrable (codex §16).
 *
 * Les montants sont stockés en centimes : aucun calcul monétaire n'est confié
 * à un nombre à virgule flottante.
 */
class Pricing extends Model
{
    /** @use HasFactory<PricingFactory> */
    use Auditable, HasFactory, HasSlug, SoftDeletes, TracksAuthor;

    protected $table = 'pricings';

    protected $fillable = [
        'label',
        'slug',
        'model',
        'amount_cents',
        'unit',
        'duration_minutes',
        'description',
        'includes',
        'travel_costs',
        'payment_methods',
        'cancellation_policy',
        'is_quote_only',
        'is_highlighted',
        'status',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'model' => PricingModel::class,
            'status' => ContentStatus::class,
            'includes' => 'array',
            'amount_cents' => 'integer',
            'duration_minutes' => 'integer',
            'position' => 'integer',
            'is_quote_only' => 'boolean',
            'is_highlighted' => 'boolean',
        ];
    }

    public function slugSourceField(): string
    {
        return 'label';
    }

    /**
     * Montant formaté en euros, ou mention « Sur devis ».
     */
    public function formattedAmount(): string
    {
        if ($this->is_quote_only || $this->amount_cents === null) {
            return __('site.pricing.on_quote');
        }

        return Number::currency($this->amount_cents / 100, 'EUR', 'fr');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('label');
    }
}
