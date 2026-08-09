<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PartnerType;
use App\Enums\RequestStatus;
use App\Models\Concerns\Anonymisable;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasReference;
use Database\Factories\PartnershipRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Demande émanant d'une commune, d'un CCAS ou d'une association (codex §15).
 *
 * Les coordonnées collectées sont professionnelles ; elles restent néanmoins
 * des données personnelles et suivent les mêmes règles de conservation.
 */
class PartnershipRequest extends Model
{
    /** @use HasFactory<PartnershipRequestFactory> */
    use Anonymisable, Auditable, HasFactory, HasReference, SoftDeletes;

    protected $fillable = [
        'organisation_name',
        'organisation_type',
        'contact_name',
        'contact_role',
        'email',
        'phone',
        'municipality_id',
        'municipality_name',
        'needs',
        'audience',
        'estimated_participants',
        'desired_period',
        'message',
        'quote_requested',
        'status',
        'assigned_to',
        'internal_notes',
        'consent_given',
        'consent_given_at',
    ];

    /**
     * @return array<int, string>
     */
    protected function auditHiddenFields(): array
    {
        return [
            'contact_name',
            'email',
            'phone',
            'message',
            'internal_notes',
        ];
    }

    protected function casts(): array
    {
        return [
            'organisation_type' => PartnerType::class,
            'status' => RequestStatus::class,
            'needs' => 'array',
            'quote_requested' => 'boolean',
            'consent_given' => 'boolean',
            'estimated_participants' => 'integer',
            'consent_given_at' => 'datetime',
            'answered_at' => 'datetime',
            'closed_at' => 'datetime',
            'anonymised_at' => 'datetime',
        ];
    }

    public function referencePrefix(): string
    {
        return 'PAR';
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withTrashed();
    }

    public function consentLogs(): MorphMany
    {
        return $this->morphMany(ConsentLog::class, 'consentable');
    }

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
            'contact_name' => __('rgpd.anonymised_first_name'),
            'contact_role' => null,
            'email' => __('rgpd.anonymised_email'),
            'phone' => null,
            'message' => __('rgpd.anonymised_content'),
            'internal_notes' => null,
            'ip_hash' => null,
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            RequestStatus::New,
            RequestStatus::InProgress,
            RequestStatus::QuoteSent,
        ]);
    }

    public function auditLabel(): string
    {
        return $this->reference;
    }
}
