<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Policies\Cultos\ServiceReportPolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CultosRouteAuthorizer
{
    public function __construct(
        private ServiceReportPolicy $policy
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

        if (str_starts_with($routeName ?? '', 'cultos.settings')) {
            return $this->policy->manageSettings($user)
                ? null
                : $denyAccess('Acesso negado. Sem permissão para configurações de cultos.');
        }

        if (in_array($routeName, ['cultos.create', 'cultos.store'], true)) {
            return $this->policy->create($user)
                ? null
                : $denyAccess('Acesso negado. Sem permissão para criar relatórios de culto.');
        }

        if (in_array($routeName, ['cultos.edit', 'cultos.update', 'cultos.finalize'], true)) {
            return ($user->hasPermission('cultos.relatorios.edit') || $user->hasPermission('cultos.relatorios.create'))
                ? null
                : $denyAccess('Acesso negado. Sem permissão para editar relatórios de culto.');
        }

        if ($routeName === 'cultos.destroy') {
            $report = $request->route('serviceReport');
            return $report && $this->policy->delete($user, $report)
                ? null
                : $denyAccess('Acesso negado. Sem permissão para excluir relatórios de culto.');
        }

        return $this->policy->viewAny($user)
            ? null
            : $denyAccess('Acesso negado. Sem permissão para visualizar relatórios de culto.');
    }
}
