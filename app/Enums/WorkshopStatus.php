<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Statuts d'un atelier collectif (codex §10).
 */
enum WorkshopStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Published = 'published';
    case Full = 'full';
    case Cancelled = 'cancelled';
    case Finished = 'finished';
    case Archived = 'archived';

    /**
     * L'atelier apparaît-il dans l'agenda public ?
     */
    public function isPublic(): bool
    {
        return in_array($this, [self::Published, self::Full, self::Cancelled], strict: true);
    }

    /**
     * Peut-on encore s'inscrire (sous réserve des places et de la date limite) ?
     */
    public function acceptsRegistrations(): bool
    {
        return in_array($this, [self::Published, self::Full], strict: true);
    }

    /**
     * Transitions autorisées depuis ce statut.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Published, self::Cancelled, self::Archived],
            self::Published => [self::Draft, self::Full, self::Cancelled, self::Finished],
            self::Full => [self::Published, self::Cancelled, self::Finished],
            self::Cancelled => [self::Draft, self::Archived],
            self::Finished => [self::Archived],
            self::Archived => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), strict: true);
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Published => 'success',
            self::Full => 'warning',
            self::Cancelled => 'danger',
            self::Finished, self::Archived => 'info',
        };
    }
}
