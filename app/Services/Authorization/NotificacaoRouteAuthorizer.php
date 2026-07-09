<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Policies\Notificacoes\NotificacaoPolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificacaoRouteAuthorizer
{
    private const VIEW_ONLY_ROUTES = [
        'notificacoes.grupos.index',
        'notificacoes.grupos.show',
        'notificacoes.enquetes.index',
        'notificacoes.enquetes.show',
        'notificacoes.painel.index',
        'notificacoes.config.index',
        'notificacoes.config.status',
        'notificacoes.config.conectar',
        'notificacoes.config.instances',
        'notificacoes.config.instances.status',
        'notificacoes.templates.index',
        'notificacoes.grupos.lista-json',
        'notificacoes.departamentos-lista-json',
    ];

    public function __construct(
        private NotificacaoPolicy $notificacaoPolicy,
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

        if (in_array($routeName, self::VIEW_ONLY_ROUTES, true)) {
            return $this->notificacaoPolicy->viewAny($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para acessar notificações.');
        }

        return $this->notificacaoPolicy->manage($user)
            ? null
            : $denyAccess('Acesso negado. Você não tem permissão para gerenciar notificações.');
    }
}
