<?php

namespace App\Services\Financial;

use App\Models\Event;
use App\Models\FinancialTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class CultoOfferingReportService
{
    public function __construct(
        private readonly PdfSignatureService $signatures,
    ) {}

    public function build(Event $culto): array
    {
        $dia = $culto->start_date?->toDateString();

        $doDia = FinancialTransaction::query()
            ->with(['member', 'contact', 'category'])
            ->where('is_paid', true)
            ->when($dia, fn ($q) => $q->whereDate('transaction_date', $dia))
            ->when(! $dia, fn ($q) => $q->whereRaw('0 = 1'))
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $entradas = $doDia->where('type', 'receita')->values();
        $saidas = $doDia->where('type', 'despesa')->values();
        $dizimos = $entradas->filter(fn ($tx) => strtolower((string) $tx->category?->slug) === 'dizimo');
        $ofertas = $entradas->filter(fn ($tx) => strtolower((string) $tx->category?->slug) === 'oferta');
        $totalEntradas = (float) $entradas->sum('amount');
        $totalSaidas = (float) $saidas->sum('amount');

        return [
            'culto' => $culto,
            'dia' => $dia,
            'entradas' => $entradas,
            'saidas' => $saidas,
            'lancamentos' => $entradas,
            'pendentes' => $this->pendentes(),
            'totalDizimos' => (float) $dizimos->sum('amount'),
            'totalOfertas' => (float) $ofertas->sum('amount'),
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'totalGeral' => $totalEntradas,
            'saldoDia' => $totalEntradas - $totalSaidas,
            'generatedAt' => now(),
            'generatedBy' => auth()->user()?->name,
            'logoPath' => $this->logoPath(),
        ] + $this->signatures->forPdf();
    }

    public function pendentes()
    {
        return $this->dizimosOfertasRecebidos()
            ->with(['member', 'category'])
            ->whereNull('culto_id')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(80)
            ->get();
    }

    public function attachToCulto(Event $culto, array $ids): int
    {
        return $this->dizimosOfertasRecebidos()
            ->whereNull('culto_id')
            ->whereIn('id', $ids)
            ->update(['culto_id' => $culto->id]);
    }

    public function detachFromCulto(Event $culto, FinancialTransaction $transaction): bool
    {
        if ((int) $transaction->culto_id !== (int) $culto->id) {
            return false;
        }

        return $transaction->update(['culto_id' => null]);
    }

    private function dizimosOfertasRecebidos()
    {
        return FinancialTransaction::query()
            ->where('type', 'receita')
            ->where('status', 'recebido')
            ->whereHas('category', function ($q) {
                $q->whereIn('slug', ['dizimo', 'oferta']);
            });
    }

    public function download(Event $culto): Response
    {
        $data = $this->build($culto);
        $binary = Pdf::loadView('financial.reports.pdf.culto-offerings', $data)
            ->setPaper('a4', 'portrait')
            ->output();

        $filename = 'dizimos-ofertas-culto-'.$culto->start_date?->format('Y-m-d-Hi').'.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function logoPath(): ?string
    {
        foreach ([
            public_path('img/img/LOG SS AZUL.png'),
            public_path('img/logo.png'),
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
