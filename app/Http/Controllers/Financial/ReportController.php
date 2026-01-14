<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;
use App\Models\FinancialAccount;
use App\Models\FinancialCostCenter;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display reports index page
     */
    public function index()
    {
        return view('financial.reports.index');
    }

    /**
     * Cash Flow - Extract Report
     */
    public function cashFlowExtract(Request $request)
    {
        // Filtros
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $type = $request->input('type', []); // Array de tipos
        $status = $request->input('status', []); // Array de status
        $accountId = $request->input('account_id');
        $costCenterId = $request->input('cost_center_id');
        $categoryReceitasId = $request->input('category_receitas_id');
        $categoryDespesasId = $request->input('category_despesas_id');
        
        // Query base
        $query = FinancialTransaction::with(['member', 'contact', 'category', 'account', 'costCenter'])
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        // Filtro por tipo (se especificado)
        if (!empty($type)) {
            if (is_array($type)) {
                $query->whereIn('type', $type);
            } else {
                $query->where('type', $type);
            }
        }

        // Filtro por status (se especificado)
        if (!empty($status)) {
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }

        // Filtro por conta
        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        // Filtro por centro de custo
        if ($costCenterId) {
            $query->where('cost_center_id', $costCenterId);
        }

        // Filtro por categoria de receitas ou despesas
        if ($categoryReceitasId || $categoryDespesasId) {
            $query->where(function($q) use ($categoryReceitasId, $categoryDespesasId) {
                // Se ambos os filtros estão definidos
                if ($categoryReceitasId && $categoryDespesasId) {
                    // Receitas da categoria selecionada OU Despesas da categoria selecionada
                    $q->where(function($subQ) use ($categoryReceitasId) {
                        $subQ->where('type', 'receita')->where('category_id', $categoryReceitasId);
                    })->orWhere(function($subQ) use ($categoryDespesasId) {
                        $subQ->where('type', 'despesa')->where('category_id', $categoryDespesasId);
                    });
                } elseif ($categoryReceitasId) {
                    // Apenas categoria de receitas: mostrar receitas dessa categoria + todas despesas
                    $q->where(function($subQ) use ($categoryReceitasId) {
                        $subQ->where('type', 'receita')->where('category_id', $categoryReceitasId);
                    })->orWhere('type', 'despesa');
                } elseif ($categoryDespesasId) {
                    // Apenas categoria de despesas: mostrar todas receitas + despesas dessa categoria
                    $q->where('type', 'receita')
                      ->orWhere(function($subQ) use ($categoryDespesasId) {
                          $subQ->where('type', 'despesa')->where('category_id', $categoryDespesasId);
                      });
                }
            });
        }

        // Busca por descrição
        if ($request->has('search') && $request->search) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        // Ordenação
        $sortBy = $request->input('sort_by', 'transaction_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 100);
        $transactions = $query->paginate($perPage);

        // Dados para o gráfico
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $periodTransactions = FinancialTransaction::whereBetween('transaction_date', [$startDate, $endDate])->get();
        
        $chartData = [];
        $currentDate = $start->copy();
        
        while ($currentDate <= $end) {
            $dateStr = $currentDate->format('Y-m-d');
            
            $dayTransactions = $periodTransactions->filter(function ($transaction) use ($dateStr) {
                return $transaction->transaction_date && $transaction->transaction_date->format('Y-m-d') === $dateStr;
            });
            
            $receitasPagas = $dayTransactions->filter(function ($t) {
                return $t->type === 'receita' && $t->is_paid;
            })->sum('amount');
            
            $despesasPagas = $dayTransactions->filter(function ($t) {
                return $t->type === 'despesa' && $t->is_paid;
            })->sum('amount');
            
            $aReceber = $dayTransactions->filter(function ($t) {
                return $t->type === 'receita' && !$t->is_paid;
            })->sum('amount');
            
            $aPagar = $dayTransactions->filter(function ($t) {
                return $t->type === 'despesa' && !$t->is_paid;
            })->sum('amount');
            
            $chartData[] = [
                'date' => $currentDate->format('d/m/Y'),
                'day' => (int)$currentDate->format('d'),
                'receitas' => (float) $receitasPagas,
                'despesas' => (float) $despesasPagas,
                'a_receber' => (float) $aReceber,
                'a_pagar' => (float) $aPagar,
            ];
            
            $currentDate->addDay();
        }

        // Calcular saldo anterior (último saldo antes do período)
        $previousPeriodEnd = Carbon::parse($startDate)->subDay();
        $previousBalance = $this->calculateBalance($previousPeriodEnd->format('Y-m-d'));

        // Calcular totais do período (aplicar os mesmos filtros da query)
        $totalReceitasQuery = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', true);
        
        if ($categoryReceitasId) {
            $totalReceitasQuery->where('category_id', $categoryReceitasId);
        }
        
        $totalReceitas = $totalReceitasQuery->sum('amount');
        
        $totalDespesasQuery = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', true);
        
        if ($categoryDespesasId) {
            $totalDespesasQuery->where('category_id', $categoryDespesasId);
        }
        
        $totalDespesas = $totalDespesasQuery->sum('amount');

        $aReceberQuery = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', false);
        
        if ($categoryReceitasId) {
            $aReceberQuery->where('category_id', $categoryReceitasId);
        }
        
        $aReceber = $aReceberQuery->sum('amount');

        $aPagarQuery = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', false);
        
        if ($categoryDespesasId) {
            $aPagarQuery->where('category_id', $categoryDespesasId);
        }
        
        $aPagar = $aPagarQuery->sum('amount');

        // Transferências (por enquanto 0, pois ainda não temos esse módulo)
        $transferenciasEnviadas = 0;
        $transferenciasRecebidas = 0;

        // Calcular saldo final
        $saldoFinal = $previousBalance + $totalReceitas - $totalDespesas + $aReceber - $aPagar - $transferenciasEnviadas + $transferenciasRecebidas;

        // Dados para filtros
        $categoriesReceitas = FinancialCategory::receitas()->orderBy('name')->get();
        $categoriesDespesas = FinancialCategory::despesas()->orderBy('name')->get();
        $accounts = FinancialAccount::orderBy('name')->get();
        $costCenters = FinancialCostCenter::orderBy('name')->get();

        return view('financial.reports.cash-flow-extract', compact(
            'transactions',
            'chartData',
            'startDate',
            'endDate',
            'previousBalance',
            'totalReceitas',
            'totalDespesas',
            'aReceber',
            'aPagar',
            'transferenciasEnviadas',
            'transferenciasRecebidas',
            'saldoFinal',
            'categoriesReceitas',
            'categoriesDespesas',
            'accounts',
            'costCenters'
        ));
    }

    /**
     * Cash Flow - Revenues / Expenses Report
     */
    public function cashFlowRevenuesExpenses(Request $request)
    {
        // Filtros
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $type = $request->input('type', []);
        if (!is_array($type) && $type) {
            $type = [$type];
        }
        $status = $request->input('status', []);
        if (!is_array($status) && $status) {
            $status = [$status];
        }
        $accountId = $request->input('account_id');
        $costCenterId = $request->input('cost_center_id');
        $categoryReceitasId = $request->input('category_receitas_id');
        $categoryDespesasId = $request->input('category_despesas_id');
        
        // Query para receitas
        $receitasQuery = FinancialTransaction::with(['member', 'category', 'account', 'costCenter'])
            ->receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        // Query para despesas
        $despesasQuery = FinancialTransaction::with(['contact', 'category', 'account', 'costCenter'])
            ->despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        // Aplicar filtros comuns
        if (!empty($status)) {
            $receitasQuery->whereIn('status', $status);
            $despesasQuery->whereIn('status', $status);
        }

        if ($accountId) {
            $receitasQuery->where('account_id', $accountId);
            $despesasQuery->where('account_id', $accountId);
        }

        if ($costCenterId) {
            $receitasQuery->where('cost_center_id', $costCenterId);
            $despesasQuery->where('cost_center_id', $costCenterId);
        }

        // Filtro por categoria de receitas
        if ($categoryReceitasId) {
            $receitasQuery->where('category_id', $categoryReceitasId);
        }

        // Filtro por categoria de despesas
        if ($categoryDespesasId) {
            $despesasQuery->where('category_id', $categoryDespesasId);
        }

        // Busca por descrição
        if ($request->has('search_receitas') && $request->search_receitas) {
            $receitasQuery->where('description', 'like', '%' . $request->search_receitas . '%');
        }

        if ($request->has('search_despesas') && $request->search_despesas) {
            $despesasQuery->where('description', 'like', '%' . $request->search_despesas . '%');
        }

        // Ordenação
        $sortByReceitas = $request->input('sort_by_receitas', 'transaction_date');
        $sortOrderReceitas = $request->input('sort_order_receitas', 'desc');
        $receitasQuery->orderBy($sortByReceitas, $sortOrderReceitas);

        $sortByDespesas = $request->input('sort_by_despesas', 'transaction_date');
        $sortOrderDespesas = $request->input('sort_order_despesas', 'desc');
        $despesasQuery->orderBy($sortByDespesas, $sortOrderDespesas);

        // Paginação separada para receitas e despesas
        $perPageReceitas = $request->input('per_page_receitas', 100);
        $perPageDespesas = $request->input('per_page_despesas', 100);
        $receitas = $receitasQuery->paginate($perPageReceitas, ['*'], 'receitas_page')
            ->withQueryString();
        $despesas = $despesasQuery->paginate($perPageDespesas, ['*'], 'despesas_page')
            ->withQueryString();

        // Calcular saldo anterior
        $previousPeriodEnd = Carbon::parse($startDate)->subDay();
        $previousBalance = $this->calculateBalance($previousPeriodEnd->format('Y-m-d'));

        // Calcular totais do período
        $totalReceitasQuery = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', true);
        
        if ($categoryReceitasId) {
            $totalReceitasQuery->where('category_id', $categoryReceitasId);
        }
        
        $totalReceitas = $totalReceitasQuery->sum('amount');
        
        $totalDespesasQuery = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', true);
        
        if ($categoryDespesasId) {
            $totalDespesasQuery->where('category_id', $categoryDespesasId);
        }
        
        $totalDespesas = $totalDespesasQuery->sum('amount');

        $aReceberQuery = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', false);
        
        if ($categoryReceitasId) {
            $aReceberQuery->where('category_id', $categoryReceitasId);
        }
        
        $aReceber = $aReceberQuery->sum('amount');

        $aPagarQuery = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', false);
        
        if ($categoryDespesasId) {
            $aPagarQuery->where('category_id', $categoryDespesasId);
        }
        
        $aPagar = $aPagarQuery->sum('amount');

        // Transferências (por enquanto 0)
        $transferenciasEnviadas = 0;
        $transferenciasRecebidas = 0;

        // Calcular saldo final
        $saldoFinal = $previousBalance + $totalReceitas - $totalDespesas + $aReceber - $aPagar - $transferenciasEnviadas + $transferenciasRecebidas;

        // Dados para filtros
        $categoriesReceitas = FinancialCategory::receitas()->orderBy('name')->get();
        $categoriesDespesas = FinancialCategory::despesas()->orderBy('name')->get();
        $accounts = FinancialAccount::orderBy('name')->get();
        $costCenters = FinancialCostCenter::orderBy('name')->get();

        return view('financial.reports.cash-flow-revenues-expenses', compact(
            'receitas',
            'despesas',
            'startDate',
            'endDate',
            'previousBalance',
            'totalReceitas',
            'totalDespesas',
            'aReceber',
            'aPagar',
            'transferenciasEnviadas',
            'transferenciasRecebidas',
            'saldoFinal',
            'categoriesReceitas',
            'categoriesDespesas',
            'accounts',
            'costCenters'
        ));
    }

    /**
     * Receitas - Extrato diário
     */
    public function revenuesDailyExtract(Request $request)
    {
        // Filtros
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $categoryId = $request->input('category_id');
        $accountId = $request->input('account_id');
        $costCenterId = $request->input('cost_center_id');
        
        // Query para receitas
        $receitasQuery = FinancialTransaction::with(['member', 'category', 'account', 'costCenter'])
            ->receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        // Aplicar filtros
        if ($categoryId) {
            $receitasQuery->where('category_id', $categoryId);
        }

        if ($accountId) {
            $receitasQuery->where('account_id', $accountId);
        }

        if ($costCenterId) {
            $receitasQuery->where('cost_center_id', $costCenterId);
        }

        // Busca por descrição
        if ($request->has('search') && $request->search) {
            $receitasQuery->where('description', 'like', '%' . $request->search . '%');
        }

        // Ordenação
        $sortBy = $request->input('sort_by', 'transaction_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $receitasQuery->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = $request->input('per_page', 100);
        $receitas = $receitasQuery->paginate($perPage)->withQueryString();

        // Dados para o gráfico diário (por dia do período)
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $chartData = [];
        $currentDate = $start->copy();
        
        // Buscar todas as receitas do período de uma vez
        $periodReceitas = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate]);
        
        if ($categoryId) {
            $periodReceitas->where('category_id', $categoryId);
        }
        if ($accountId) {
            $periodReceitas->where('account_id', $accountId);
        }
        if ($costCenterId) {
            $periodReceitas->where('cost_center_id', $costCenterId);
        }
        
        $periodReceitas = $periodReceitas->get();
        
        // Agrupar por data
        while ($currentDate->lte($end)) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayReceitas = $periodReceitas->filter(function ($transaction) use ($dateStr) {
                return $transaction->transaction_date->format('Y-m-d') === $dateStr;
            });
            
            $receitasPagas = $dayReceitas->where('is_paid', true)->sum('amount');
            $aReceber = $dayReceitas->where('is_paid', false)->sum('amount');

            $chartData[] = [
                'date' => $currentDate->format('d/m/Y'),
                'day' => $currentDate->format('d/m'),
                'receitas' => (float) $receitasPagas,
                'a_receber' => (float) $aReceber,
            ];
            
            $currentDate->addDay();
        }

        // Calcular saldo anterior
        $previousPeriodEnd = Carbon::parse($startDate)->subDay();
        $previousBalance = $this->calculateBalance($previousPeriodEnd->format('Y-m-d'));

        // Calcular totais do período
        $totalReceitasQuery = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', true);
        
        if ($categoryId) {
            $totalReceitasQuery->where('category_id', $categoryId);
        }
        if ($accountId) {
            $totalReceitasQuery->where('account_id', $accountId);
        }
        if ($costCenterId) {
            $totalReceitasQuery->where('cost_center_id', $costCenterId);
        }
        
        $totalReceitas = $totalReceitasQuery->sum('amount');

        $aReceberQuery = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', false);
        
        if ($categoryId) {
            $aReceberQuery->where('category_id', $categoryId);
        }
        if ($accountId) {
            $aReceberQuery->where('account_id', $accountId);
        }
        if ($costCenterId) {
            $aReceberQuery->where('cost_center_id', $costCenterId);
        }
        
        $aReceber = $aReceberQuery->sum('amount');

        // Despesas (para o gráfico, mas não para a tabela)
        $periodDespesas = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();
        
        // Adicionar despesas ao gráfico
        $chartDate = $start->copy();
        foreach ($chartData as $index => $day) {
            $dateStr = $chartDate->format('Y-m-d');
            $dayDespesas = $periodDespesas->filter(function ($transaction) use ($dateStr) {
                return $transaction->transaction_date->format('Y-m-d') === $dateStr;
            });
            
            $chartData[$index]['despesas'] = (float) $dayDespesas->where('is_paid', true)->sum('amount');
            $chartData[$index]['a_pagar'] = (float) $dayDespesas->where('is_paid', false)->sum('amount');
            
            $chartDate->addDay();
        }

        $totalDespesas = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', true)
            ->sum('amount');

        $aPagar = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', false)
            ->sum('amount');

        // Transferências (por enquanto 0)
        $transferenciasEnviadas = 0;
        $transferenciasRecebidas = 0;

        // Calcular saldo final
        $saldoFinal = $previousBalance + $totalReceitas - $totalDespesas + $aReceber - $aPagar - $transferenciasEnviadas + $transferenciasRecebidas;

        // Dados para filtros
        $categoriesReceitas = FinancialCategory::receitas()->orderBy('name')->get();
        $accounts = FinancialAccount::orderBy('name')->get();
        $costCenters = FinancialCostCenter::orderBy('name')->get();

        return view('financial.reports.revenues-daily-extract', compact(
            'receitas',
            'chartData',
            'startDate',
            'endDate',
            'previousBalance',
            'totalReceitas',
            'totalDespesas',
            'aReceber',
            'aPagar',
            'transferenciasEnviadas',
            'transferenciasRecebidas',
            'saldoFinal',
            'categoriesReceitas',
            'accounts',
            'costCenters',
            'categoryId',
            'accountId',
            'costCenterId'
        ));
    }

    /**
     * Receitas e Despesas - Por categoria
     */
    public function revenuesExpensesByCategory(Request $request)
    {
        // Filtros
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $categoryReceitasId = $request->input('category_receitas_id');
        $categoryDespesasId = $request->input('category_despesas_id');
        $accountId = $request->input('account_id');
        $costCenterId = $request->input('cost_center_id');
        $status = $request->input('status', []);
        if (!is_array($status) && $status) {
            $status = [$status];
        }
        
        // Query para receitas
        $receitasQuery = FinancialTransaction::with(['member', 'category', 'account', 'costCenter'])
            ->receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        // Query para despesas
        $despesasQuery = FinancialTransaction::with(['contact', 'category', 'account', 'costCenter'])
            ->despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        // Aplicar filtros comuns
        if (!empty($status)) {
            $receitasQuery->whereIn('status', $status);
            $despesasQuery->whereIn('status', $status);
        }

        if ($accountId) {
            $receitasQuery->where('account_id', $accountId);
            $despesasQuery->where('account_id', $accountId);
        }

        if ($costCenterId) {
            $receitasQuery->where('cost_center_id', $costCenterId);
            $despesasQuery->where('cost_center_id', $costCenterId);
        }

        // Filtro por categoria de receitas
        if ($categoryReceitasId) {
            $receitasQuery->where('category_id', $categoryReceitasId);
        }

        // Filtro por categoria de despesas
        if ($categoryDespesasId) {
            $despesasQuery->where('category_id', $categoryDespesasId);
        }

        // Busca por descrição
        if ($request->has('search') && $request->search) {
            $receitasQuery->where('description', 'like', '%' . $request->search . '%');
            $despesasQuery->where('description', 'like', '%' . $request->search . '%');
        }

        // Ordenação
        $sortBy = $request->input('sort_by', 'transaction_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $receitasQuery->orderBy($sortBy, $sortOrder);
        $despesasQuery->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = $request->input('per_page', 100);
        $receitas = $receitasQuery->paginate($perPage, ['*'], 'receitas_page')->withQueryString();
        $despesas = $despesasQuery->paginate($perPage, ['*'], 'despesas_page')->withQueryString();

        // Dados para o gráfico diário (por dia do período)
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $chartData = [];
        $currentDate = $start->copy();
        
        // Buscar todas as transações do período
        $periodReceitas = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate]);
        
        if ($categoryReceitasId) {
            $periodReceitas->where('category_id', $categoryReceitasId);
        }
        if ($accountId) {
            $periodReceitas->where('account_id', $accountId);
        }
        if ($costCenterId) {
            $periodReceitas->where('cost_center_id', $costCenterId);
        }
        if (!empty($status)) {
            $periodReceitas->whereIn('status', $status);
        }
        
        $periodReceitas = $periodReceitas->get();
        
        $periodDespesas = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate]);
        
        if ($categoryDespesasId) {
            $periodDespesas->where('category_id', $categoryDespesasId);
        }
        if ($accountId) {
            $periodDespesas->where('account_id', $accountId);
        }
        if ($costCenterId) {
            $periodDespesas->where('cost_center_id', $costCenterId);
        }
        if (!empty($status)) {
            $periodDespesas->whereIn('status', $status);
        }
        
        $periodDespesas = $periodDespesas->get();
        
        // Agrupar por data
        while ($currentDate->lte($end)) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayReceitas = $periodReceitas->filter(function ($transaction) use ($dateStr) {
                return $transaction->transaction_date->format('Y-m-d') === $dateStr;
            });
            
            $dayDespesas = $periodDespesas->filter(function ($transaction) use ($dateStr) {
                return $transaction->transaction_date->format('Y-m-d') === $dateStr;
            });
            
            $receitasPagas = $dayReceitas->where('is_paid', true)->sum('amount');
            $aReceber = $dayReceitas->where('is_paid', false)->sum('amount');
            $despesasPagas = $dayDespesas->where('is_paid', true)->sum('amount');
            $aPagar = $dayDespesas->where('is_paid', false)->sum('amount');

            $chartData[] = [
                'date' => $currentDate->format('d/m/Y'),
                'day' => $currentDate->format('d/m'),
                'receitas' => (float) $receitasPagas,
                'despesas' => (float) $despesasPagas,
                'a_receber' => (float) $aReceber,
                'a_pagar' => (float) $aPagar,
            ];
            
            $currentDate->addDay();
        }

        // Calcular saldo anterior
        $previousPeriodEnd = Carbon::parse($startDate)->subDay();
        $previousBalance = $this->calculateBalance($previousPeriodEnd->format('Y-m-d'));

        // Calcular totais do período
        $totalReceitasQuery = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', true);
        
        if ($categoryReceitasId) {
            $totalReceitasQuery->where('category_id', $categoryReceitasId);
        }
        if ($accountId) {
            $totalReceitasQuery->where('account_id', $accountId);
        }
        if ($costCenterId) {
            $totalReceitasQuery->where('cost_center_id', $costCenterId);
        }
        if (!empty($status)) {
            $totalReceitasQuery->whereIn('status', $status);
        }
        
        $totalReceitas = $totalReceitasQuery->sum('amount');
        
        $totalDespesasQuery = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', true);
        
        if ($categoryDespesasId) {
            $totalDespesasQuery->where('category_id', $categoryDespesasId);
        }
        if ($accountId) {
            $totalDespesasQuery->where('account_id', $accountId);
        }
        if ($costCenterId) {
            $totalDespesasQuery->where('cost_center_id', $costCenterId);
        }
        if (!empty($status)) {
            $totalDespesasQuery->whereIn('status', $status);
        }
        
        $totalDespesas = $totalDespesasQuery->sum('amount');

        $aReceberQuery = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', false);
        
        if ($categoryReceitasId) {
            $aReceberQuery->where('category_id', $categoryReceitasId);
        }
        
        $aReceber = $aReceberQuery->sum('amount');

        $aPagarQuery = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', false);
        
        if ($categoryDespesasId) {
            $aPagarQuery->where('category_id', $categoryDespesasId);
        }
        
        $aPagar = $aPagarQuery->sum('amount');

        // Transferências (por enquanto 0)
        $transferenciasEnviadas = 0;
        $transferenciasRecebidas = 0;

        // Calcular saldo final
        $saldoFinal = $previousBalance + $totalReceitas - $totalDespesas + $aReceber - $aPagar - $transferenciasEnviadas + $transferenciasRecebidas;

        // Dados para filtros
        $categoriesReceitas = FinancialCategory::receitas()->orderBy('name')->get();
        $categoriesDespesas = FinancialCategory::despesas()->orderBy('name')->get();
        $accounts = FinancialAccount::orderBy('name')->get();
        $costCenters = FinancialCostCenter::orderBy('name')->get();

        return view('financial.reports.revenues-expenses-by-category', compact(
            'receitas',
            'despesas',
            'chartData',
            'startDate',
            'endDate',
            'previousBalance',
            'totalReceitas',
            'totalDespesas',
            'aReceber',
            'aPagar',
            'transferenciasEnviadas',
            'transferenciasRecebidas',
            'saldoFinal',
            'categoriesReceitas',
            'categoriesDespesas',
            'accounts',
            'costCenters',
            'categoryReceitasId',
            'categoryDespesasId',
            'accountId',
            'costCenterId',
            'status'
        ));
    }

    /**
     * Despesas - Extrato diário
     */
    public function expensesDailyExtract(Request $request)
    {
        // Filtros
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $categoryId = $request->input('category_id');
        $accountId = $request->input('account_id');
        $costCenterId = $request->input('cost_center_id');
        
        // Query para despesas
        $despesasQuery = FinancialTransaction::with(['contact', 'category', 'account', 'costCenter'])
            ->despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        // Aplicar filtros
        if ($categoryId) {
            $despesasQuery->where('category_id', $categoryId);
        }

        if ($accountId) {
            $despesasQuery->where('account_id', $accountId);
        }

        if ($costCenterId) {
            $despesasQuery->where('cost_center_id', $costCenterId);
        }

        // Busca por descrição
        if ($request->has('search') && $request->search) {
            $despesasQuery->where('description', 'like', '%' . $request->search . '%');
        }

        // Ordenação
        $sortBy = $request->input('sort_by', 'transaction_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $despesasQuery->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = $request->input('per_page', 100);
        $despesas = $despesasQuery->paginate($perPage)->withQueryString();

        // Dados para o gráfico diário (por dia do período)
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $chartData = [];
        $currentDate = $start->copy();
        
        // Buscar todas as despesas do período de uma vez
        $periodDespesas = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate]);
        
        if ($categoryId) {
            $periodDespesas->where('category_id', $categoryId);
        }
        if ($accountId) {
            $periodDespesas->where('account_id', $accountId);
        }
        if ($costCenterId) {
            $periodDespesas->where('cost_center_id', $costCenterId);
        }
        
        $periodDespesas = $periodDespesas->get();
        
        // Receitas (para o gráfico, mas não para a tabela)
        $periodReceitas = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();
        
        // Agrupar por data
        while ($currentDate->lte($end)) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayDespesas = $periodDespesas->filter(function ($transaction) use ($dateStr) {
                return $transaction->transaction_date->format('Y-m-d') === $dateStr;
            });
            
            $dayReceitas = $periodReceitas->filter(function ($transaction) use ($dateStr) {
                return $transaction->transaction_date->format('Y-m-d') === $dateStr;
            });
            
            $despesasPagas = $dayDespesas->where('is_paid', true)->sum('amount');
            $aPagar = $dayDespesas->where('is_paid', false)->sum('amount');
            $receitasPagas = $dayReceitas->where('is_paid', true)->sum('amount');
            $aReceber = $dayReceitas->where('is_paid', false)->sum('amount');

            $chartData[] = [
                'date' => $currentDate->format('d/m/Y'),
                'day' => $currentDate->format('d/m'),
                'despesas' => (float) $despesasPagas,
                'a_pagar' => (float) $aPagar,
                'receitas' => (float) $receitasPagas,
                'a_receber' => (float) $aReceber,
            ];
            
            $currentDate->addDay();
        }

        // Calcular saldo anterior
        $previousPeriodEnd = Carbon::parse($startDate)->subDay();
        $previousBalance = $this->calculateBalance($previousPeriodEnd->format('Y-m-d'));

        // Calcular totais do período
        $totalDespesasQuery = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', true);
        
        if ($categoryId) {
            $totalDespesasQuery->where('category_id', $categoryId);
        }
        if ($accountId) {
            $totalDespesasQuery->where('account_id', $accountId);
        }
        if ($costCenterId) {
            $totalDespesasQuery->where('cost_center_id', $costCenterId);
        }
        
        $totalDespesas = $totalDespesasQuery->sum('amount');

        $aPagarQuery = FinancialTransaction::despesas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', false);
        
        if ($categoryId) {
            $aPagarQuery->where('category_id', $categoryId);
        }
        if ($accountId) {
            $aPagarQuery->where('account_id', $accountId);
        }
        if ($costCenterId) {
            $aPagarQuery->where('cost_center_id', $costCenterId);
        }
        
        $aPagar = $aPagarQuery->sum('amount');

        // Receitas para o resumo
        $totalReceitas = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', true)
            ->sum('amount');

        $aReceber = FinancialTransaction::receitas()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_paid', false)
            ->sum('amount');

        // Transferências (por enquanto 0)
        $transferenciasEnviadas = 0;
        $transferenciasRecebidas = 0;

        // Calcular saldo final
        $saldoFinal = $previousBalance + $totalReceitas - $totalDespesas + $aReceber - $aPagar - $transferenciasEnviadas + $transferenciasRecebidas;

        // Dados para filtros
        $categoriesDespesas = FinancialCategory::despesas()->orderBy('name')->get();
        $accounts = FinancialAccount::orderBy('name')->get();
        $costCenters = FinancialCostCenter::orderBy('name')->get();

        return view('financial.reports.expenses-daily-extract', compact(
            'despesas',
            'chartData',
            'startDate',
            'endDate',
            'previousBalance',
            'totalReceitas',
            'totalDespesas',
            'aReceber',
            'aPagar',
            'transferenciasEnviadas',
            'transferenciasRecebidas',
            'saldoFinal',
            'categoriesDespesas',
            'accounts',
            'costCenters',
            'categoryId',
            'accountId',
            'costCenterId'
        ));
    }

    /**
     * Receitas - Resumo anual por categoria
     */
    public function revenuesAnnualSummary(Request $request)
    {
        // Ano selecionado
        $year = $request->input('year', now()->year);
        
        // Buscar todas as receitas do ano
        $receitas = FinancialTransaction::receitas()
            ->whereYear('transaction_date', $year)
            ->where('is_paid', true)
            ->with(['category'])
            ->get();

        // Agrupar por categoria
        $byCategory = $receitas->groupBy('category_id')->map(function ($group) {
            return [
                'category_name' => $group->first()->category ? $group->first()->category->name : 'Sem categoria',
                'total' => $group->sum('amount'),
                'count' => $group->count(),
            ];
        })->sortByDesc('total')->values();

        // Agrupar por mês
        $byMonth = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
            $monthEnd = Carbon::create($year, $month, 1)->endOfMonth();
            
            $monthReceitas = $receitas->filter(function ($transaction) use ($monthStart, $monthEnd) {
                return $transaction->transaction_date->gte($monthStart) && $transaction->transaction_date->lte($monthEnd);
            });
            
            $byMonth[$month] = [
                'month_name' => $monthStart->translatedFormat('F'),
                'total' => $monthReceitas->sum('amount'),
                'count' => $monthReceitas->count(),
                'by_category' => $monthReceitas->groupBy('category_id')->map(function ($group) {
                    return [
                        'category_name' => $group->first()->category ? $group->first()->category->name : 'Sem categoria',
                        'total' => $group->sum('amount'),
                    ];
                })->sortByDesc('total')->values(),
            ];
        }

        // Total geral
        $totalGeral = $receitas->sum('amount');

        // Dados para o gráfico mensal
        $chartData = collect($byMonth)->map(function ($month, $index) {
            return [
                'month' => $index,
                'month_name' => $month['month_name'],
                'total' => (float) $month['total'],
            ];
        })->values()->toArray();

        // Dados para filtros
        $years = range(now()->year - 5, now()->year + 1);
        $categoriesReceitas = FinancialCategory::receitas()->orderBy('name')->get();

        return view('financial.reports.revenues-annual-summary', compact(
            'year',
            'byCategory',
            'byMonth',
            'totalGeral',
            'chartData',
            'years',
            'categoriesReceitas'
        ));
    }

    /**
     * Despesas - Resumo anual por categoria
     */
    public function expensesAnnualSummary(Request $request)
    {
        // Ano selecionado
        $year = $request->input('year', now()->year);
        
        // Buscar todas as despesas do ano
        $despesas = FinancialTransaction::despesas()
            ->whereYear('transaction_date', $year)
            ->where('is_paid', true)
            ->with(['category'])
            ->get();

        // Agrupar por categoria
        $byCategory = $despesas->groupBy('category_id')->map(function ($group) {
            return [
                'category_name' => $group->first()->category ? $group->first()->category->name : 'Sem categoria',
                'total' => $group->sum('amount'),
                'count' => $group->count(),
            ];
        })->sortByDesc('total')->values();

        // Agrupar por mês
        $byMonth = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
            $monthEnd = Carbon::create($year, $month, 1)->endOfMonth();
            
            $monthDespesas = $despesas->filter(function ($transaction) use ($monthStart, $monthEnd) {
                return $transaction->transaction_date->gte($monthStart) && $transaction->transaction_date->lte($monthEnd);
            });
            
            $byMonth[$month] = [
                'month_name' => $monthStart->translatedFormat('F'),
                'total' => $monthDespesas->sum('amount'),
                'count' => $monthDespesas->count(),
                'by_category' => $monthDespesas->groupBy('category_id')->map(function ($group) {
                    return [
                        'category_name' => $group->first()->category ? $group->first()->category->name : 'Sem categoria',
                        'total' => $group->sum('amount'),
                    ];
                })->sortByDesc('total')->values(),
            ];
        }

        // Total geral
        $totalGeral = $despesas->sum('amount');

        // Dados para o gráfico mensal
        $chartData = collect($byMonth)->map(function ($month, $index) {
            return [
                'month' => $index,
                'month_name' => $month['month_name'],
                'total' => (float) $month['total'],
            ];
        })->values()->toArray();

        // Dados para filtros
        $years = range(now()->year - 5, now()->year + 1);
        $categoriesDespesas = FinancialCategory::despesas()->orderBy('name')->get();

        return view('financial.reports.expenses-annual-summary', compact(
            'year',
            'byCategory',
            'byMonth',
            'totalGeral',
            'chartData',
            'years',
            'categoriesDespesas'
        ));
    }

    /**
     * Calcular saldo até uma data específica
     */
    private function calculateBalance($untilDate)
    {
        $receitas = FinancialTransaction::receitas()
            ->where('transaction_date', '<=', $untilDate)
            ->where('is_paid', true)
            ->sum('amount');
        
        $despesas = FinancialTransaction::despesas()
            ->where('transaction_date', '<=', $untilDate)
            ->where('is_paid', true)
            ->sum('amount');

        return $receitas - $despesas;
    }
}
