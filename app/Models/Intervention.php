<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InterventionActivity;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\TracksAuthor;
use App\Support\FrenchFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Prestation réalisée et facturée : une ligne de la comptabilité séparée
 * SAP / hors SAP.
 *
 * C'est une pièce comptable, pas une demande : elle n'est ni anonymisée ni
 * effacée avec le rendez-vous dont elle est issue. Sa conservation repose
 * sur l'obligation légale de tenir une comptabilité (dix ans).
 */
class Intervention extends Model
{
    use Auditable, HasReference, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'performed_on',
        'activity',
        'client_name',
        'appointment_id',
        'duration_minutes',
        'amount_cents',
        'invoice_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'performed_on' => 'date',
            'activity' => InterventionActivity::class,
            'duration_minutes' => 'integer',
            'amount_cents' => 'integer',
        ];
    }

    /**
     * Le journal d'audit garde les montants, jamais l'identité du client.
     *
     * @return array<int, string>
     */
    protected function auditHiddenFields(): array
    {
        return ['client_name', 'notes'];
    }

    public function referencePrefix(): string
    {
        return 'PRE';
    }

    public function auditLabel(): string
    {
        return $this->reference;
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class)->withTrashed();
    }

    public function isSap(): bool
    {
        return $this->activity->isSap();
    }

    public function formattedAmount(): string
    {
        return FrenchFormat::money($this->amount_cents);
    }

    /**
     * Montant au format de saisie : « 35,00 ».
     */
    public function amountForInput(): ?string
    {
        return $this->amount_cents === null ? null : FrenchFormat::decimal($this->amount_cents);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInYear(Builder $query, int $year): Builder
    {
        return $query->whereBetween('performed_on', ["{$year}-01-01", "{$year}-12-31"]);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSap(Builder $query, bool $sap = true): Builder
    {
        $values = collect($sap ? InterventionActivity::sapCases() : InterventionActivity::otherCases())
            ->map(fn (InterventionActivity $case): string => $case->value)
            ->all();

        return $query->whereIn('activity', $values);
    }
}
