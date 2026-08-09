<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Nature d'une structure partenaire ou demandeuse (codex §15).
 */
enum PartnerType: string
{
    use HasLabel;

    case CityHall = 'city_hall';
    case SocialCentre = 'social_centre';
    case Ccas = 'ccas';
    case Library = 'library';
    case Association = 'association';
    case SeniorResidence = 'senior_residence';
    case Ehpad = 'ehpad';
    case NeighbourhoodHouse = 'neighbourhood_house';
    case FranceServices = 'france_services';
    case Company = 'company';
    case TrainingOrganisation = 'training_organisation';
    case Other = 'other';
}
