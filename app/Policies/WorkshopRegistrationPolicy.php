<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\WorkshopRegistration;

class WorkshopRegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageRegistrations);
    }

    public function view(User $user, WorkshopRegistration $registration): bool
    {
        return $this->viewAny($user);
    }

    /**
     * L'administrateur doit pouvoir enregistrer une inscription reçue par
     * téléphone (codex §10).
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageRegistrations);
    }

    public function update(User $user, WorkshopRegistration $registration): bool
    {
        return $user->hasPermission(Permission::ManageRegistrations);
    }

    public function delete(User $user, WorkshopRegistration $registration): bool
    {
        return $user->hasPermission(Permission::ManageGdprRequests);
    }

    public function anonymise(User $user, WorkshopRegistration $registration): bool
    {
        return $user->hasPermission(Permission::ManageGdprRequests)
            && ! $registration->isAnonymised();
    }

    public function export(User $user): bool
    {
        return $user->hasPermission(Permission::ExportData);
    }
}
