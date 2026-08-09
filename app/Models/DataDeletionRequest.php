<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DataRequestStatus;
use App\Models\Concerns\HasReference;
use Database\Factories\DataDeletionRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande d'effacement ou d'opposition (codex §27).
 *
 * L'exécution passe par une anonymisation : les statistiques agrégées restent
 * exactes, mais plus aucune donnée ne permet d'identifier la personne.
 */
class DataDeletionRequest extends Model
{
    /** @use HasFactory<DataDeletionRequestFactory> */
    use HasFactory, HasReference;

    public const int RESPONSE_DEADLINE_DAYS = 30;

    public const string SCOPE_ALL = 'all';

    public const string SCOPE_APPOINTMENTS = 'appointments';

    public const string SCOPE_REGISTRATIONS = 'registrations';

    public const string SCOPE_CONTACTS = 'contacts';

    protected $fillable = [
        'requester_name',
        'requester_email',
        'requester_phone',
        'details',
        'scope',
        'status',
        'identity_verified',
        'identity_verified_at',
        'handled_by',
        'records_anonymised',
        'completed_at',
        'due_on',
        'refusal_reason',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => DataRequestStatus::class,
            'identity_verified' => 'boolean',
            'identity_verified_at' => 'datetime',
            'completed_at' => 'datetime',
            'due_on' => 'date',
            'records_anonymised' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            $request->due_on ??= now()->addDays(self::RESPONSE_DEADLINE_DAYS)->toDateString();
        });
    }

    public function referencePrefix(): string
    {
        return 'SUP';
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by')->withTrashed();
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen()
            && $this->due_on !== null
            && $this->due_on->isPast();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            DataRequestStatus::Received,
            DataRequestStatus::IdentityCheck,
            DataRequestStatus::InProgress,
        ]);
    }

    /**
     * Libellés des périmètres proposés dans le formulaire d'administration.
     *
     * @return array<string, string>
     */
    public static function scopeOptions(): array
    {
        return [
            self::SCOPE_ALL => __('rgpd.scope.all'),
            self::SCOPE_APPOINTMENTS => __('rgpd.scope.appointments'),
            self::SCOPE_REGISTRATIONS => __('rgpd.scope.registrations'),
            self::SCOPE_CONTACTS => __('rgpd.scope.contacts'),
        ];
    }
}
