<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\ArticleCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Rubrique du centre de ressources, partagée par les articles et les fiches
 * pratiques afin d'éviter deux arborescences concurrentes.
 */
class ArticleCategory extends Model
{
    /** @use HasFactory<ArticleCategoryFactory> */
    use Auditable, HasFactory, HasSlug, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'name',
        'slug',
        'summary',
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

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function guides(): HasMany
    {
        return $this->hasMany(PracticalGuide::class);
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
