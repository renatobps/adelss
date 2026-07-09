<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Policies\Servico\DepartmentPolicy;
use App\Policies\Servico\ServiceAreaPolicy;
use App\Policies\Servico\ServiceHistoryPolicy;
use App\Policies\Servico\ServiceSchedulePolicy;
use App\Policies\Servico\ServicoModulePolicy;
use App\Policies\Servico\VolunteerAvailabilityPolicy;
use App\Policies\Servico\VolunteerPolicy;
use App\Policies\Servico\VolunteerReportPolicy;
use App\Services\Authorization\Concerns\AuthorizesCrudRoute;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServicoRouteAuthorizer
{
    use AuthorizesCrudRoute;

    public function __construct(
        private DepartmentPolicy $departmentPolicy,
        private VolunteerPolicy $volunteerPolicy,
        private ServiceAreaPolicy $serviceAreaPolicy,
        private VolunteerAvailabilityPolicy $volunteerAvailabilityPolicy,
        private ServiceSchedulePolicy $serviceSchedulePolicy,
        private ServiceHistoryPolicy $serviceHistoryPolicy,
        private VolunteerReportPolicy $volunteerReportPolicy,
        private ServicoModulePolicy $servicoModulePolicy,
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

        if (str_starts_with($routeName, 'departments')) {
            return $this->authorizeCrudRoute(
                $user,
                $this->departmentPolicy,
                $action,
                'Acesso negado. Você não tem permissão para acessar departamentos.',
                $denyAccess
            );
        }

        if (str_starts_with($routeName, 'voluntarios.cadastro')) {
            return $this->authorizeCrudRoute(
                $user,
                $this->volunteerPolicy,
                $action,
                'Acesso negado. Você não tem permissão para acessar voluntários.',
                $denyAccess
            );
        }

        if (str_starts_with($routeName, 'voluntarios.areas')) {
            return $this->authorizeCrudRoute(
                $user,
                $this->serviceAreaPolicy,
                $action,
                'Acesso negado. Você não tem permissão para acessar áreas de serviço.',
                $denyAccess
            );
        }

        if (str_starts_with($routeName, 'voluntarios.disponibilidade')) {
            return $this->authorizeCrudRoute(
                $user,
                $this->volunteerAvailabilityPolicy,
                $action,
                'Acesso negado. Você não tem permissão para acessar disponibilidade.',
                $denyAccess
            );
        }

        if (str_starts_with($routeName, 'voluntarios.escalas')
            || str_starts_with($routeName, 'voluntarios.escalas-mensais')) {
            $scheduleAction = in_array($routeName, [
                'voluntarios.escalas.store.step1',
                'voluntarios.escalas.store.step2',
                'voluntarios.escalas.store.step3',
            ], true) ? 'store' : $action;

            return $this->authorizeCrudRoute(
                $user,
                $this->serviceSchedulePolicy,
                $scheduleAction,
                'Acesso negado. Você não tem permissão para acessar escalas.',
                $denyAccess
            );
        }

        if (str_starts_with($routeName, 'voluntarios.historico')) {
            return $this->serviceHistoryPolicy->authorizeRouteAction($user, $action)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar histórico de serviço.');
        }

        if (str_starts_with($routeName, 'voluntarios.relatorios')) {
            return $this->volunteerReportPolicy->authorizeRouteAction($user, $action)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar relatórios.');
        }

        return $this->servicoModulePolicy->accessAny($user)
            ? null
            : $denyAccess('Acesso negado. Você não tem permissão para acessar este módulo.');
    }
}
