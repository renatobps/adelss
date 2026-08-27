<?php

namespace App\Services\Financial;

use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Support\PdfText;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CashFlowClosingPdfService
{
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

        $logoPath = $this->resolveLogoPath();

        return [
            'start' => $start,
            'end' => $end,
            'account' => $account,
            'logoPath' => $logoPath,
            'entradas' => $entradas,
            'saidas' => $saidas,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'saldoAnterior' => $saldoAnterior,
            'saldoFinal' => $saldoFinal,
            'generatedAt' => now(),
            'comprovantes' => $this->mapComprovantes($saidas),
            'pastorNome' => $this->namesForRole(['Pastor%', 'Pastora%', '%Pastor(a)%']),
            'tesoureiroNome' => $this->tesoureiroNome(),
        ];
    }

    /**
     * Somente o 1º tesoureiro; o cargo 2º Tesoureiro(a) não assina o fechamento.
     */
    private function tesoureiroNome(): ?string
    {
        $members = $this->membersWithRoleLike(['%Tesoureiro%']);
        if ($members->isEmpty()) {
            return null;
        }

        $first = $members->filter(fn (Member $member) => $this->isFirstTreasurerRole((string) ($member->role->name ?? '')));
        if ($first->isNotEmpty()) {
            return $this->formatMemberNames($first);
        }

        $withoutSecond = $members->reject(fn (Member $member) => $this->isSecondTreasurerRole((string) ($member->role->name ?? '')));

        return $this->formatMemberNames($withoutSecond);
    }

    private function isFirstTreasurerRole(string $roleName): bool
    {
        return (bool) preg_match('/(?:^|[\s])(?:1[ºo°]?|primeiro)\s*tesoureir/iu', $roleName);
    }

    private function isSecondTreasurerRole(string $roleName): bool
    {
        return (bool) preg_match('/(?:^|[\s])(?:2[ºo°]?|segundo)\s*tesoureir/iu', $roleName);
    }

    /**
     * Nomes dos membros ativos com o cargo (role) correspondente.
     *
     * @param  list<string>  $namePatterns
     */
    private function namesForRole(array $namePatterns): ?string
    {
        return $this->formatMemberNames($this->membersWithRoleLike($namePatterns));
    }

    /**
     * @param  list<string>  $namePatterns
     * @return Collection<int, Member>
     */
    private function membersWithRoleLike(array $namePatterns): Collection
    {
        if (! Schema::hasTable('members') || ! Schema::hasTable('member_roles')) {
            return collect();
        }

        $query = Member::query()
            ->with('role')
            ->whereHas('role', function ($q) use ($namePatterns) {
                $q->where('is_active', true)
                    ->where(function ($inner) use ($namePatterns) {
                        foreach ($namePatterns as $index => $pattern) {
                            if ($index === 0) {
                                $inner->where('name', 'like', $pattern);
                            } else {
                                $inner->orWhere('name', 'like', $pattern);
                            }
                        }
                    });
            })
            ->orderBy('name');

        $members = (clone $query)->where('status', Member::STATUS_ATIVO)->get();

        return $members->isNotEmpty() ? $members : $query->get();
    }

    /**
     * @param  Collection<int, Member>  $members
     */
    private function formatMemberNames(Collection $members): ?string
    {
        if ($members->isEmpty()) {
            return null;
        }

        $names = $members
            ->pluck('name')
            ->filter()
            ->map(fn ($name) => PdfText::stripEmoji((string) $name))
            ->unique()
            ->values();

        return $names->isEmpty() ? null : $names->implode(' / ');
    }

    /**
     * @param  Collection<int, FinancialTransaction>  $saidas
     * @return list<array{transaction: FinancialTransaction, fileName: string, kind: string, imageSrc: ?string, absolutePath: ?string}>
     */
    private function mapComprovantes(Collection $saidas): array
    {
        $items = [];

        foreach ($saidas as $transaction) {
            foreach ($transaction->attachments as $attachment) {
                $absolute = $this->absolutePath((string) $attachment->file_path);
                $kind = $this->detectKind((string) $attachment->file_type, (string) $attachment->file_name, $absolute);

                $items[] = [
                    'transaction' => $transaction,
                    'fileName' => PdfText::stripEmoji((string) $attachment->file_name),
                    'kind' => $kind,
                    'imageSrc' => $kind === 'image' && $absolute ? $this->toDataUri($absolute) : null,
                    'absolutePath' => $kind === 'pdf' ? $absolute : null,
                ];
            }
        }

        return $items;
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
            $this->importPdf($fpdi, $tmpMain);

            foreach ($extraPdfPaths as $path) {
                try {
                    $this->importPdf($fpdi, $path);
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
