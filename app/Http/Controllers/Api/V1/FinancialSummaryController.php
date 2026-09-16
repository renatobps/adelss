<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialSummaryController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        if (! $request->user()->can('financial.view-summary')) {
            return ApiResponse::error('Você não tem acesso ao resumo financeiro.', 403, [
                'code' => 'financial_forbidden',
            ]);
        }

        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $entradas = (float) FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        $saidas = (float) FinancialTransaction::despesas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        $accounts = FinancialAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (FinancialAccount $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'label' => $account->paymentOptionLabel(),
                'type' => $account->type,
                'balance' => round($account->currentBalance(), 2),
            ])
            ->all();

        return ApiResponse::success([
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => $start->translatedFormat('F Y'),
            ],
            'in_total' => round($entradas, 2),
            'out_total' => round($saidas, 2),
            'result' => round($entradas - $saidas, 2),
            'accounts' => $accounts,
        ]);
    }
}
