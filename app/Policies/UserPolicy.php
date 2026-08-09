<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Comptes d'administration.
 *
 * Deux garde-fous structurent cette politique :
 * on n'agit jamais sur un compte de niveau supérieur ou égal au sien, et on ne
 * se supprime ni ne se désactive soi-même.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasPermission(Permission::ManageUsers) || $user->is($target);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function update(User $user, User $target): bool
    {
        if (! $user->hasPermission(Permission::ManageUsers)) {
            return false;
        }

        // Un compte peut toujours modifier son propre profil.
        return $user->is($target) || $user->canManage($target);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->hasPermission(Permission::ManageUsers)
            && $user->canManage($target);
    }

    public function toggleActivation(User $user, User $target): bool
    {
        return $this->delete($user, $target);
    }

    /**
     * Chacun modifie son propre profil, quel que soit son rôle.
     */
    public function updateProfile(User $user, User $target): bool
    {
        return $user->is($target);
    }
}
