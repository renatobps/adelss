<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Policies\Agenda\EventCategoryPolicy;
use App\Policies\Agenda\EventPolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgendaRouteAuthorizer
{
    public function __construct(
        private EventPolicy $eventPolicy,
        private EventCategoryPolicy $eventCategoryPolicy,
    ) {}

    /**
     * @param  callable(string): Response  $denyAccess
     */
    public function authorize(Request $request, User $user, callable $denyAccess): ?Response
    {
        if ($user->is_admin) {
            return null;
        }

        $routeName = $request->route()?->getName() ?? '';
        $action = $request->route()?->getActionMethod() ?? '';

        if (str_starts_with($routeName, 'agenda.calendario')
            || in_array($routeName, ['agenda.events.index', 'agenda.events.show', 'agenda.eventos.index'], true)
            || in_array($action, ['index', 'show'], true) && str_starts_with($routeName, 'agenda.events')) {
            return null;
        }

        if ($routeName === 'agenda.events.store' || $action === 'store' && $routeName === 'agenda.events.store') {
            return $this->eventPolicy->create($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para adicionar eventos.');
        }

        if ($routeName === 'agenda.events.update' || ($action === 'update' && str_starts_with($routeName, 'agenda.events'))) {
            return $this->eventPolicy->update($user, new \App\Models\Event())
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para editar eventos.');
        }

        if ($routeName === 'agenda.events.destroy' || ($action === 'destroy' && str_starts_with($routeName, 'agenda.events'))) {
            return $this->eventPolicy->delete($user, new \App\Models\Event())
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para excluir eventos.');
        }

        if (str_starts_with($routeName, 'agenda.eventos')) {
            if (in_array($routeName, ['agenda.eventos.create', 'agenda.eventos.store', 'agenda.eventos.duplicate'], true)) {
                return $this->eventPolicy->create($user)
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para criar eventos.');
            }

            if (in_array($routeName, ['agenda.eventos.edit', 'agenda.eventos.update'], true)) {
                return $this->eventPolicy->update($user, new \App\Models\Event())
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para editar eventos.');
            }

            if ($routeName === 'agenda.eventos.destroy') {
                return $this->eventPolicy->delete($user, new \App\Models\Event())
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para excluir eventos.');
            }

            if (in_array($routeName, [
                'agenda.eventos.registrations.destroy',
                'agenda.eventos.registrations.restore',
            ], true)) {
                return $this->eventPolicy->deleteRegistrations($user, new \App\Models\Event())
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para excluir inscrições.');
            }

            if (str_starts_with($routeName, 'agenda.eventos.registrations.')
                && ! in_array($routeName, ['agenda.eventos.registrations.export', 'agenda.eventos.registrations.export-pdf'], true)) {
                return $this->eventPolicy->manageRegistrations($user, new \App\Models\Event())
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para alterar inscrições.');
            }

            if ($routeName === 'agenda.eventos.editor-upload') {
                return $this->eventPolicy->uploadEditorImage($user)
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para enviar imagens no editor.');
            }

            return null;
        }

        if (str_starts_with($routeName, 'agenda.categories')) {
            if (in_array($routeName, ['agenda.categories.index', 'agenda.categories.show'], true)
                || in_array($action, ['index', 'show'], true)) {
                return null;
            }

            if ($routeName === 'agenda.categories.store' || $action === 'store') {
                return $this->eventCategoryPolicy->create($user)
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para adicionar categorias.');
            }

            if ($routeName === 'agenda.categories.update' || $action === 'update') {
                return $this->eventCategoryPolicy->update($user, new \App\Models\EventCategory())
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para editar categorias.');
            }

            if ($routeName === 'agenda.categories.destroy' || $action === 'destroy') {
                return $this->eventCategoryPolicy->delete($user, new \App\Models\EventCategory())
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para excluir categorias.');
            }
        }

        return null;
    }
}
