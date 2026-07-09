<?php

namespace App\Policies\Discipleship;

use App\Models\User;

class DiscipleshipModulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('discipleship.view')
            || $user->hasPermission('discipleship.manage');
    }

    public function manage(User $user): bool
    {
        return $user->is_admin || $user->hasPermission('discipleship.manage');
    }
}
