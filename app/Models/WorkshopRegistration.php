<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgeRange;
use App\Enums\DeviceType;
use App\Enums\RegistrationStatus;
use App\Models\Concerns\Anonymisable;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasReference;
use Database\Factories\WorkshopRegistrationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Inscription à un atelier.
 *
 * L'adresse électronique reste facultative : une personne qui n'en possède pas
 * doit pouvoir s'inscrire, par le formulaire ou par téléphone (codex §46).
 */
class WorkshopRegistration extends Model
{
    /** @use HasFactory<WorkshopRegistrationFactory> */
    use Anonymisable, Auditable, HasFactory, HasReference, SoftDeletes;

    protected $fillable = [
        'workshop_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'municipality_id',
        'municipality_name',
        'age_range',
        'device',
        'special_needs',
        'status',
        'waiting_position',
        'registered_by_phone',
        'registered_by',
        'consent_given',
        'consent_given_at',
        'voice_message_allowed',
        'internal_notes',
    ];

    /**
     * Le journal d'audit ne conserve que la référence et le statut : jamais
     * l'identité ni les coordonnées de la personne inscrite.
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
            'special_needs',
            'internal_notes',
            'municipality_name',
        ];
    }

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'age_range' => AgeRange::class,
            'device' => DeviceType::class,
            'waiting_position' => 'integer',
            'registered_by_phone' => 'boolean',
            'consent_given' => 'boolean',
            'voice_message_allowed' => 'boolean',
            'consent_given_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'anonymised_at' => 'datetime',
        ];
    }

    public function referencePrefix(): string
    {
        return 'ATE';
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by')->withTrashed();
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

    /**
     * Une confirmation par courrier électronique n'est possible que si une
     * adresse a été fournie ; sinon le conseiller rappelle la personne.
     */
    public function canReceiveEmail(): bool
    {
        return filled($this->email) && ! $this->isAnonymised();
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
            'special_needs' => null,
            'internal_notes' => null,
            'municipality_name' => null,
            'ip_hash' => null,
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOccupyingSeat(Builder $query): Builder
    {
        return $query->whereIn('status', [
            RegistrationStatus::Pending,
            RegistrationStatus::Confirmed,
            RegistrationStatus::Attended,
        ]);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', RegistrationStatus::WaitingList)
            ->orderBy('waiting_position');
    }

    public function auditLabel(): string
    {
        return $this->reference;
    }
}
