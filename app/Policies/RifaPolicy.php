<?php

namespace App\Policies;

use App\Models\Rifa;
use App\Models\User;

class RifaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('rifas.index.view')
            || $user->hasPermission('rifas.index.manage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Rifa $rifa): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('rifas.index.create')
            || $user->hasPermission('rifas.index.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Rifa $rifa): bool
    {
        return $user->is_admin
            || $user->hasPermission('rifas.index.edit')
            || $user->hasPermission('rifas.index.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Rifa $rifa): bool
    {
        return $user->is_admin
            || $user->hasPermission('rifas.index.delete')
            || $user->hasPermission('rifas.index.manage');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Rifa $rifa): bool
    {
        return $this->delete($user, $rifa);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Rifa $rifa): bool
    {
        return $this->delete($user, $rifa);
    }

    public function sell(User $user, Rifa $rifa): bool
    {
        return $user->is_admin
            || $user->hasPermission('rifas.sales.create')
            || $user->hasPermission('rifas.sales.manage')
            || $user->hasPermission('rifas.index.manage');
    }
}
