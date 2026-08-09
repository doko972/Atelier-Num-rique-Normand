<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Moyen par lequel la personne souhaite être recontactée.
 */
enum ContactPreference: string
{
    use HasLabel;

    case Phone = 'phone';
    case Sms = 'sms';
    case Email = 'email';

    /**
     * Ce moyen nécessite-t-il une adresse électronique ?
     */
    public function requiresEmail(): bool
    {
        return $this === self::Email;
    }
}
