<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->is_admin) {
            return $next($request);
        }

        $denyAccess = function ($message) use ($request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 403);
            }

            $redirectUrl = $request->headers->get('referer') ?: route('dashboard');

            return redirect($redirectUrl)->with('access_denied', $message);
        };

        switch ($module) {
            case 'members':
                $denied = app(\App\Services\Authorization\MemberRouteAuthorizer::class)
                    ->authorize($request, $user, $denyAccess);
                break;

            case 'pgis':
                $denied = app(\App\Services\Authorization\PgiRouteAuthorizer::class)
                    ->authorize($request, $user, $denyAccess);
                break;

            case 'ensino':
                $denied = app(\App\Services\Authorization\EnsinoRouteAuthorizer::class)
                    ->authorize($request, $user, $denyAccess);
                break;

            case 'agenda':
                $denied = app(\App\Services\Authorization\AgendaRouteAuthorizer::class)
                    ->authorize($request, $user, $denyAccess);
                break;

            case 'financial':
                $denied = app(\App\Services\Authorization\FinancialRouteAuthorizer::class)
                    ->authorize($request, $user, $denyAccess);
                break;

            case 'servico':
                $denied = app(\App\Services\Authorization\ServicoRouteAuthorizer::class)
                    ->authorize($request, $user, $denyAccess);
                break;

            case 'rifas':
                $denied = app(\App\Services\Authorization\RifaRouteAuthorizer::class)
                    ->authorize($request, $user, $denyAccess);
                break;

            case 'notificacoes':
                $denied = app(\App\Services\Authorization\NotificacaoRouteAuthorizer::class)
                    ->authorize($request, $user, $denyAccess);
                break;

            case 'moriah':
                $denied = app(\App\Services\Authorization\MoriahRouteAuthorizer::class)
                    ->authorize($request, $user, $denyAccess);
                break;

            case 'discipleship':
                $denied = app(\App\Services\Authorization\DiscipleshipRouteAuthorizer::class)
                    ->authorize($request, $user, $denyAccess);
                break;

            case 'pagina-principal':
                if (!$user->hasPermission('pagina-principal.manage')) {
                    return $denyAccess('Você não tem permissão para gerenciar a página principal.');
                }
                $denied = null;
                break;

            default:
                return $denyAccess('Acesso negado.');
        }

        if ($denied) {
            return $denied;
        }

        return $next($request);
    }
}
