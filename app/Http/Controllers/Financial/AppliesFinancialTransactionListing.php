<?php

namespace App\Http\Controllers\Financial;

use Illuminate\Http\Request;

trait AppliesFinancialTransactionListing
{
    private function applyListingFilters($query, Request $request, string $startDate, string $endDate): void
    {
        $query->whereBetween('transaction_date', [$startDate, $endDate]);

        $type = $request->input('type', []);
        if (! empty($type)) {
            $query->whereIn('type', is_array($type) ? $type : [$type]);
        }

        $status = $request->input('status', []);
        if (! empty($status)) {
            $query->whereIn('status', is_array($status) ? $status : [$status]);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->input('account_id'));
        }

        if ($request->filled('cost_center_id')) {
            $query->where('cost_center_id', $request->input('cost_center_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $categoryReceitasId = $request->input('category_receitas_id');
        $categoryDespesasId = $request->input('category_despesas_id');
        if ($categoryReceitasId || $categoryDespesasId) {
            $query->where(function ($q) use ($categoryReceitasId, $categoryDespesasId) {
                if ($categoryReceitasId && $categoryDespesasId) {
                    $q->where(function ($subQ) use ($categoryReceitasId) {
                        $subQ->where('type', 'receita')->where('category_id', $categoryReceitasId);
                    })->orWhere(function ($subQ) use ($categoryDespesasId) {
                        $subQ->where('type', 'despesa')->where('category_id', $categoryDespesasId);
                    });
                } elseif ($categoryReceitasId) {
                    $q->where(function ($subQ) use ($categoryReceitasId) {
                        $subQ->where('type', 'receita')->where('category_id', $categoryReceitasId);
                    })->orWhere('type', 'despesa');
                } else {
                    $q->where('type', 'receita')
                        ->orWhere(function ($subQ) use ($categoryDespesasId) {
                            $subQ->where('type', 'despesa')->where('category_id', $categoryDespesasId);
                        });
                }
            });
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%'.$request->input('search').'%');
        }
    }

    private function applyListingSort($query, Request $request): void
    {
        $sort = $request->input('sort', 'date');
        $dir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columns = [
            'date' => 'transaction_date',
            'description' => 'description',
            'amount' => 'amount',
        ];
        $column = $columns[$sort] ?? 'transaction_date';

        $query->orderBy($column, $dir)->orderBy('id', $dir);
    }
}
