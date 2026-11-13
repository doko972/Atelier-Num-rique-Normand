<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Intervention;
use App\Models\User;

/**
 * Prestations réalisées : chiffre d'affaires, accès réservé.
 */
class InterventionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageInterventions);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Intervention $intervention): bool
    {
        return $this->viewAny($user) && ! $intervention->trashed();
    }

    /**
     * La suppression envoie en corbeille : une pièce comptable ne disparaît
     * jamais sans trace.
     */
    public function delete(User $user, Intervention $intervention): bool
    {
        return $this->update($user, $intervention);
    }

    public function restore(User $user, Intervention $intervention): bool
    {
        return $this->viewAny($user) && $intervention->trashed();
    }

    public function export(User $user): bool
    {
        return $this->viewAny($user) && $user->hasPermission(Permission::ExportData);
    }
}
