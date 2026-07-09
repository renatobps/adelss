<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Policies\Rifas\RifaPolicy;
use App\Policies\Rifas\RifaReportPolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RifaRouteAuthorizer
{
    public function __construct(
        private RifaPolicy $rifaPolicy,
        private RifaReportPolicy $rifaReportPolicy,
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

        if ($routeName === 'rifas.index' || $action === 'index'
            || $routeName === 'rifas.show' || $action === 'show'
            || $routeName === 'rifas.sorteios.index'
            || $routeName === 'rifas.cartelas.index'
            || str_starts_with($routeName, 'rifas.relatorios')) {
            return $this->canViewRifas($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar rifas.');
        }

        if (in_array($routeName, ['rifas.create', 'rifas.store'], true) || in_array($action, ['create', 'store'], true)) {
            return $this->rifaPolicy->create($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para criar rifas.');
        }

        if (in_array($routeName, ['rifas.edit', 'rifas.update', 'rifas.status.update'], true)
            || in_array($action, ['edit', 'update'], true)) {
            return $this->rifaPolicy->update($user, new \App\Models\Rifa())
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para editar rifas.');
        }

        if (in_array($routeName, ['rifas.destroy', 'rifas.sorteios.destroy'], true) || $action === 'destroy') {
            return $this->rifaPolicy->delete($user, new \App\Models\Rifa())
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para excluir/cancelar rifas.');
        }

        if (str_starts_with($routeName, 'rifas.vendas')
            || str_starts_with($routeName, 'rifas.numeros')
            || $routeName === 'rifas.sortear') {
            return $this->rifaPolicy->sell($user, new \App\Models\Rifa())
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para gerenciar vendas de rifa.');
        }

        return $this->canViewRifas($user)
            ? null
            : $denyAccess('Acesso negado. Você não tem permissão para acessar rifas.');
    }

    private function canViewRifas(User $user): bool
    {
        return $this->rifaPolicy->viewAny($user) || $this->rifaReportPolicy->viewAny($user);
    }
}
