<?php

namespace App\Services\Financial;

use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Support\PdfText;
use App\Support\PdfToImages;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CashFlowClosingPdfService
{
    public function __construct(
        private readonly PdfSignatureService $signatures,
        private readonly PdfToImages $pdfToImages,
    ) {
    }

    public function download(Request $request): Response
    {
        @set_time_limit(180);

        $data = $this->buildViewData($request);
        $pdf = Pdf::loadView('financial.reports.pdf.cash-flow-closing', $data)
            ->setPaper('a4', 'portrait');

        $binary = $pdf->output();
        $extraPdfs = collect($data['comprovantes'])
            ->where('kind', 'pdf')
            ->pluck('absolutePath')
            ->filter(fn ($path) => is_string($path) && is_file($path))
            ->values()
            ->all();

        if ($extraPdfs !== []) {
            $merged = $this->mergePdfFiles($binary, $extraPdfs);
            if ($merged !== null) {
                $binary = $merged;
            }
        }

        $filename = 'fechamento-caixa-'.$data['start']->format('Y-m').'.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(Request $request): array
    {
        $start = Carbon::parse($request->input('start_date', now()->startOfMonth()->format('Y-m-d')))->startOfDay();
        $end = Carbon::parse($request->input('end_date', now()->endOfMonth()->format('Y-m-d')))->endOfDay();
        $accountId = $request->filled('account_id') ? (int) $request->input('account_id') : null;
        $costCenterId = $request->filled('cost_center_id') ? (int) $request->input('cost_center_id') : null;
        $categoryReceitasId = $request->filled('category_receitas_id') ? (int) $request->input('category_receitas_id') : null;
        $categoryDespesasId = $request->filled('category_despesas_id') ? (int) $request->input('category_despesas_id') : null;

        $entradas = $this->periodQuery($start, $end, $accountId, $costCenterId)
            ->receitas()
            ->where('is_paid', true)
            ->when($categoryReceitasId, fn ($q) => $q->where('category_id', $categoryReceitasId))
            ->with(['member', 'category', 'account'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $saidas = $this->periodQuery($start, $end, $accountId, $costCenterId)
            ->despesas()
            ->where('is_paid', true)
            ->when($categoryDespesasId, fn ($q) => $q->where('category_id', $categoryDespesasId))
            ->with(['contact', 'category', 'account', 'attachments'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $totalEntradas = (float) $entradas->sum('amount');
        $totalSaidas = (float) $saidas->sum('amount');
        $saldoAnterior = $this->balanceUntil($start->copy()->subDay()->toDateString(), $accountId, $costCenterId);
        $saldoFinal = $saldoAnterior + $totalEntradas - $totalSaidas;

        $account = $accountId ? FinancialAccount::query()->find($accountId) : null;

        $headerPath = is_file(public_path('images/cabecalho-cdel.jpg'))
            ? public_path('images/cabecalho-cdel.jpg')
            : public_path('images/cabecalho-cdel.png');
        $headerSrc = is_file($headerPath)
            ? 'data:'.(str_ends_with($headerPath, '.png') ? 'image/png' : 'image/jpeg').';base64,'.base64_encode((string) file_get_contents($headerPath))
            : null;
        $logoPath = $this->resolveLogoPath();

        return [
            'start' => $start,
            'end' => $end,
            'account' => $account,
            'headerSrc' => $headerSrc,
            'logoPath' => $logoPath,
            'entradas' => $entradas,
            'saidas' => $saidas,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'saldoAnterior' => $saldoAnterior,
            'saldoFinal' => $saldoFinal,
            'generatedAt' => now(),
            'comprovantes' => $this->mapComprovantes($saidas),
        ] + $this->signatures->forPdf();
    }

    /**
     * @param  Collection<int, FinancialTransaction>  $saidas
     * @return list<array{transaction: FinancialTransaction, fileName: string, kind: string, imageSrc: ?string, imageSrcs: list<string>, absolutePath: ?string}>
     */
    private function mapComprovantes(Collection $saidas): array
    {
        $items = [];

        foreach ($saidas as $transaction) {
            foreach ($transaction->attachments as $attachment) {
                $absolute = $this->absolutePath((string) $attachment->file_path);
                $kind = $this->detectKind((string) $attachment->file_type, (string) $attachment->file_name, $absolute);
                $imageSrcs = [];

                if ($kind === 'image' && $absolute) {
                    $uri = $this->toDataUri($absolute);
                    if ($uri) {
                        $imageSrcs[] = $uri;
                    }
                }

                if ($kind === 'pdf' && $absolute) {
                    $rendered = $this->pdfPagesAsDataUris($absolute);
                    if ($rendered !== []) {
                        $kind = 'image';
                        $imageSrcs = $rendered;
                        $absolute = null;
                    }
                }

                $items[] = [
                    'transaction' => $transaction,
                    'fileName' => PdfText::stripEmoji((string) $attachment->file_name),
                    'kind' => $kind,
                    'imageSrc' => $imageSrcs[0] ?? null,
                    'imageSrcs' => $imageSrcs,
                    'absolutePath' => $kind === 'pdf' ? $absolute : null,
                ];
            }
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function pdfPagesAsDataUris(string $pdfPath): array
    {
        $files = $this->pdfToImages->convert($pdfPath);
        if ($files === []) {
            return [];
        }

        $uris = [];
        foreach ($files as $file) {
            $uri = $this->toDataUri($file);
            if ($uri) {
                $uris[] = $uri;
            }
            @unlink($file);
        }

        return $uris;
    }

    private function periodQuery(Carbon $start, Carbon $end, ?int $accountId, ?int $costCenterId)
    {
        return FinancialTransaction::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->when($costCenterId, fn ($q) => $q->where('cost_center_id', $costCenterId));
    }

    private function balanceUntil(string $untilDate, ?int $accountId, ?int $costCenterId): float
    {
        $receitas = FinancialTransaction::receitas()
            ->where('transaction_date', '<=', $untilDate)
            ->where('is_paid', true)
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->when($costCenterId, fn ($q) => $q->where('cost_center_id', $costCenterId))
            ->sum('amount');

        $despesas = FinancialTransaction::despesas()
            ->where('transaction_date', '<=', $untilDate)
            ->where('is_paid', true)
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->when($costCenterId, fn ($q) => $q->where('cost_center_id', $costCenterId))
            ->sum('amount');

        return (float) $receitas - (float) $despesas;
    }

    private function absolutePath(string $filePath): ?string
    {
        $filePath = ltrim(str_replace('\\', '/', $filePath), '/');
        $candidates = [
            Storage::disk('public')->path($filePath),
            storage_path('app/public/'.$filePath),
            public_path('storage/'.$filePath),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function detectKind(string $fileType, string $fileName, ?string $absolutePath): string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mime = strtolower($fileType);

        if ($absolutePath && is_file($absolutePath)) {
            $detected = @mime_content_type($absolutePath) ?: '';
            if (str_starts_with($detected, 'image/')) {
                return 'image';
            }
            if ($detected === 'application/pdf') {
                return 'pdf';
            }
        }

        if (str_contains($mime, 'pdf') || $ext === 'pdf') {
            return 'pdf';
        }

        if (str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'image';
        }

        return 'other';
    }

    private function toDataUri(string $path): ?string
    {
        $mime = @mime_content_type($path) ?: 'image/jpeg';
        if (! str_starts_with($mime, 'image/')) {
            return null;
        }

        $contents = @file_get_contents($path);
        if ($contents === false || $contents === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function resolveLogoPath(): ?string
    {
        foreach ([
            public_path('img/img/LOG SS AZUL.png'),
            public_path('img/logo.png'),
            public_path('img/LOG SS preta.png'),
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $extraPdfPaths
     */
    private function mergePdfFiles(string $mainPdfBinary, array $extraPdfPaths): ?string
    {
        $tmpMain = tempnam(sys_get_temp_dir(), 'cf-main-');
        if ($tmpMain === false) {
            return null;
        }

        file_put_contents($tmpMain, $mainPdfBinary);

        try {
            $fpdi = new Fpdi();
            $this->importPdfOrFlatten($fpdi, $tmpMain);

            foreach ($extraPdfPaths as $path) {
                try {
                    $this->importPdfOrFlatten($fpdi, $path);
                } catch (Throwable $e) {
                    Log::warning('Comprovante PDF não pôde ser incorporado ao fechamento de caixa.', [
                        'path' => $path,
                        'error' => $e->getMessage(),
                    ]);
                    $this->addFallbackPage($fpdi, basename($path));
                }
            }

            return $fpdi->Output('S');
        } catch (Throwable $e) {
            Log::warning('Falha ao unir PDFs do fechamento de caixa.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            @unlink($tmpMain);
        }
    }

    private function importPdfOrFlatten(Fpdi $fpdi, string $path): void
    {
        try {
            $this->importPdf($fpdi, $path);

            return;
        } catch (Throwable $e) {
            $flat = $this->pdfToImages->flattenToPdf14($path);
            if ($flat === null) {
                throw $e;
            }
            try {
                $this->importPdf($fpdi, $flat);
            } finally {
                @unlink($flat);
            }
        }
    }

    private function importPdf(Fpdi $fpdi, string $path): void
    {
        $pageCount = $fpdi->setSourceFile($path);
        for ($page = 1; $page <= $pageCount; $page++) {
            $template = $fpdi->importPage($page);
            $size = $fpdi->getTemplateSize($template);
            $orientation = ($size['width'] ?? 210) > ($size['height'] ?? 297) ? 'L' : 'P';
            $fpdi->AddPage($orientation, [$size['width'] ?? 210, $size['height'] ?? 297]);
            $fpdi->useTemplate($template);
        }
    }

    private function addFallbackPage(Fpdi $fpdi, string $fileName): void
    {
        $fpdi->AddPage('P', [210, 297]);
        $fpdi->SetFont('Helvetica', '', 11);
        $fpdi->SetXY(20, 40);
        $fpdi->MultiCell(170, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT',
            'O comprovante "'.$fileName.'" está em um formato de PDF que não pôde ser incorporado automaticamente. Arquive o arquivo original junto com este relatório.'
        ));
    }
}
