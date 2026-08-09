<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ContactRequest;
use App\Models\PartnershipRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Politique commune aux messages reçus et aux demandes de partenariat.
 */
class RequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageContactRequests)
            || $user->hasPermission(Permission::ManagePartnershipRequests);
    }

    public function view(User $user, Model $request): bool
    {
        return $user->hasPermission($this->permissionFor($request));
    }

    public function update(User $user, Model $request): bool
    {
        return $user->hasPermission($this->permissionFor($request));
    }

    public function delete(User $user, Model $request): bool
    {
        return $user->hasPermission(Permission::ManageGdprRequests);
    }

    public function anonymise(User $user, Model $request): bool
    {
        return $user->hasPermission(Permission::ManageGdprRequests)
            && ! $request->isAnonymised();
    }

    public function export(User $user): bool
    {
        return $user->hasPermission(Permission::ExportData);
    }

    protected function permissionFor(Model $request): Permission
    {
        return match ($request::class) {
            PartnershipRequest::class => Permission::ManagePartnershipRequests,
            ContactRequest::class => Permission::ManageContactRequests,
            default => Permission::ManageContactRequests,
        };
    }
}
