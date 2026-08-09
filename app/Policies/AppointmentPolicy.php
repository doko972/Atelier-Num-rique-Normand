<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\User;

/**
 * Demandes de rendez-vous : données personnelles, accès restreint.
 */
class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageAppointments);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageAppointments);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission(Permission::ManageAppointments);
    }

    /**
     * La suppression est réservée aux administrateurs : dans le cours normal
     * du service, on anonymise plutôt que de supprimer.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission(Permission::ManageGdprRequests);
    }

    public function anonymise(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission(Permission::ManageGdprRequests)
            && ! $appointment->isAnonymised();
    }

    public function export(User $user): bool
    {
        return $user->hasPermission(Permission::ExportData);
    }
}
