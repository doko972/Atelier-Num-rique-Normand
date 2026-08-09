<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\WorkshopCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Thématique d'atelier collectif.
 */
class WorkshopCategory extends Model
{
    /** @use HasFactory<WorkshopCategoryFactory> */
    use Auditable, HasFactory, HasSlug, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'name',
        'slug',
        'summary',
        'icon',
        'status',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'position' => 'integer',
        ];
    }

    public function slugSourceField(): string
    {
        return 'name';
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
        return $query->where('status', ContentStatus::Published);
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
