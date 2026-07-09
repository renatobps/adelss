<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Policies\Moriah\MoriahPolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MoriahRouteAuthorizer
{
    public function __construct(
        private MoriahPolicy $moriahPolicy,
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

        if ($this->moriahPolicy->isViewOnlyRoute($routeName)) {
            return null;
        }

        return $this->moriahPolicy->manage($user)
            ? null
            : $denyAccess('Acesso negado. Você não tem permissão para gerenciar o Moriah.');
    }
}
