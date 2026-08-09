<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Finalités pour lesquelles un consentement est recueilli et journalisé.
 *
 * Le registre des consentements conserve, pour chaque envoi de formulaire, la
 * finalité, l'horodatage, l'adresse IP tronquée et le texte exact affiché.
 */
enum ConsentPurpose: string
{
    use HasLabel;

    case AppointmentRequest = 'appointment_request';
    case WorkshopRegistration = 'workshop_registration';
    case ContactRequest = 'contact_request';
    case PartnershipRequest = 'partnership_request';
    case VoiceMessage = 'voice_message';
}
