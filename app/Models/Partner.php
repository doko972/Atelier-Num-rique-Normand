<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PartnerType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\PartnerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Structure partenaire affichée sur le site.
 */
class Partner extends Model
{
    /** @use HasFactory<PartnerFactory> */
    use Auditable, HasFactory, HasSlug, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'municipality_id',
        'name',
        'slug',
        'type',
        'logo_path',
        'logo_alt',
        'website',
        'description',
        'is_published',
        'position',
        'partnership_started_on',
    ];

    protected function casts(): array
    {
        return [
            'type' => PartnerType::class,
            'is_published' => 'boolean',
            'position' => 'integer',
            'partnership_started_on' => 'date',
        ];
    }

    public function slugSourceField(): string
    {
        return 'name';
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }
}
