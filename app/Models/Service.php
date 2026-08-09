<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\SkillLevel;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Service d'accompagnement proposé aux habitants.
 */
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use Auditable, HasFactory, HasSlug, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'service_category_id',
        'title',
        'slug',
        'summary',
        'description',
        'learning_points',
        'icon',
        'image_path',
        'image_alt',
        'status',
        'is_featured',
        'position',
        'estimated_duration_minutes',
        'level',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'learning_points' => 'array',
            'status' => ContentStatus::class,
            'level' => SkillLevel::class,
            'is_featured' => 'boolean',
            'position' => 'integer',
            'estimated_duration_minutes' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
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
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('title');
    }
}
