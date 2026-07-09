<?php

namespace App\Services\Authorization;

use App\Models\Pgi;
use App\Models\User;
use App\Policies\Pgis\PgiPolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PgiRouteAuthorizer
{
    public function __construct(
        private PgiPolicy $pgiPolicy,
    ) {}

    /**
     * @param  callable(string): Response  $denyAccess
     */
    public function authorize(Request $request, User $user, callable $denyAccess): ?Response
    {
        if ($user->is_admin) {
            return null;
        }

        $routeName = $request->route()?->getName();
        $action = $request->route()?->getActionMethod();
        $pgi = $this->resolvePgi($request);

        if (str_starts_with($routeName ?? '', 'pgis.meetings')) {
            return $pgi && $this->pgiPolicy->manageMeetings($user, $pgi)
                ? null
                : $denyAccess('Acesso negado. Apenas líderes e líderes em treinamento podem gerenciar reuniões.');
        }

        if ($routeName === 'pgis.index' || $action === 'index') {
            return $this->pgiPolicy->viewAny($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar PGIs.');
        }

        if ($routeName === 'pgis.show' || $action === 'show') {
            return $pgi && $this->pgiPolicy->view($user, $pgi)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar este PGI.');
        }

        if (in_array($routeName, ['pgis.create', 'pgis.store'], true) || in_array($action, ['create', 'store'], true)) {
            return $this->pgiPolicy->create($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para criar PGIs.');
        }

        if (in_array($routeName, ['pgis.edit', 'pgis.update'], true) || in_array($action, ['edit', 'update'], true)) {
            return $pgi && $this->pgiPolicy->update($user, $pgi)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para editar PGIs.');
        }

        if ($routeName === 'pgis.destroy' || $action === 'destroy') {
            return $pgi && $this->pgiPolicy->delete($user, $pgi)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para remover PGIs.');
        }

        if ($routeName === 'pgis.members.attach' || $action === 'attachMembers') {
            return $pgi && $this->pgiPolicy->attachMembers($user, $pgi)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para acessar este módulo.');
        }

        if ($routeName === 'pgis.members.detach' || $action === 'detachMember') {
            return $pgi && $this->pgiPolicy->detachMember($user, $pgi)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para acessar este módulo.');
        }

        if ($routeName === 'pgis.logo.update' || $action === 'updateLogo') {
            return $pgi && $this->pgiPolicy->updateLogo($user, $pgi)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para acessar este módulo.');
        }

        if ($routeName === 'pgis.banner.update' || $action === 'updateBanner') {
            return $pgi && $this->pgiPolicy->updateBanner($user, $pgi)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para acessar este módulo.');
        }

        if ($routeName === 'pgis.notificacoes.enviar' || $action === 'enviarNotificacao') {
            return $pgi && $this->pgiPolicy->sendNotification($user, $pgi)
                ? null
                : $denyAccess('Acesso negado. Apenas administradores ou líderes podem enviar notificações para o PGI.');
        }

        return $this->pgiPolicy->viewAny($user)
            ? null
            : $denyAccess('Acesso negado. Você não tem permissão para acessar este módulo.');
    }

    private function resolvePgi(Request $request): ?Pgi
    {
        $pgi = $request->route('pgi');

        if ($pgi instanceof Pgi) {
            return $pgi;
        }

        if ($pgi) {
            return Pgi::find($pgi);
        }

        return null;
    }
}
