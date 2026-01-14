<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SummaryController extends Controller
{
    public function index(Request $request)
    {
        // Períodos padrão
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Filtros de período (para os cards)
        $periodFilter = $request->input('period', 'today'); // today, 7days, 1month, 3months
        
        // Calcular datas baseado no período
        $periodStart = $today;
        $periodEnd = $today;
        
        switch ($periodFilter) {
            case '7days':
                $periodStart = $today->copy()->subDays(7);
                $periodEnd = $today;
                break;
            case '1month':
                $periodStart = $startOfMonth;
                $periodEnd = $endOfMonth;
                break;
            case '3months':
                $periodStart = $today->copy()->subMonths(3)->startOfMonth();
                $periodEnd = $endOfMonth;
                break;
            default: // today
                $periodStart = $today;
                $periodEnd = $today;
                break;
        }
        
        // Recebido hoje/período (receitas pagas)
        $recebidoPeriodo = FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->sum('amount');
        
        $recebidoMes = FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        
        // Pago hoje/período (despesas pagas)
        $pagoPeriodo = FinancialTransaction::despesas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->sum('amount');
        
        $pagoMes = FinancialTransaction::despesas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        
        // A receber hoje/período (receitas não pagas)
        $aReceberPeriodo = FinancialTransaction::receitas()
            ->where('is_paid', false)
            ->whereBetween('due_date', [$periodStart, $periodEnd])
            ->sum('amount');
        
        $aReceberMes = FinancialTransaction::receitas()
            ->where('is_paid', false)
            ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        
        // A pagar hoje/período (despesas não pagas)
        $aPagarPeriodo = FinancialTransaction::despesas()
            ->where('is_paid', false)
            ->whereBetween('due_date', [$periodStart, $periodEnd])
            ->sum('amount');
        
        $aPagarMes = FinancialTransaction::despesas()
            ->where('is_paid', false)
            ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        
        // Recebimentos em atraso (receitas não pagas com due_date passado)
        // Mês atual: recebimentos que vencem no mês atual e já estão atrasados (até ontem)
        $yesterday = $today->copy()->subDay();
        $recebimentosAtrasoMes = FinancialTransaction::receitas()
            ->where('is_paid', false)
            ->where('due_date', '>=', $startOfMonth)
            ->where('due_date', '<=', $yesterday)
            ->sum('amount');
        
        // Todo o período: todas as receitas não pagas com due_date passado
        $recebimentosAtrasoTodoPeriodo = FinancialTransaction::receitas()
            ->where('is_paid', false)
            ->where('due_date', '<', $today)
            ->sum('amount');
        
        // Pagamentos em atraso (despesas não pagas com due_date passado)
        // Mês atual: pagamentos que vencem no mês atual e já estão atrasados (até ontem)
        $pagamentosAtrasoMes = FinancialTransaction::despesas()
            ->where('is_paid', false)
            ->where('due_date', '>=', $startOfMonth)
            ->where('due_date', '<=', $yesterday)
            ->sum('amount');
        
        // Todo o período: todas as despesas não pagas com due_date passado
        $pagamentosAtrasoTodoPeriodo = FinancialTransaction::despesas()
            ->where('is_paid', false)
            ->where('due_date', '<', $today)
            ->sum('amount');
        
        // Saldo atual por conta
        $accounts = FinancialAccount::all();
        
        $accountsBalance = [];
        $totalBalance = 0;
        
        foreach ($accounts as $account) {
            $receitas = FinancialTransaction::receitas()
                ->where('is_paid', true)
                ->where('account_id', $account->id)
                ->sum('amount');
            
            $despesas = FinancialTransaction::despesas()
                ->where('is_paid', true)
                ->where('account_id', $account->id)
                ->sum('amount');
            
            $saldo = $receitas - $despesas;
            if ($saldo != 0 || $receitas > 0 || $despesas > 0) {
                $accountsBalance[] = [
                    'name' => $account->name,
                    'balance' => $saldo,
                ];
                $totalBalance += $saldo;
            }
        }
        
        // Adicionar "Sem conta" (transações sem account_id)
        $receitasSemConta = FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->whereNull('account_id')
            ->sum('amount');
        
        $despesasSemConta = FinancialTransaction::despesas()
            ->where('is_paid', true)
            ->whereNull('account_id')
            ->sum('amount');
        
        $saldoSemConta = $receitasSemConta - $despesasSemConta;
        if ($saldoSemConta != 0 || $receitasSemConta > 0 || $despesasSemConta > 0) {
            $accountsBalance[] = [
                'name' => 'Sem conta',
                'balance' => $saldoSemConta,
            ];
            $totalBalance += $saldoSemConta;
        }
        
        // Se não houver contas com saldo, criar um item vazio
        if (empty($accountsBalance)) {
            $accountsBalance[] = [
                'name' => 'Nenhuma conta',
                'balance' => 0,
            ];
        }
        
        // Dados para gráfico anual (ano selecionado)
        $selectedYear = $request->input('year', now()->year);
        $annualData = $this->getAnnualData($selectedYear);
        
        // Dados para gráfico mensal (mês selecionado)
        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $monthlyData = $this->getMonthlyData($selectedMonth);
        
        // Anos disponíveis para filtro
        $availableYears = range(now()->year - 2, now()->year + 1);
        
        // Meses disponíveis para filtro (últimos 12 meses)
        $availableMonths = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i);
            $availableMonths[] = [
                'value' => $month->format('Y-m'),
                'label' => $month->translatedFormat('F') . ' - ' . $month->format('Y'),
            ];
        }
        
        return view('financial.summary', compact(
            'recebidoPeriodo',
            'recebidoMes',
            'pagoPeriodo',
            'pagoMes',
            'aReceberPeriodo',
            'aReceberMes',
            'aPagarPeriodo',
            'aPagarMes',
            'recebimentosAtrasoMes',
            'recebimentosAtrasoTodoPeriodo',
            'pagamentosAtrasoMes',
            'pagamentosAtrasoTodoPeriodo',
            'accountsBalance',
            'totalBalance',
            'annualData',
            'monthlyData',
            'selectedYear',
            'selectedMonth',
            'availableYears',
            'availableMonths',
            'periodFilter'
        ));
    }
    
    /**
     * Retorna dados para o gráfico anual
     */
    private function getAnnualData($year)
    {
        $data = [
            'labels' => ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'],
            'receitas' => [],
            'despesas' => [],
            'aReceber' => [],
            'aPagar' => [],
        ];
        
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
            $monthEnd = Carbon::create($year, $month, 1)->endOfMonth();
            
            // Receitas pagas do mês
            $receitas = FinancialTransaction::receitas()
                ->where('is_paid', true)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');
            
            // Despesas pagas do mês
            $despesas = FinancialTransaction::despesas()
                ->where('is_paid', true)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');
            
            // A receber do mês (receitas não pagas)
            $aReceber = FinancialTransaction::receitas()
                ->where('is_paid', false)
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->sum('amount');
            
            // A pagar do mês (despesas não pagas)
            $aPagar = FinancialTransaction::despesas()
                ->where('is_paid', false)
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->sum('amount');
            
            $data['receitas'][] = (float) $receitas;
            $data['despesas'][] = (float) $despesas;
            $data['aReceber'][] = (float) $aReceber;
            $data['aPagar'][] = (float) $aPagar;
        }
        
        // Calcular máximo para escala do gráfico
        $allValues = array_merge($data['receitas'], $data['despesas'], $data['aReceber'], $data['aPagar']);
        $maxValue = count($allValues) > 0 ? max($allValues) : 0;
        $data['maxValue'] = $maxValue > 0 ? ceil($maxValue / 500) * 500 : 1000;
        
        return $data;
    }
    
    /**
     * Retorna dados para o gráfico mensal
     */
    private function getMonthlyData($yearMonth)
    {
        list($year, $month) = explode('-', $yearMonth);
        $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth();
        $daysInMonth = $monthEnd->day;
        
        $data = [
            'labels' => range(1, $daysInMonth),
            'receitas' => array_fill(0, $daysInMonth, 0),
            'despesas' => array_fill(0, $daysInMonth, 0),
            'aReceber' => array_fill(0, $daysInMonth, 0),
            'aPagar' => array_fill(0, $daysInMonth, 0),
        ];
        
        // Buscar receitas pagas do mês
        $receitas = FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->get();
        
        foreach ($receitas as $transacao) {
            $day = $transacao->transaction_date->day - 1; // Índice baseado em 0
            if ($day >= 0 && $day < $daysInMonth) {
                $data['receitas'][$day] += (float) $transacao->amount;
            }
        }
        
        // Buscar despesas pagas do mês
        $despesas = FinancialTransaction::despesas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->get();
        
        foreach ($despesas as $transacao) {
            $day = $transacao->transaction_date->day - 1;
            if ($day >= 0 && $day < $daysInMonth) {
                $data['despesas'][$day] += (float) $transacao->amount;
            }
        }
        
        // Buscar a receber do mês
        $aReceber = FinancialTransaction::receitas()
            ->where('is_paid', false)
            ->whereBetween('due_date', [$monthStart, $monthEnd])
            ->get();
        
        foreach ($aReceber as $transacao) {
            $day = $transacao->due_date->day - 1;
            if ($day >= 0 && $day < $daysInMonth) {
                $data['aReceber'][$day] += (float) $transacao->amount;
            }
        }
        
        // Buscar a pagar do mês
        $aPagar = FinancialTransaction::despesas()
            ->where('is_paid', false)
            ->whereBetween('due_date', [$monthStart, $monthEnd])
            ->get();
        
        foreach ($aPagar as $transacao) {
            $day = $transacao->due_date->day - 1;
            if ($day >= 0 && $day < $daysInMonth) {
                $data['aPagar'][$day] += (float) $transacao->amount;
            }
        }
        
        // Calcular máximo para escala do gráfico
        $allValues = array_merge($data['receitas'], $data['despesas'], $data['aReceber'], $data['aPagar']);
        $maxValue = count($allValues) > 0 ? max($allValues) : 0;
        $data['maxValue'] = $maxValue > 0 ? ceil($maxValue / 200) * 200 : 1000;
        
        return $data;
    }
}

