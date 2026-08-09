<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Comptes d'administration de démonstration.
 *
 * Les mots de passe sont volontairement identiques et connus : ce seeder ne
 * doit jamais être exécuté en production. La commande d'installation le
 * rappelle, et le README également.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('UserSeeder ignoré : comptes de démonstration interdits en production.');

            return;
        }

        $accounts = [
            [
                'role' => UserRole::SuperAdmin,
                'name' => 'Super administrateur',
                'email' => 'super-admin@example.test',
            ],
            [
                'role' => UserRole::Admin,
                'name' => 'Administrateur',
                'email' => 'admin@example.test',
            ],
            [
                'role' => UserRole::Adviser,
                'name' => 'Conseiller numérique',
                'email' => 'conseiller@example.test',
            ],
            [
                'role' => UserRole::Editor,
                'name' => 'Éditeur du site',
                'email' => 'editeur@example.test',
            ],
            [
                'role' => UserRole::Viewer,
                'name' => 'Lecteur',
                'email' => 'lecteur@example.test',
            ],
        ];

        foreach ($accounts as $account) {
            $role = Role::query()->where('slug', $account['role']->value)->firstOrFail();

            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'role_id' => $role->id,
                    'name' => $account['name'],
                    'password' => Hash::make('MotDePasseDemo2026!'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password_changed_at' => now(),
                ],
            );
        }

        $this->command?->info('Comptes de démonstration créés. Mot de passe commun : MotDePasseDemo2026!');
    }
}
