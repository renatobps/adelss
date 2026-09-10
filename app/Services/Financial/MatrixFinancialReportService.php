<?php

namespace App\Services\Financial;

use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Support\FinancialReceiptLogo;
use App\Support\FinancialReceiptPresenter;
use App\Support\PdfText;
use App\Support\ValorPorExtenso;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MatrixFinancialReportService
{
    public function __construct(private PdfSignatureService $signatures) {}

    /**
     * @return array{
     *     year: int,
     *     months: list<array<string, mixed>>,
     *     headerSrc: ?string,
     *     congregacao: string,
     *     cidade: string
     * }
     */
    public function buildYear(int $year): array
    {
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[] = $this->buildMonth($year, $month);
        }

        return [
            'year' => $year,
            'months' => $months,
            'headerSrc' => $this->headerSrc(),
            'reciboFundoSrc' => FinancialReceiptLogo::backgroundPathForPdf(),
            'congregacao' => 'ADEL São Sebastião',
            'cidade' => 'Luziânia',
            'pastorNome' => $this->signatures->pastorNome(),
            'pastorAssinaturaSrc' => $this->pdfLocalSrc($this->signatures->imagePath(PdfSignatureService::ROLE_PASTOR)),
            'tesoureiroNome' => $this->signatures->tesoureiroNome(),
            'tesoureiroAssinaturaSrc' => $this->pdfLocalSrc($this->signatures->imagePath(PdfSignatureService::ROLE_TESOUREIRO)),
        ];
    }

    /**
     * @param  Collection<int, FinancialTransaction>|null  $saidas
     * @return array{
     *     month: int,
     *     month_name: string,
     *     month_name_lower: string,
     *     start: Carbon,
     *     end: Carbon,
     *     date_label: string,
     *     total_entradas: float,
     *     dizimo_obreiros: float,
     *     dizimo_membros: float,
     *     total_saidas: float,
     *     saidas: list<array{description: string, amount: float, amount_extenso: string, recibo_numero: string, is_prebenda: bool, receipt: array<string, string>}>,
     *     saldo_anterior: float,
     *     saldo_final: float
     * }
     */
    public function buildMonth(int $year, int $month, ?Collection $saidas = null): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $monthName = ucfirst($start->locale('pt_BR')->translatedFormat('F'));

        $totalEntradas = (float) FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        if ($saidas === null) {
            $saidas = $this->paidExpensesBetween($start, $end);
        }

        $saidasList = $this->groupExpensesByType($saidas, $year, $month, $end);

        $totalSaidas = round((float) $saidas->sum(fn (FinancialTransaction $tx) => (float) $tx->amount), 2);

        $dizimoObreiros = round($totalEntradas * 0.37, 2);
        $dizimoMembros = round($totalEntradas - $dizimoObreiros, 2);

        $saldoAnterior = $this->balanceUntil($start->copy()->subDay()->toDateString());
        $saldoFinal = round($saldoAnterior + $totalEntradas - $totalSaidas, 2);

        return [
            'month' => $month,
            'month_name' => $monthName,
            'month_name_lower' => mb_strtolower($monthName),
            'start' => $start,
            'end' => $end,
            'date_label' => $end->locale('pt_BR')->translatedFormat('d \d\e F \d\e Y'),
            'total_entradas' => round($totalEntradas, 2),
            'dizimo_obreiros' => $dizimoObreiros,
            'dizimo_membros' => $dizimoMembros,
            'total_saidas' => $totalSaidas,
            'saidas' => $saidasList,
            'saldo_anterior' => round($saldoAnterior, 2),
            'saldo_final' => $saldoFinal,
        ];
    }

    public function download(int $year): Response
    {
        @set_time_limit(180);

        $data = $this->buildYear($year);
        $binary = Pdf::loadView('financial.reports.pdf.matrix-demonstrativo', $data)
            ->setPaper('a4', 'portrait')
            ->output();

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="demonstrativo-financeiro-matriz-'.$year.'-saidas.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * @return Collection<int, FinancialTransaction>
     */
    private function paidExpensesBetween(Carbon $start, Carbon $end): Collection
    {
        return FinancialTransaction::despesas()
            ->with('category')
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, FinancialTransaction>  $saidas
     * @return list<array{description: string, amount: float, amount_extenso: string, recibo_numero: string, is_prebenda: bool, receipt: array<string, string>}>
     */
    private function groupExpensesByType(Collection $saidas, int $year, int $month, Carbon $end): array
    {
        $grouped = [];

        foreach ($saidas as $tx) {
            $label = $this->expenseTypeLabel($tx);
            $key = mb_strtolower($label);
            $isPrebenda = FinancialCategory::slugIsPrebendaPastoral($tx->category?->slug, $label);

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'description' => $label,
                    'amount' => 0.0,
                    'is_prebenda' => $isPrebenda,
                ];
            }

            $grouped[$key]['amount'] = round($grouped[$key]['amount'] + (float) $tx->amount, 2);
            $grouped[$key]['is_prebenda'] = $grouped[$key]['is_prebenda'] || $isPrebenda;
        }

        uasort($grouped, function (array $a, array $b) {
            return strcasecmp(
                Str::ascii($a['description']),
                Str::ascii($b['description'])
            );
        });

        $list = [];
        $seq = 1;
        foreach ($grouped as $row) {
            $numero = sprintf('%02d%02d%02d', $year % 100, $month, $seq);
            $list[] = [
                'description' => $row['description'],
                'amount' => $row['amount'],
                'amount_extenso' => ValorPorExtenso::reais($row['amount']),
                'recibo_numero' => $numero,
                'is_prebenda' => $row['is_prebenda'],
                'receipt' => FinancialReceiptPresenter::forExpenseGroup(
                    $row['description'],
                    $row['amount'],
                    $end,
                    $numero
                ),
            ];
            $seq++;
        }

        return $list;
    }

    private function expenseTypeLabel(FinancialTransaction $tx): string
    {
        $category = PdfText::stripEmoji((string) ($tx->category?->name ?? ''));
        if ($category !== '') {
            return $category;
        }

        $description = PdfText::stripEmoji((string) $tx->description);

        return $description !== '' ? $description : 'Saída';
    }

    private function balanceUntil(string $untilDate): float
    {
        $receitas = FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->where('transaction_date', '<=', $untilDate)
            ->sum('amount');

        $despesas = FinancialTransaction::despesas()
            ->where('is_paid', true)
            ->where('transaction_date', '<=', $untilDate)
            ->sum('amount');

        return (float) $receitas - (float) $despesas;
    }

    private function headerSrc(): ?string
    {
        $headerPath = is_file(public_path('images/cabecalho-adel-logo.jpg'))
            ? public_path('images/cabecalho-adel-logo.jpg')
            : (is_file(public_path('images/cabecalho-cdel.jpg'))
                ? public_path('images/cabecalho-cdel.jpg')
                : public_path('images/cabecalho-cdel.png'));

        return $this->pdfLocalSrc($headerPath);
    }

    private function pdfLocalSrc(?string $path): ?string
    {
        if (! is_string($path) || $path === '' || ! is_file($path)) {
            return null;
        }

        return str_replace('\\', '/', $path);
    }
}
