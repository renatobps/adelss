<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class ModuleResourcePolicy
{
    use ChecksModuleResourcePermissions;

    public function viewAny(User $user): bool
    {
        return $this->canViewAny($user);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->canViewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreate($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->canUpdate($user);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->canDelete($user);
    }
}
