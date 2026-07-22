<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('financial.view-summary');

        $today = Carbon::today();
        $periodFilter = $request->input('period', '1month');

        [$periodStart, $periodEnd, $periodLabel] = $this->resolvePeriod($periodFilter, $today);

        $entradas = (float) FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->sum('amount');

        $entradasCount = (int) FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->count();

        $saidas = (float) FinancialTransaction::despesas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->sum('amount');

        $resultado = $entradas - $saidas;
        $saldoTotal = $resultado;
        $resultadoLabel = $resultado >= 0 ? 'Superávit' : 'Déficit';

        $periodRangeLabel = $periodStart->format('d/m/Y') . ' - ' . $periodEnd->format('d/m/Y');

        $accounts = FinancialAccount::all();
        $accountsBalance = [];
        $totalBalance = 0.0;

        foreach ($accounts as $account) {
            $receitas = (float) FinancialTransaction::receitas()
                ->where('is_paid', true)
                ->where('account_id', $account->id)
                ->sum('amount');

            $despesas = (float) FinancialTransaction::despesas()
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

        $receitasSemConta = (float) FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->whereNull('account_id')
            ->sum('amount');

        $despesasSemConta = (float) FinancialTransaction::despesas()
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

        if (empty($accountsBalance)) {
            $accountsBalance[] = [
                'name' => 'Nenhuma conta',
                'balance' => 0,
            ];
        }

        $selectedYear = (int) $request->input('year', now()->year);
        $annualData = $this->getAnnualData($selectedYear);

        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $monthlyData = $this->getMonthlyData($selectedMonth);

        $availableYears = range(now()->year - 2, now()->year + 1);
        $availableMonths = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i);
            $availableMonths[] = [
                'value' => $month->format('Y-m'),
                'label' => $month->translatedFormat('F') . ' - ' . $month->format('Y'),
            ];
        }

        $periodOptions = [
            'today' => 'Hoje',
            '7days' => 'Últimos 7 dias',
            '1month' => 'Mês Atual',
            'last_month' => 'Mês Anterior',
            '3months' => 'Últimos 3 meses',
        ];

        return view('financial.summary', compact(
            'entradas',
            'entradasCount',
            'saidas',
            'resultado',
            'resultadoLabel',
            'saldoTotal',
            'periodFilter',
            'periodLabel',
            'periodRangeLabel',
            'periodOptions',
            'accountsBalance',
            'totalBalance',
            'annualData',
            'monthlyData',
            'selectedYear',
            'selectedMonth',
            'availableYears',
            'availableMonths'
        ));
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolvePeriod(string $periodFilter, Carbon $today): array
    {
        return match ($periodFilter) {
            'today' => [$today->copy(), $today->copy(), 'Hoje'],
            '7days' => [$today->copy()->subDays(6), $today->copy(), 'Últimos 7 dias'],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
                'Mês Anterior',
            ],
            '3months' => [
                $today->copy()->subMonths(2)->startOfMonth(),
                $today->copy()->endOfMonth(),
                'Últimos 3 meses',
            ],
            default => [
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth(),
                'Mês Atual',
            ],
        };
    }

    private function getAnnualData(int $year): array
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

            $data['receitas'][] = (float) FinancialTransaction::receitas()
                ->where('is_paid', true)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $data['despesas'][] = (float) FinancialTransaction::despesas()
                ->where('is_paid', true)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $data['aReceber'][] = (float) FinancialTransaction::receitas()
                ->where('is_paid', false)
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $data['aPagar'][] = (float) FinancialTransaction::despesas()
                ->where('is_paid', false)
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->sum('amount');
        }

        $allValues = array_merge($data['receitas'], $data['despesas'], $data['aReceber'], $data['aPagar']);
        $maxValue = count($allValues) > 0 ? max($allValues) : 0;
        $data['maxValue'] = $maxValue > 0 ? ceil($maxValue / 500) * 500 : 1000;

        return $data;
    }

    private function getMonthlyData(string $yearMonth): array
    {
        [$year, $month] = explode('-', $yearMonth);
        $monthStart = Carbon::create((int) $year, (int) $month, 1)->startOfMonth();
        $monthEnd = Carbon::create((int) $year, (int) $month, 1)->endOfMonth();
        $daysInMonth = $monthEnd->day;

        $data = [
            'labels' => range(1, $daysInMonth),
            'receitas' => array_fill(0, $daysInMonth, 0),
            'despesas' => array_fill(0, $daysInMonth, 0),
            'aReceber' => array_fill(0, $daysInMonth, 0),
            'aPagar' => array_fill(0, $daysInMonth, 0),
        ];

        foreach (
            FinancialTransaction::receitas()
                ->where('is_paid', true)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->get() as $transacao
        ) {
            $day = $transacao->transaction_date->day - 1;
            if ($day >= 0 && $day < $daysInMonth) {
                $data['receitas'][$day] += (float) $transacao->amount;
            }
        }

        foreach (
            FinancialTransaction::despesas()
                ->where('is_paid', true)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->get() as $transacao
        ) {
            $day = $transacao->transaction_date->day - 1;
            if ($day >= 0 && $day < $daysInMonth) {
                $data['despesas'][$day] += (float) $transacao->amount;
            }
        }

        foreach (
            FinancialTransaction::receitas()
                ->where('is_paid', false)
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->get() as $transacao
        ) {
            $day = $transacao->due_date->day - 1;
            if ($day >= 0 && $day < $daysInMonth) {
                $data['aReceber'][$day] += (float) $transacao->amount;
            }
        }

        foreach (
            FinancialTransaction::despesas()
                ->where('is_paid', false)
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->get() as $transacao
        ) {
            $day = $transacao->due_date->day - 1;
            if ($day >= 0 && $day < $daysInMonth) {
                $data['aPagar'][$day] += (float) $transacao->amount;
            }
        }

        $allValues = array_merge($data['receitas'], $data['despesas'], $data['aReceber'], $data['aPagar']);
        $maxValue = count($allValues) > 0 ? max($allValues) : 0;
        $data['maxValue'] = $maxValue > 0 ? ceil($maxValue / 200) * 200 : 1000;

        return $data;
    }
}
