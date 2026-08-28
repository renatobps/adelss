<?php

namespace App\Services\Financial;

use App\Models\CashClosing;
use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class WeeklyCashClosingService
{
    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public function weekFor(?string $date): array
    {
        $ref = $date ? Carbon::parse($date) : now();
        $start = $ref->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end = $ref->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @return array<string, mixed>
     */
    public function liveTotals(Carbon $start, Carbon $end): array
    {
        $receitas = FinancialTransaction::query()
            ->with(['category', 'member'])
            ->receitas()
            ->where('status', 'recebido')
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('transaction_date')
            ->get();

        $despesas = FinancialTransaction::query()
            ->with(['category', 'contact'])
            ->despesas()
            ->where('status', 'pago')
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('transaction_date')
            ->get();

        $totalReceitas = (float) $receitas->sum('amount');
        $totalDespesas = (float) $despesas->sum('amount');

        return [
            'start' => $start,
            'end' => $end,
            'receitas' => $receitas,
            'despesas' => $despesas,
            'total_receitas' => $totalReceitas,
            'total_despesas' => $totalDespesas,
            'saldo' => round($totalReceitas - $totalDespesas, 2),
            'receitas_por_categoria' => $this->groupByCategory($receitas),
            'despesas_por_categoria' => $this->groupByCategory($despesas),
            'latest_closing' => CashClosing::query()
                ->whereDate('period_start', $start->toDateString())
                ->whereDate('period_end', $end->toDateString())
                ->latest('generated_at')
                ->first(),
        ];
    }

    public function snapshotHasDiverged(?CashClosing $saved, array $live): bool
    {
        if (! $saved) {
            return false;
        }

        return abs((float) $saved->total_receitas - (float) $live['total_receitas']) > 0.009
            || abs((float) $saved->total_despesas - (float) $live['total_despesas']) > 0.009
            || abs((float) $saved->saldo - (float) $live['saldo']) > 0.009;
    }

    public function generate(Carbon $start, Carbon $end): CashClosing
    {
        $live = $this->liveTotals($start, $end);

        return CashClosing::create([
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'total_receitas' => $live['total_receitas'],
            'total_despesas' => $live['total_despesas'],
            'saldo' => $live['saldo'],
            'generated_by' => Auth::id(),
            'generated_at' => now(),
        ]);
    }

    /**
     * @param  Collection<int, FinancialTransaction>  $items
     * @return list<array{name: string, total: float, count: int}>
     */
    private function groupByCategory(Collection $items): array
    {
        return $items
            ->groupBy(fn (FinancialTransaction $tx) => $tx->category?->name ?: 'Sem categoria')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'total' => (float) $group->sum('amount'),
                'count' => $group->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }
}
