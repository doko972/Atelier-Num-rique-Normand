<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\WorkshopRegistration;

/**
 * Résultat d'une inscription à un atelier.
 *
 * Le contrôleur a besoin de savoir si la place est acquise ou si la personne
 * a été placée en liste d'attente, car le message affiché n'est pas le même.
 */
final readonly class RegistrationOutcome
{
    public function __construct(
        public WorkshopRegistration $registration,
        public bool $onWaitingList,
    ) {}

    /**
     * Message de confirmation destiné à la personne.
     */
    public function message(): string
    {
        $key = $this->onWaitingList
            ? 'site.workshops.registered_waiting'
            : 'site.workshops.registered_confirmed';

        return __($key, ['reference' => $this->registration->reference]);
    }
}
