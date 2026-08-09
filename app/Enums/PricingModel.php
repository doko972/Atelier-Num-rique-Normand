<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Modèles tarifaires proposés (codex §16).
 */
enum PricingModel: string
{
    use HasLabel;

    case Hourly = 'hourly';
    case Discovery = 'discovery';
    case Package = 'package';
    case HomeVisit = 'home_visit';
    case Workshop = 'workshop';
    case FundedWorkshop = 'funded_workshop';
    case Association = 'association';
    case LocalAuthority = 'local_authority';
    case Quote = 'quote';
    case Mileage = 'mileage';

    /**
     * Le tarif est-il exprimé par un montant, ou sur devis ?
     */
    public function hasAmount(): bool
    {
        return $this !== self::Quote;
    }
}
