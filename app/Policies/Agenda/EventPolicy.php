<?php

namespace App\Policies\Agenda;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('agenda.events.create')
            || $user->hasPermission('agenda.events.manage');
    }

    public function update(User $user, Event $event): bool
    {
        return $user->is_admin
            || $user->hasPermission('agenda.events.edit')
            || $user->hasPermission('agenda.events.manage');
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->is_admin
            || $user->hasPermission('agenda.events.delete')
            || $user->hasPermission('agenda.events.manage');
    }

    public function duplicate(User $user, Event $event): bool
    {
        return $this->create($user);
    }

    public function manageRegistrations(User $user, Event $event): bool
    {
        return $this->update($user, $event)
            || $user->hasPermission('agenda.inscricoes.edit')
            || $user->hasPermission('agenda.inscricoes.manage');
    }

    /**
     * Excluir é permissão à parte de editar: quem gerencia inscrições no dia do
     * evento não precisa poder apagá-las.
     */
    public function deleteRegistrations(User $user, Event $event): bool
    {
        return $user->is_admin
            || $user->hasPermission('agenda.inscricoes.delete')
            || $user->hasPermission('agenda.inscricoes.manage');
    }

    public function uploadEditorImage(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('agenda.events.create')
            || $user->hasPermission('agenda.events.edit')
            || $user->hasPermission('agenda.events.manage');
    }
}
