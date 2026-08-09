<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Politique commune aux contenus éditoriaux : services, catégories, articles,
 * fiches pratiques, pages, questions fréquentes, témoignages, tarifs, liens.
 *
 * Les modèles concernés partagent exactement les mêmes règles ; les décliner
 * en une classe par modèle n'apporterait rien et multiplierait les oublis.
 */
class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageContent);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageContent);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Model $model): bool
    {
        // Une page système est référencée par le pied de page : on peut la
        // dépublier, jamais la supprimer.
        if (($model->is_system ?? false) === true) {
            return false;
        }

        return $user->hasPermission(Permission::ManageContent);
    }

    public function restore(User $user, Model $model): bool
    {
        return $user->hasPermission(Permission::ManageContent);
    }

    /**
     * La suppression définitive n'est ouverte qu'au super administrateur :
     * elle échappe à la corbeille et ne peut plus être rattrapée.
     */
    public function forceDelete(User $user, Model $model): bool
    {
        return $user->isSuperAdmin();
    }
}
