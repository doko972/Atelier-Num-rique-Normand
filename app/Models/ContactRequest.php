<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactPreference;
use App\Enums\RequestStatus;
use App\Models\Concerns\Anonymisable;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasReference;
use Database\Factories\ContactRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Message envoyé depuis le formulaire de contact.
 */
class ContactRequest extends Model
{
    /** @use HasFactory<ContactRequestFactory> */
    use Anonymisable, Auditable, HasFactory, HasReference, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'municipality_id',
        'subject',
        'message',
        'contact_preference',
        'voice_message_allowed',
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
            'first_name',
            'last_name',
            'phone',
            'email',
            'message',
            'internal_notes',
        ];
    }

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'contact_preference' => ContactPreference::class,
            'voice_message_allowed' => 'boolean',
            'consent_given' => 'boolean',
            'consent_given_at' => 'datetime',
            'answered_at' => 'datetime',
            'closed_at' => 'datetime',
            'anonymised_at' => 'datetime',
        ];
    }

    public function referencePrefix(): string
    {
        return 'MSG';
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

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
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
            'first_name' => __('rgpd.anonymised_first_name'),
            'last_name' => null,
            'phone' => null,
            'email' => null,
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
        return $query->whereIn('status', [RequestStatus::New, RequestStatus::InProgress]);
    }

    public function auditLabel(): string
    {
        return $this->reference;
    }
}
