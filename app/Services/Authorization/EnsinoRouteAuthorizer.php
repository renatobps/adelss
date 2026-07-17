<?php

namespace App\Services\Authorization;

use App\Models\Turma;
use App\Models\User;
use App\Policies\Ensino\EnsinoModulePolicy;
use App\Policies\Ensino\EscolaPolicy;
use App\Policies\Ensino\EstudoPolicy;
use App\Policies\Ensino\TurmaPolicy;
use App\Services\Authorization\Concerns\AuthorizesCrudRoute;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsinoRouteAuthorizer
{
    use AuthorizesCrudRoute;

    public function __construct(
        private EstudoPolicy $estudoPolicy,
        private EscolaPolicy $escolaPolicy,
        private TurmaPolicy $turmaPolicy,
        private EnsinoModulePolicy $ensinoModulePolicy,
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
        $turma = $this->resolveTurma($request);

        if (str_starts_with($routeName, 'ensino.estudos.formularios')) {
            return $this->estudoPolicy->authorizeFormRouteAction($user, $action)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para gerenciar formulários de estudos.');
        }

        if (str_starts_with($routeName, 'ensino.estudos')) {
            return $this->estudoPolicy->authorizeRouteAction($user, $action)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para acessar estudos.');
        }

        if (str_starts_with($routeName, 'ensino.escolas')) {
            return $this->authorizeCrudRoute(
                $user,
                $this->escolaPolicy,
                $action,
                'Acesso negado. Você não tem permissão para acessar escolas.',
                $denyAccess
            );
        }

        if (str_starts_with($routeName, 'ensino.turmas')) {
            if ($routeName === 'ensino.turmas.index' || $action === 'index') {
                return $this->turmaPolicy->viewAny($user)
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para visualizar turmas.');
            }

            if ($routeName === 'ensino.turmas.show' || $action === 'show') {
                return $turma && $this->turmaPolicy->view($user, $turma)
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para visualizar esta turma.');
            }

            if ($routeName === 'ensino.turmas.lessons.store' || $action === 'storeLesson') {
                return $turma && $this->turmaPolicy->manageLesson($user, $turma)
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para registrar aulas.');
            }

            if ($routeName === 'ensino.turmas.files.store' || $action === 'storeFile') {
                return $turma && $this->turmaPolicy->manageFile($user, $turma)
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para adicionar arquivos.');
            }

            return $this->authorizeCrudRoute(
                $user,
                $this->turmaPolicy,
                $action,
                'Acesso negado. Você não tem permissão para acessar turmas.',
                $denyAccess
            );
        }

        return $this->ensinoModulePolicy->accessAny($user)
            ? null
            : $denyAccess('Acesso negado. Você não tem permissão para acessar este módulo.');
    }

    private function resolveTurma(Request $request): ?Turma
    {
        $turma = $request->route('turma');

        if ($turma instanceof Turma) {
            return $turma;
        }

        if ($turma) {
            return Turma::find($turma);
        }

        return null;
    }
}
