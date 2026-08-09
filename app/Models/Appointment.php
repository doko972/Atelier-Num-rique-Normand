<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\ContactPreference;
use App\Enums\DeviceType;
use App\Models\Concerns\Anonymisable;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Demande de rendez-vous (codex §11).
 *
 * Le visiteur propose ses disponibilités ; le conseiller confirme ensuite
 * manuellement. Aucun agenda automatisé n'est nécessaire en première version.
 */
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use Anonymisable, Auditable, HasFactory, HasReference, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'municipality_id',
        'municipality_name',
        'type',
        'need_description',
        'device',
        'availability',
        'contact_preference',
        'home_visit_requested',
        'has_mobility_difficulty',
        'voice_message_allowed',
        'status',
        'assigned_to',
        'callback_on',
        'scheduled_for',
        'location_id',
        'consent_given',
        'consent_given_at',
        'source',
    ];

    /**
     * Données personnelles tenues hors du journal d'audit.
     *
     * @return array<int, string>
     */
    protected function auditHiddenFields(): array
    {
        return [
            'first_name',
            'last_name',
            'phone',
            'email',
            'need_description',
            'availability',
            'municipality_name',
        ];
    }

    protected function casts(): array
    {
        return [
            'type' => AppointmentType::class,
            'status' => AppointmentStatus::class,
            'device' => DeviceType::class,
            'contact_preference' => ContactPreference::class,
            'home_visit_requested' => 'boolean',
            'has_mobility_difficulty' => 'boolean',
            'voice_message_allowed' => 'boolean',
            'consent_given' => 'boolean',
            'callback_on' => 'date',
            'scheduled_for' => 'datetime',
            'consent_given_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
            'closed_at' => 'datetime',
            'anonymised_at' => 'datetime',
        ];
    }

    public function referencePrefix(): string
    {
        return 'RDV';
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withTrashed();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(AppointmentNote::class)->latest();
    }

    public function consentLogs(): MorphMany
    {
        return $this->morphMany(ConsentLog::class, 'consentable');
    }

    // -------------------------------------------------------------------------
    // Comportement
    // -------------------------------------------------------------------------

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function canReceiveEmail(): bool
    {
        return filled($this->email) && ! $this->isAnonymised();
    }

    /**
     * Le rappel prévu est-il en retard ?
     */
    public function isCallbackOverdue(): bool
    {
        return $this->callback_on !== null
            && $this->status->isOpen()
            && $this->callback_on->isPast();
    }

    /**
     * @return array<string, mixed>
     */
    public function anonymisedAttributes(): array
    {
        return [
            'first_name' => __('rgpd.anonymised_first_name'),
            'last_name' => '',
            'phone' => '',
            'email' => null,
            'need_description' => __('rgpd.anonymised_content'),
            'availability' => null,
            'municipality_name' => null,
            'ip_hash' => null,
        ];
    }

    // -------------------------------------------------------------------------
    // Portées
    // -------------------------------------------------------------------------

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AppointmentStatus::New,
            AppointmentStatus::ToCall,
            AppointmentStatus::WaitingInformation,
            AppointmentStatus::Proposed,
            AppointmentStatus::Confirmed,
        ]);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AppointmentStatus::New,
            AppointmentStatus::ToCall,
        ]);
    }

    public function auditLabel(): string
    {
        return $this->reference;
    }
}
