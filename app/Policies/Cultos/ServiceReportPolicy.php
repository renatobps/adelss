<?php

namespace App\Policies\Cultos;

use App\Models\ServiceReport;
use App\Models\User;

class ServiceReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin || $user->hasPermission('cultos.relatorios.view');
    }

    public function view(User $user, ServiceReport $report): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('cultos.relatorios.create')
            || $user->hasPermission('cultos.relatorios.edit');
    }

    public function update(User $user, ServiceReport $report): bool
    {
        return $user->is_admin || $user->hasPermission('cultos.relatorios.edit');
    }

    public function delete(User $user, ServiceReport $report): bool
    {
        return $user->is_admin || $user->hasPermission('cultos.relatorios.delete');
    }

    public function manageSettings(User $user): bool
    {
        return $user->is_admin || $user->hasPermission('cultos.configuracoes.manage');
    }
}
