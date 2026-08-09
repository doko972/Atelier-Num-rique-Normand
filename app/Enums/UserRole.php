<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Rôles de l'espace d'administration (codex §23).
 *
 * Les rôles sont hiérarchiques : un niveau supérieur possède au moins les
 * permissions des niveaux inférieurs.
 */
enum UserRole: string
{
    use HasLabel;

    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Adviser = 'adviser';
    case Editor = 'editor';
    case Viewer = 'viewer';

    /**
     * Poids hiérarchique du rôle.
     */
    public function level(): int
    {
        return match ($this) {
            self::SuperAdmin => 50,
            self::Admin => 40,
            self::Adviser => 30,
            self::Editor => 20,
            self::Viewer => 10,
        };
    }

    /**
     * Le rôle atteint-il au moins le niveau demandé ?
     */
    public function atLeast(self $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Permissions accordées par ce rôle.
     *
     * @return array<int, Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            // Le super administrateur détient toutes les permissions.
            self::SuperAdmin => Permission::cases(),

            // L'administrateur gère tout le site, sauf la création et la
            // suppression des comptes d'administration, réservées au super
            // administrateur afin d'éviter toute escalade de privilèges.
            self::Admin => array_values(array_filter(
                Permission::cases(),
                fn (Permission $permission): bool => $permission !== Permission::ManageUsers,
            )),

            // Le conseiller traite le quotidien : demandes, ateliers, inscriptions.
            self::Adviser => [
                Permission::ViewDashboard,
                Permission::ManageAppointments,
                Permission::ManageWorkshops,
                Permission::ManageRegistrations,
                Permission::ManageContactRequests,
                Permission::ManagePartnershipRequests,
                Permission::ExportData,
            ],

            // L'éditeur ne touche qu'au contenu éditorial.
            self::Editor => [
                Permission::ViewDashboard,
                Permission::ManageContent,
                Permission::ManageDirectory,
                Permission::ManageWorkshops,
            ],

            // Le lecteur consulte le tableau de bord, sans rien modifier.
            self::Viewer => [
                Permission::ViewDashboard,
            ],
        };
    }

    /**
     * Le rôle dispose-t-il de la permission demandée ?
     */
    public function grants(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), strict: true);
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::SuperAdmin => 'danger',
            self::Admin => 'primary',
            self::Adviser => 'success',
            self::Editor => 'info',
            self::Viewer => 'neutral',
        };
    }
}
