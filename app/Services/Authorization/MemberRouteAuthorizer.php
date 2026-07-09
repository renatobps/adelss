<?php

namespace App\Services\Authorization;

use App\Models\Member;
use App\Models\User;
use App\Policies\Members\MemberPolicy;
use App\Policies\Members\MemberRolePolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MemberRouteAuthorizer
{
    public function __construct(
        private MemberPolicy $memberPolicy,
        private MemberRolePolicy $memberRolePolicy,
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

        if (str_starts_with($routeName ?? '', 'member-roles')) {
            return $this->authorizeMemberRoleRoute($user, $routeName, $action, $denyAccess);
        }

        return $this->authorizeMemberRoute($request, $user, $routeName, $action, $denyAccess);
    }

    /**
     * @param  callable(string): Response  $denyAccess
     */
    private function authorizeMemberRoute(
        Request $request,
        User $user,
        ?string $routeName,
        string $action,
        callable $denyAccess
    ): ?Response {
        $member = $this->resolveMember($request);

        if ($routeName === 'members.index' || $action === 'index') {
            return $this->memberPolicy->viewAny($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar membros.');
        }

        if ($routeName === 'members.show' || $action === 'show') {
            return $member && $this->memberPolicy->view($user, $member)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar membros.');
        }

        if (in_array($routeName, ['members.create', 'members.store'], true) || in_array($action, ['create', 'store'], true)) {
            return $this->memberPolicy->create($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para criar membros.');
        }

        if (in_array($routeName, ['members.edit', 'members.update'], true) || in_array($action, ['edit', 'update'], true)) {
            return $member && $this->memberPolicy->update($user, $member)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para editar membros.');
        }

        if ($routeName === 'members.destroy' || $action === 'destroy') {
            return $member && $this->memberPolicy->delete($user, $member)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para remover membros.');
        }

        if ($routeName === 'members.import' || $action === 'import') {
            return $this->memberPolicy->import($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para importar membros.');
        }

        if (in_array($routeName, ['members.import.tutorial', 'members.import.template'], true)
            || in_array($action, ['importTutorial', 'downloadTemplate'], true)) {
            return $this->memberPolicy->viewAny($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para acessar este módulo.');
        }

        return $this->memberPolicy->viewAny($user)
            ? null
            : $denyAccess('Acesso negado. Você não tem permissão para acessar este módulo.');
    }

    /**
     * @param  callable(string): Response  $denyAccess
     */
    private function authorizeMemberRoleRoute(
        User $user,
        ?string $routeName,
        string $action,
        callable $denyAccess
    ): ?Response {
        if (in_array($action, ['index', 'show'], true)) {
            return $this->memberRolePolicy->viewAny($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar cargos.');
        }

        if (in_array($action, ['create', 'store'], true)) {
            return $this->memberRolePolicy->create($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para criar cargos.');
        }

        if (in_array($action, ['edit', 'update'], true)) {
            return $this->memberRolePolicy->update($user, new \App\Models\MemberRole())
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para editar cargos.');
        }

        if ($action === 'destroy') {
            return $this->memberRolePolicy->delete($user, new \App\Models\MemberRole())
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para remover cargos.');
        }

        if ($routeName === 'member-roles.import' || $action === 'import') {
            return $this->memberRolePolicy->import($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para importar cargos.');
        }

        if ($routeName === 'member-roles.import.template' || $action === 'downloadTemplate') {
            return $this->memberRolePolicy->downloadTemplate($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar cargos.');
        }

        return $this->memberRolePolicy->viewAny($user)
            ? null
            : $denyAccess('Acesso negado. Você não tem permissão para acessar cargos.');
    }

    private function resolveMember(Request $request): ?Member
    {
        $member = $request->route('member');

        if ($member instanceof Member) {
            return $member;
        }

        if ($member) {
            return Member::find($member);
        }

        return null;
    }
}
