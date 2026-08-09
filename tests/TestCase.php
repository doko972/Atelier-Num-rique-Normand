<?php

declare(strict_types=1);

namespace Tests;

use App\Enums\UserRole;
use App\Http\Requests\Site\PublicFormRequest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crée un compte d'administration ayant le rôle demandé.
     */
    protected function userWithRole(UserRole $role): User
    {
        $this->seedRoles();

        $user = User::create([
            'role_id' => Role::query()->where('slug', $role->value)->firstOrFail()->id,
            'name' => 'Compte de test '.$role->value,
            'email' => $role->value.'@test.local',
            'password' => Hash::make('MotDePasseTest2026!'),
            'is_active' => true,
        ]);

        // `email_verified_at` n'est volontairement pas assignable en masse :
        // seul le parcours de vérification doit pouvoir le renseigner.
        $user->markEmailAsVerified();

        return $user->refresh();
    }

    /**
     * Amorce la matrice des rôles, une seule fois par test.
     */
    protected function seedRoles(): void
    {
        if (Role::query()->exists()) {
            return;
        }

        $this->seed(RoleSeeder::class);
    }

    /**
     * Champs anti-spam attendus par tout formulaire public.
     *
     * L'horodatage est volontairement daté de plusieurs minutes : le contrôle
     * de délai minimal considérerait sinon l'envoi comme trop rapide.
     *
     * @return array<string, string>
     */
    protected function antiSpamFields(): array
    {
        return [
            PublicFormRequest::HONEYPOT_FIELD => '',
            PublicFormRequest::TIMESTAMP_FIELD => (string) (time() - 300),
        ];
    }
}
