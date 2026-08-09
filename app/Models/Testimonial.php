<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Témoignage d'une personne accompagnée.
 *
 * Un témoignage n'est publiable qu'avec l'accord explicite de son auteur, et
 * l'identité peut se limiter à un prénom ou à une initiale.
 */
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use Auditable, HasFactory, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'quote',
        'author_name',
        'author_context',
        'municipality_id',
        'status',
        'is_featured',
        'position',
        'collected_on',
        'publication_consent',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'is_featured' => 'boolean',
            'publication_consent' => 'boolean',
            'position' => 'integer',
            'collected_on' => 'date',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * Un témoignage sans accord de publication n'apparaît jamais en ligne,
     * même si son statut a été passé à « publié » par erreur.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published)
            ->where('publication_consent', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderByDesc('collected_on');
    }

    /**
     * Signature affichée sous la citation.
     */
    public function attribution(): string
    {
        return collect([
            $this->author_name ?: __('site.testimonials.anonymous'),
            $this->author_context,
            $this->municipality?->name,
        ])->filter()->implode(', ');
    }

    public function auditLabel(): string
    {
        return str($this->quote)->limit(60)->toString();
    }
}
