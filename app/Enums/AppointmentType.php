<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Types de demande de rendez-vous (codex §11).
 */
enum AppointmentType: string
{
    use HasLabel;

    case Individual = 'individual';
    case Home = 'home';
    case PartnerLocation = 'partner_location';
    case Phone = 'phone';
    case Information = 'information';
    case Caregiver = 'caregiver';
    case Organisation = 'organisation';

    /**
     * Description affichée sous le libellé dans le formulaire public.
     */
    public function description(): string
    {
        $group = 'enums.appointment_type_description';

        return __("{$group}.{$this->value}");
    }

    /**
     * Ce type implique-t-il un déplacement chez la personne ?
     */
    public function requiresTravel(): bool
    {
        return $this === self::Home;
    }
}
