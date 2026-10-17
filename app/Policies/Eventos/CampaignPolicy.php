<?php

namespace App\Policies\Eventos;

use App\Models\User;

/**
 * Permissões das campanhas de arrecadação e dos gastos de eventos
 * (módulo Gestor de Eventos): eventos.campanhas.{view|create|edit|delete|pagar|estornar}.
 */
class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->action($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->action($user, 'create');
    }

    public function update(User $user): bool
    {
        return $this->action($user, 'edit');
    }

    public function delete(User $user): bool
    {
        return $this->action($user, 'delete');
    }

    public function action(User $user, string $action): bool
    {
        return $user->is_admin
            || $user->hasPermission('eventos.campanhas.manage')
            || $user->hasPermission("eventos.campanhas.{$action}");
    }
}
