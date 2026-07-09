<?php

namespace App\Policies\Ensino;

use App\Models\User;

class EnsinoModulePolicy
{
    public function accessAny(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->hasPermission('ensino.estudos.view')
            || $user->hasPermission('ensino.estudos.manage')
            || $user->hasPermission('ensino.escolas.view')
            || $user->hasPermission('ensino.escolas.manage')
            || $user->hasPermission('ensino.turmas.view')
            || $user->hasPermission('ensino.turmas.manage');
    }
}
