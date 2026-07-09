<?php

namespace App\Policies\Servico;

use App\Models\User;
use App\Policies\Concerns\ChecksModuleResourcePermissions;

class ServiceHistoryPolicy
{
    use ChecksModuleResourcePermissions;

    protected function resourcePermissionKey(): string
    {
        return 'servico.voluntarios.historico';
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewAny($user);
    }

    public function view(User $user, mixed $model = null): bool
    {
        return $this->canViewAny($user);
    }

    public function authorizeRouteAction(User $user, string $action): bool
    {
        return $this->canViewAny($user);
    }
}
