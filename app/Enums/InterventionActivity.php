<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Nature d'une prestation réalisée, au regard des services à la personne.
 *
 * Le critère décisif est le lieu : seules les prestations réalisées **au
 * domicile** d'un particulier relèvent des services à la personne (SAP). La
 * même heure d'accompagnement, donnée à distance ou dans une salle
 * partenaire, est hors SAP — d'où des natures distinctes plutôt qu'une case
 * « à domicile » qu'on oublierait de cocher.
 *
 * Les quatre natures SAP se répartissent sur deux activités réglementaires
 * (article D. 7231-1 du code du travail), qui ne se déclarent pas ensemble :
 * l'assistance informatique à domicile — plafonnée à 3 000 € de dépenses par
 * foyer et par an pour le crédit d'impôt — et l'assistance administrative à
 * domicile.
 *
 * Voir docs/tarifs-et-services-a-la-personne.md.
 */
enum InterventionActivity: string
{
    use HasLabel;

    // -- Services à la personne -------------------------------------------
    case HomeTraining = 'home_training';
    case HomeInstallation = 'home_installation';
    case HomeMaintenance = 'home_maintenance';
    case HomeAdministrative = 'home_administrative';

    // -- Hors services à la personne --------------------------------------
    case Remote = 'remote';
    case PartnerLocation = 'partner_location';
    case Workshop = 'workshop';
    case Organisation = 'organisation';
    case Other = 'other';

    public const string GROUP_IT = 'it_assistance';

    public const string GROUP_ADMINISTRATIVE = 'administrative_assistance';

    /**
     * La prestation relève-t-elle des services à la personne ?
     */
    public function isSap(): bool
    {
        return $this->sapGroup() !== null;
    }

    /**
     * Activité réglementaire SAP de rattachement, ou null hors SAP.
     */
    public function sapGroup(): ?string
    {
        return match ($this) {
            self::HomeTraining,
            self::HomeInstallation,
            self::HomeMaintenance => self::GROUP_IT,
            self::HomeAdministrative => self::GROUP_ADMINISTRATIVE,
            default => null,
        };
    }

    public function badgeVariant(): string
    {
        return $this->isSap() ? 'success' : 'neutral';
    }

    /**
     * @return array<int, self>
     */
    public static function sapCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $case): bool => $case->isSap()));
    }

    /**
     * @return array<int, self>
     */
    public static function otherCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $case): bool => ! $case->isSap()));
    }

    /**
     * Options regroupées pour une liste déroulante (`<optgroup>`).
     *
     * @return array<string, array<string, string>>
     */
    public static function groupedOptions(): array
    {
        $map = fn (array $cases): array => collect($cases)
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();

        return [
            __('admin.interventions.group_sap') => $map(self::sapCases()),
            __('admin.interventions.group_other') => $map(self::otherCases()),
        ];
    }

    /**
     * Nature proposée par défaut pour une prestation issue d'un rendez-vous.
     */
    public static function suggestedFor(AppointmentType $type): self
    {
        return match ($type) {
            AppointmentType::Organisation => self::Organisation,
            AppointmentType::Phone => self::Remote,
            AppointmentType::PartnerLocation => self::PartnerLocation,
            default => self::HomeTraining,
        };
    }
}
