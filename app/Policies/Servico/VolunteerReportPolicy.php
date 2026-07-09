<?php

namespace App\Policies\Servico;

use App\Models\User;
use App\Policies\Concerns\ChecksModuleResourcePermissions;

class VolunteerReportPolicy
{
    use ChecksModuleResourcePermissions;

    protected function resourcePermissionKey(): string
    {
        return 'servico.voluntarios.relatorios';
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewAny($user);
    }

    public function authorizeRouteAction(User $user, string $action): bool
    {
        return $this->canViewAny($user);
    }
}
