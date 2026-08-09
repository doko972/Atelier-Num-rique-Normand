<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Enums\SkillLevel;
use App\Enums\WorkshopStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksAuthor;
use Carbon\CarbonImmutable;
use Database\Factories\WorkshopFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Number;

/**
 * Atelier collectif (codex §10).
 *
 * Le nombre de places restantes n'est jamais stocké : il est toujours déduit
 * des inscriptions, ce qui écarte tout risque de désynchronisation.
 */
class Workshop extends Model
{
    /** @use HasFactory<WorkshopFactory> */
    use Auditable, HasFactory, HasSlug, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'workshop_category_id',
        'location_id',
        'municipality_id',
        'partner_id',
        'title',
        'slug',
        'description',
        'objectives',
        'prerequisites',
        'level',
        'date',
        'start_time',
        'end_time',
        'registration_deadline',
        'capacity',
        'waiting_list_enabled',
        'is_accessible',
        'equipment_provided',
        'own_device_allowed',
        'is_free',
        'price_cents',
        'instructor_name',
        'image_path',
        'image_alt',
        'status',
        'cancellation_reason',
        'published_at',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'objectives' => 'array',
            'level' => SkillLevel::class,
            'status' => WorkshopStatus::class,
            'date' => 'date',
            'registration_deadline' => 'date',
            'published_at' => 'datetime',
            'capacity' => 'integer',
            'price_cents' => 'integer',
            'waiting_list_enabled' => 'boolean',
            'is_accessible' => 'boolean',
            'equipment_provided' => 'boolean',
            'own_device_allowed' => 'boolean',
            'is_free' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkshopCategory::class, 'workshop_category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(WorkshopRegistration::class);
    }

    /**
     * Inscriptions qui occupent réellement une place.
     */
    public function activeRegistrations(): HasMany
    {
        return $this->registrations()->whereIn(
            'status',
            array_map(
                fn (RegistrationStatus $status): string => $status->value,
                array_filter(
                    RegistrationStatus::cases(),
                    fn (RegistrationStatus $status): bool => $status->occupiesSeat(),
                ),
            ),
        );
    }

    public function waitingList(): HasMany
    {
        return $this->registrations()
            ->where('status', RegistrationStatus::WaitingList)
            ->orderBy('waiting_position');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(DownloadableFile::class, 'attachable')->orderBy('position');
    }

    // -------------------------------------------------------------------------
    // Places
    // -------------------------------------------------------------------------

    /**
     * Nombre de places occupées.
     */
    public function occupiedSeats(): int
    {
        // Utilise le compteur déjà chargé lorsqu'il est disponible, pour
        // éviter une requête par atelier dans les listes.
        $counted = $this->getAttributes()['active_registrations_count'] ?? null;

        return (int) ($counted ?? $this->activeRegistrations()->count());
    }

    /**
     * Nombre de places encore disponibles, jamais négatif.
     */
    public function remainingSeats(): int
    {
        return max(0, $this->capacity - $this->occupiedSeats());
    }

    public function isFull(): bool
    {
        return $this->remainingSeats() === 0;
    }

    /**
     * La date limite d'inscription est-elle dépassée ?
     */
    public function registrationDeadlinePassed(): bool
    {
        $deadline = $this->registration_deadline ?? $this->date;

        return CarbonImmutable::today()->greaterThan($deadline);
    }

    public function isPast(): bool
    {
        return $this->startsAt()->isPast();
    }

    /**
     * Peut-on s'inscrire immédiatement (une place est libre) ?
     */
    public function registrationsOpen(): bool
    {
        return $this->status->acceptsRegistrations()
            && ! $this->registrationDeadlinePassed()
            && ! $this->isFull();
    }

    /**
     * À défaut de place, peut-on rejoindre la liste d'attente ?
     */
    public function waitingListOpen(): bool
    {
        return $this->waiting_list_enabled
            && $this->status->acceptsRegistrations()
            && ! $this->registrationDeadlinePassed()
            && $this->isFull();
    }

    /**
     * Date et heure de début, dans le fuseau de l'application.
     */
    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $this->date->format('Y-m-d').' '.$this->formatTime($this->start_time),
        );
    }

    public function endsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $this->date->format('Y-m-d').' '.$this->formatTime($this->end_time),
        );
    }

    public function durationMinutes(): int
    {
        return (int) $this->startsAt()->diffInMinutes($this->endsAt());
    }

    public function formattedPrice(): string
    {
        if ($this->is_free || $this->price_cents === null || $this->price_cents === 0) {
            return __('site.workshops.free');
        }

        return Number::currency($this->price_cents / 100, 'EUR', 'fr');
    }

    /**
     * Normalise une heure quelle que soit sa représentation renvoyée par le
     * pilote de base de données (`H:i:s` ou objet date).
     */
    protected function formatTime(mixed $time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i:s');
        }

        return (string) $time;
    }

    // -------------------------------------------------------------------------
    // Portées
    // -------------------------------------------------------------------------

    /**
     * Ateliers visibles par le public.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->whereIn('status', [
            WorkshopStatus::Published,
            WorkshopStatus::Full,
            WorkshopStatus::Cancelled,
        ]);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('date', '>=', CarbonImmutable::today())
            ->orderBy('date')
            ->orderBy('start_time');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->whereDate('date', '<', CarbonImmutable::today())
            ->orderByDesc('date');
    }
}
