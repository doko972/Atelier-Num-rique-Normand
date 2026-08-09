<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\Workshop;

class WorkshopPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageWorkshops);
    }

    public function view(User $user, Workshop $workshop): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageWorkshops);
    }

    public function update(User $user, Workshop $workshop): bool
    {
        return $user->hasPermission(Permission::ManageWorkshops);
    }

    /**
     * Un atelier ayant reçu des inscriptions ne se supprime pas : on l'annule,
     * ce qui déclenche l'information des personnes inscrites.
     */
    public function delete(User $user, Workshop $workshop): bool
    {
        return $user->hasPermission(Permission::ManageWorkshops)
            && $workshop->registrations()->doesntExist();
    }

    public function cancel(User $user, Workshop $workshop): bool
    {
        return $user->hasPermission(Permission::ManageWorkshops);
    }

    public function export(User $user): bool
    {
        return $user->hasPermission(Permission::ExportData);
    }
}
