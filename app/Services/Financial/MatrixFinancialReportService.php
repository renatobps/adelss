<?php

namespace App\Services\Financial;

use App\Models\FinancialTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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
            'congregacao' => 'ADEL São Sebastião',
            'cidade' => 'Luziânia',
        ] + $this->signatures->forPdf();
    }

    /**
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
     *     saidas_label: string,
     *     saldo_anterior: float,
     *     saldo_final: float
     * }
     */
    public function buildMonth(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $monthName = ucfirst($start->locale('pt_BR')->translatedFormat('F'));

        $totalEntradas = (float) FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $totalSaidas = (float) FinancialTransaction::despesas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

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
            'total_saidas' => round($totalSaidas, 2),
            'saidas_label' => 'Saídas do mês de '.$monthName,
            'saldo_anterior' => round($saldoAnterior, 2),
            'saldo_final' => $saldoFinal,
        ];
    }

    public function download(int $year): Response
    {
        $data = $this->buildYear($year);
        $binary = Pdf::loadView('financial.reports.pdf.matrix-demonstrativo', $data)
            ->setPaper('a4', 'portrait')
            ->output();

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="demonstrativo-financeiro-matriz-'.$year.'.pdf"',
        ]);
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
        $headerPath = is_file(public_path('images/cabecalho-cdel.jpg'))
            ? public_path('images/cabecalho-cdel.jpg')
            : public_path('images/cabecalho-cdel.png');

        if (! is_file($headerPath)) {
            return null;
        }

        $mime = str_ends_with($headerPath, '.png') ? 'image/png' : 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($headerPath));
    }
}
