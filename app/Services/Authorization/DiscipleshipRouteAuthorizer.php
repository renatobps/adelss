<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Policies\Discipleship\DiscipleshipCyclePolicy;
use App\Policies\Discipleship\DiscipleshipFeedbackPolicy;
use App\Policies\Discipleship\DiscipleshipGoalPolicy;
use App\Policies\Discipleship\DiscipleshipIndicatorPolicy;
use App\Policies\Discipleship\DiscipleshipMeetingPolicy;
use App\Policies\Discipleship\DiscipleshipMemberPolicy;
use App\Policies\Discipleship\DiscipleshipModulePolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiscipleshipRouteAuthorizer
{
    public function __construct(
        private DiscipleshipModulePolicy $modulePolicy,
        private DiscipleshipCyclePolicy $cyclePolicy,
        private DiscipleshipMemberPolicy $memberPolicy,
        private DiscipleshipMeetingPolicy $meetingPolicy,
        private DiscipleshipIndicatorPolicy $indicatorPolicy,
        private DiscipleshipGoalPolicy $goalPolicy,
        private DiscipleshipFeedbackPolicy $feedbackPolicy,
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

        if (str_starts_with($routeName, 'discipleship.dashboard') || $routeName === 'discipleship.help') {
            return $this->checkResource($user, $this->modulePolicy, $action, $routeName, $denyAccess,
                'Acesso negado. Você não tem permissão para acessar o discipulado.');
        }

        if (str_starts_with($routeName, 'discipleship.cycles')) {
            return $this->checkResource($user, $this->cyclePolicy, $action, $routeName, $denyAccess,
                'Acesso negado. Você não tem permissão para visualizar ciclos.',
                'Acesso negado. Você não tem permissão para gerenciar ciclos.');
        }

        if (str_starts_with($routeName, 'discipleship.members')) {
            return $this->checkResource($user, $this->memberPolicy, $action, $routeName, $denyAccess,
                'Acesso negado. Você não tem permissão para visualizar membros do discipulado.',
                'Acesso negado. Você não tem permissão para gerenciar membros do discipulado.');
        }

        if (str_starts_with($routeName, 'discipleship.meetings')) {
            return $this->checkResource($user, $this->meetingPolicy, $action, $routeName, $denyAccess,
                'Acesso negado. Você não tem permissão para visualizar encontros.',
                'Acesso negado. Você não tem permissão para gerenciar encontros.');
        }

        if (str_starts_with($routeName, 'discipleship.indicators')) {
            return $this->checkResource($user, $this->indicatorPolicy, $action, $routeName, $denyAccess,
                'Acesso negado. Você não tem permissão para visualizar indicadores.',
                'Acesso negado. Você não tem permissão para gerenciar indicadores.');
        }

        if (str_starts_with($routeName, 'discipleship.goals')) {
            return $this->checkResource($user, $this->goalPolicy, $action, $routeName, $denyAccess,
                'Acesso negado. Você não tem permissão para visualizar propósitos.',
                'Acesso negado. Você não tem permissão para gerenciar propósitos.');
        }

        if (str_starts_with($routeName, 'discipleship.feedbacks')) {
            return $this->checkResource($user, $this->feedbackPolicy, $action, $routeName, $denyAccess,
                'Acesso negado. Você não tem permissão para visualizar feedbacks.',
                'Acesso negado. Você não tem permissão para gerenciar feedbacks.');
        }

        return $this->modulePolicy->viewAny($user)
            ? null
            : $denyAccess('Acesso negado. Você não tem permissão para acessar o discipulado.');
    }

    /**
     * @param  callable(string): Response  $denyAccess
     */
    private function checkResource(
        User $user,
        object $policy,
        string $action,
        string $routeName,
        callable $denyAccess,
        string $viewMessage,
        ?string $manageMessage = null
    ): ?Response {
        $manageMessage ??= $viewMessage;

        $isReadAction = in_array($action, ['index', 'show', 'discipulador', 'lideranca', 'help', 'generatePdf'], true)
            || in_array($routeName, ['discipleship.help', 'discipleship.goals.pdf'], true);

        if ($isReadAction) {
            if ($policy instanceof DiscipleshipModulePolicy) {
                return $policy->viewAny($user) ? null : $denyAccess($viewMessage);
            }

            return $policy->viewAny($user) ? null : $denyAccess($viewMessage);
        }

        if ($policy instanceof DiscipleshipModulePolicy) {
            return $policy->manage($user) ? null : $denyAccess($manageMessage);
        }

        return $policy->authorizeRouteAction($user, $action)
            ? null
            : $denyAccess($manageMessage);
    }
}
