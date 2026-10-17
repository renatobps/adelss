<?php

namespace App\Http\Controllers\Api\V1;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ResolvesFinancialApi
{
    protected function denyFinancial(string $message = 'Você não tem acesso ao módulo financeiro.'): JsonResponse
    {
        return ApiResponse::error($message, 403, ['code' => 'financial_forbidden']);
    }

    protected function guardFinancial(Request $request, string $ability, mixed $arguments = null): ?JsonResponse
    {
        $user = $request->user();
        if ($arguments === null) {
            if ($user->cannot($ability)) {
                return $this->denyFinancial();
            }

            return null;
        }

        if ($user->cannot($ability, $arguments)) {
            return $this->denyFinancial();
        }

        return null;
    }
}
