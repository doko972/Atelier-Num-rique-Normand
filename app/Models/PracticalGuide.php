<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\SkillLevel;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\PracticalGuideFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fiche pratique en étapes numérotées (codex §14).
 *
 * Chaque fiche est imprimable ; la date de dernière vérification est affichée
 * car une démarche administrative peut évoluer.
 */
class PracticalGuide extends Model
{
    /** @use HasFactory<PracticalGuideFactory> */
    use Auditable, HasFactory, HasSlug, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'article_category_id',
        'title',
        'slug',
        'summary',
        'introduction',
        'level',
        'estimated_minutes',
        'prerequisites',
        'safety_notice',
        'conclusion',
        'image_path',
        'image_alt',
        'status',
        'is_featured',
        'published_at',
        'reviewed_on',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'level' => SkillLevel::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'reviewed_on' => 'date',
            'estimated_minutes' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(GuideStep::class)->orderBy('position');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(DownloadableFile::class, 'attachable')->orderBy('position');
    }

    /**
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
     * Une fiche non vérifiée depuis plus d'un an est signalée en interne :
     * les procédures administratives changent souvent.
     */
    public function needsReview(): bool
    {
        return $this->reviewed_on === null
            || $this->reviewed_on->lt(now()->subYear());
    }
}
