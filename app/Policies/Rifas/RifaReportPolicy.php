<?php

namespace App\Policies\Rifas;

use App\Models\Rifa;
use App\Models\User;

class RifaReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('rifas.reports.view')
            || $user->hasPermission('rifas.reports.manage')
            || $user->hasPermission('rifas.index.view')
            || $user->hasPermission('rifas.index.manage')
            || $user->hasPermission('rifas.sales.view')
            || $user->hasPermission('rifas.sales.manage');
    }
}
