<?php

namespace App\Policies\Notificacoes;

use App\Models\User;

class NotificacaoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('notificacoes.view')
            || $user->hasPermission('notificacoes.manage');
    }

    public function manage(User $user): bool
    {
        return $user->is_admin || $user->hasPermission('notificacoes.manage');
    }
}
