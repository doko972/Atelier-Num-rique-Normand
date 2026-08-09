<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\ServiceCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Grande famille de services (« Premiers pas », « Smartphone »…).
 */
class ServiceCategory extends Model
{
    /** @use HasFactory<ServiceCategoryFactory> */
    use Auditable, HasFactory, HasSlug, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'name',
        'slug',
        'summary',
        'description',
        'icon',
        'status',
        'position',
        'meta_title',
        'meta_description',
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

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('position');
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
