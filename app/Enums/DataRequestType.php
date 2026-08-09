<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Nature d'une demande fondée sur le RGPD (codex §27).
 */
enum DataRequestType: string
{
    use HasLabel;

    case Access = 'access';
    case Rectification = 'rectification';
    case Erasure = 'erasure';
    case Objection = 'objection';
    case Portability = 'portability';
    case ConsentWithdrawal = 'consent_withdrawal';

    /**
     * La demande aboutit-elle à une suppression ou une anonymisation ?
     */
    public function isDestructive(): bool
    {
        return in_array($this, [self::Erasure, self::ConsentWithdrawal], strict: true);
    }
}
