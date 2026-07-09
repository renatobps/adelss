<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksModuleResourcePermissions
{
    abstract protected function resourcePermissionKey(): string;

    protected function canViewAny(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $key = $this->resourcePermissionKey();

        return $user->hasPermission("{$key}.view")
            || $user->hasPermission("{$key}.manage");
    }

    protected function canCreate(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $key = $this->resourcePermissionKey();

        return $user->hasPermission("{$key}.create")
            || $user->hasPermission("{$key}.manage");
    }

    protected function canUpdate(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $key = $this->resourcePermissionKey();

        return $user->hasPermission("{$key}.edit")
            || $user->hasPermission("{$key}.manage");
    }

    protected function canDelete(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $key = $this->resourcePermissionKey();

        return $user->hasPermission("{$key}.delete")
            || $user->hasPermission("{$key}.manage");
    }

    public function authorizeRouteAction(User $user, string $action): bool
    {
        if (in_array($action, ['index', 'show'], true)) {
            return $this->canViewAny($user);
        }

        if (in_array($action, ['create', 'store'], true)) {
            return $this->canCreate($user);
        }

        if (in_array($action, ['edit', 'update'], true)) {
            return $this->canUpdate($user);
        }

        if ($action === 'destroy') {
            return $this->canDelete($user);
        }

        return $this->canViewAny($user);
    }
}
