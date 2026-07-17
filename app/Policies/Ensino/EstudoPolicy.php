<?php

namespace App\Policies\Ensino;

use App\Models\Study;
use App\Models\User;

class EstudoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Study $study): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('ensino.estudos.create')
            || $user->hasPermission('ensino.estudos.manage');
    }

    public function update(User $user, Study $study): bool
    {
        return $user->is_admin
            || $user->hasPermission('ensino.estudos.edit')
            || $user->hasPermission('ensino.estudos.manage');
    }

    public function delete(User $user, Study $study): bool
    {
        return $user->is_admin
            || $user->hasPermission('ensino.estudos.delete')
            || $user->hasPermission('ensino.estudos.manage');
    }

    public function authorizeRouteAction(User $user, string $action): bool
    {
        if (in_array($action, ['index', 'show', 'submissions', 'showSubmission'], true)) {
            return true;
        }

        if (in_array($action, ['create', 'store'], true)) {
            return $this->create($user);
        }

        if (in_array($action, ['edit', 'update', 'preview'], true)) {
            return $this->update($user, new Study());
        }

        if ($action === 'destroy') {
            return $this->delete($user, new Study());
        }

        return true;
    }

    public function authorizeFormRouteAction(User $user, string $action): bool
    {
        if (in_array($action, ['index', 'show', 'submissions', 'showSubmission', 'pdf'], true)) {
            return true;
        }

        return $this->update($user, new Study());
    }
}


