<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Article de conseils pratiques.
 */
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use Auditable, HasFactory, HasSlug, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'article_category_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'image_path',
        'image_alt',
        'status',
        'is_featured',
        'published_at',
        'reading_minutes',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'reading_minutes' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(DownloadableFile::class, 'attachable')->orderBy('position');
    }

    /**
     * Articles réellement visibles : publiés et dont la date de parution est
     * atteinte.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * Durée de lecture estimée, calculée si elle n'a pas été saisie.
     */
    public function readingMinutes(): int
    {
        if ($this->reading_minutes !== null) {
            return $this->reading_minutes;
        }

        // 180 mots par minute : rythme prudent pour un public senior.
        return max(1, (int) ceil(str_word_count(strip_tags($this->body)) / 180));
    }
}
