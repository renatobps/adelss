<?php

namespace App\Services\Authorization;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MidiaRouteAuthorizer
{
    /**
     * @param  callable(string): Response  $denyAccess
     */
    public function authorize(Request $request, User $user, callable $denyAccess): ?Response
    {
        if ($user->is_admin) {
            return null;
        }

        $route = $request->route()?->getName() ?? '';

        if ($route === 'midia.settings') {
            return ($user->hasPermission('midia.configuracoes.manage')
                || $user->hasPermission('midia.instagram.configuracoes.manage'))
                ? null
                : $denyAccess('Acesso negado. Sem permissão para configurações de Mídia.');
        }

        if (str_starts_with($route, 'midia.google.')) {
            return $user->hasPermission('midia.configuracoes.manage')
                ? null
                : $denyAccess('Acesso negado. Sem permissão para configurações do Google Drive.');
        }

        if (str_starts_with($route, 'midia.instagram.redirect')
            || str_starts_with($route, 'midia.instagram.callback')
            || str_starts_with($route, 'midia.instagram.disconnect')) {
            return $user->hasPermission('midia.instagram.configuracoes.manage')
                ? null
                : $denyAccess('Acesso negado. Sem permissão para configurações do Instagram.');
        }

        if (str_starts_with($route, 'midia.instagram.posts')) {
            if (in_array($route, ['midia.instagram.posts.index', 'midia.instagram.posts.show'], true)
                || str_ends_with($route, '.index') || str_ends_with($route, '.show')) {
                return $user->hasPermission('midia.instagram.view')
                    ? null
                    : $denyAccess('Acesso negado. Sem permissão para ver publicações do Instagram.');
            }

            return $user->hasPermission('midia.instagram.schedule')
                ? null
                : $denyAccess('Acesso negado. Sem permissão para agendar publicações.');
        }

        if ($route === 'midia.folders.store') {
            return $user->hasPermission('midia.pastas.manage')
                ? null
                : $denyAccess('Acesso negado. Sem permissão para gerenciar pastas.');
        }

        if ($route === 'midia.upload') {
            return $user->hasPermission('midia.arquivos.upload')
                ? null
                : $denyAccess('Acesso negado. Sem permissão para upload.');
        }

        if ($route === 'midia.destroy') {
            return $user->hasPermission('midia.arquivos.delete')
                ? null
                : $denyAccess('Acesso negado. Sem permissão para excluir arquivos.');
        }

        if (in_array($route, ['midia.index', 'midia.download', 'midia.move'], true)) {
            return $user->hasPermission('midia.arquivos.view')
                ? null
                : $denyAccess('Acesso negado. Sem permissão para ver arquivos.');
        }

        return ($user->hasPermission('midia.arquivos.view')
            || $user->hasPermission('midia.instagram.view')
            || $user->hasPermission('midia.configuracoes.manage')
            || $user->hasPermission('midia.instagram.configuracoes.manage'))
            ? null
            : $denyAccess('Acesso negado ao módulo Mídia.');
    }
}
