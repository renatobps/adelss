<?php

namespace App\Services\Authorization\Concerns;

use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

trait AuthorizesCrudRoute
{
    /**
     * @param  callable(string): Response  $denyAccess
     */
    protected function authorizeCrudRoute(
        User $user,
        object $policy,
        string $action,
        string $denyMessage,
        callable $denyAccess
    ): ?Response {
        if ($user->is_admin) {
            return null;
        }

        if (method_exists($policy, 'authorizeRouteAction') && $policy->authorizeRouteAction($user, $action)) {
            return null;
        }

        return $denyAccess($denyMessage);
    }
}
