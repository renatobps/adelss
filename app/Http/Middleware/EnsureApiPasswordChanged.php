<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->must_change_password) {
            return ApiResponse::error('É necessário definir uma nova senha.', 403, [
                'code' => 'must_change_password',
            ]);
        }

        return $next($request);
    }
}
