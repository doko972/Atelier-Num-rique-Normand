<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DataRequestStatus;
use App\Enums\DataRequestType;
use App\Models\Concerns\HasReference;
use Database\Factories\DataExportRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande d'accès, de rectification ou de portabilité (codex §27).
 *
 * Le délai de réponse est d'un mois à compter de la réception ; l'archive
 * produite expire automatiquement pour ne pas laisser traîner de données.
 */
class DataExportRequest extends Model
{
    /** @use HasFactory<DataExportRequestFactory> */
    use HasFactory, HasReference;

    public const int RESPONSE_DEADLINE_DAYS = 30;

    public const int EXPORT_LIFETIME_DAYS = 7;

    protected $fillable = [
        'type',
        'requester_name',
        'requester_email',
        'requester_phone',
        'details',
        'status',
        'identity_verified',
        'identity_verified_at',
        'handled_by',
        'export_path',
        'export_expires_at',
        'completed_at',
        'due_on',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => DataRequestType::class,
            'status' => DataRequestStatus::class,
            'identity_verified' => 'boolean',
            'identity_verified_at' => 'datetime',
            'export_expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'due_on' => 'date',
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
        return 'EXP';
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by')->withTrashed();
    }

    /**
     * Le délai légal d'un mois est-il dépassé ?
     */
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
}
