<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Rôles et permissions.
 *
 * Ce seeder est idempotent : le relancer met la matrice à jour sans créer de
 * doublon, ce qui permet de l'exécuter après chaque déploiement.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(PermissionEnum::cases())
            ->mapWithKeys(fn (PermissionEnum $permission): array => [
                $permission->value => Permission::updateOrCreate(
                    ['slug' => $permission->value],
                    [
                        'name' => $permission->label(),
                        'group' => $this->groupFor($permission),
                    ],
                ),
            ]);

        foreach (UserRole::cases() as $case) {
            $role = Role::updateOrCreate(
                ['slug' => $case->value],
                [
                    'name' => $case->label(),
                    'level' => $case->level(),
                    'is_system' => true,
                    'description' => $this->descriptionFor($case),
                ],
            );

            $role->permissions()->sync(
                collect($case->permissions())
                    ->map(fn (PermissionEnum $permission) => $permissions[$permission->value]->id)
                    ->all(),
            );
        }
    }

    protected function groupFor(PermissionEnum $permission): string
    {
        return match ($permission) {
            PermissionEnum::ManageAppointments,
            PermissionEnum::ManageWorkshops,
            PermissionEnum::ManageRegistrations,
            PermissionEnum::ManageContactRequests,
            PermissionEnum::ManagePartnershipRequests => 'quotidien',

            PermissionEnum::ManageContent,
            PermissionEnum::ManageDirectory => 'contenus',

            PermissionEnum::ManageUsers,
            PermissionEnum::ManageSettings,
            PermissionEnum::ManageGdprRequests,
            PermissionEnum::ViewAuditLog => 'administration',

            default => 'general',
        };
    }

    protected function descriptionFor(UserRole $role): string
    {
        return match ($role) {
            UserRole::SuperAdmin => 'Accès complet, y compris la gestion des comptes.',
            UserRole::Admin => 'Gère tout le site, sauf la création des comptes d’administration.',
            UserRole::Adviser => 'Traite les demandes, les ateliers et les inscriptions au quotidien.',
            UserRole::Editor => 'Rédige et publie les contenus du site.',
            UserRole::Viewer => 'Consulte le tableau de bord, sans rien modifier.',
        };
    }
}
