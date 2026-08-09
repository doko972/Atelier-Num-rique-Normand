<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Suivi d'une demande RGPD. Le délai légal de réponse est d'un mois.
 */
enum DataRequestStatus: string
{
    use HasLabel;

    case Received = 'received';
    case IdentityCheck = 'identity_check';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Refused = 'refused';

    public function isOpen(): bool
    {
        return in_array(
            $this,
            [self::Received, self::IdentityCheck, self::InProgress],
            strict: true,
        );
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Received => 'danger',
            self::IdentityCheck, self::InProgress => 'warning',
            self::Completed => 'success',
            self::Refused => 'neutral',
        };
    }
}
