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
        $lancamentos = FinancialTransaction::query()
            ->with(['member', 'category'])
            ->where('culto_id', $culto->id)
            ->where('type', 'receita')
            ->where('status', 'recebido')
            ->whereHas('category', function ($q) {
                $q->whereIn('slug', ['dizimo', 'oferta']);
            })
            ->orderBy('id')
            ->get();

        $dizimos = $lancamentos->filter(fn ($tx) => strtolower((string) $tx->category?->slug) === 'dizimo');
        $ofertas = $lancamentos->filter(fn ($tx) => strtolower((string) $tx->category?->slug) === 'oferta');

        return [
            'culto' => $culto,
            'lancamentos' => $lancamentos,
            'totalDizimos' => (float) $dizimos->sum('amount'),
            'totalOfertas' => (float) $ofertas->sum('amount'),
            'totalGeral' => (float) $lancamentos->sum('amount'),
            'generatedAt' => now(),
            'generatedBy' => auth()->user()?->name,
            'logoPath' => $this->logoPath(),
        ] + $this->signatures->forPdf();
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
