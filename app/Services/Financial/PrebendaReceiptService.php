<?php

namespace App\Services\Financial;

use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use App\Support\FinancialReceiptLogo;
use App\Support\PdfText;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Prebenda pastoral não tem recibo de próprio punho: o sistema emite o recibo
 * já assinado, com a assinatura do pastor dirigente e o nome do pastor
 * escolhido em "Pago à".
 */
class PrebendaReceiptService
{
    private const DISK = 'public';

    /** Pasta exclusiva dos recibos emitidos pelo sistema. */
    private const DIR = FinancialTransactionAttachment::GENERATED_DIR;

    public function __construct(private PdfSignatureService $signatures) {}

    public function appliesTo(?FinancialTransaction $transaction): bool
    {
        return $transaction?->type === 'despesa'
            && $this->categoryIsPrebenda($transaction->category_id);
    }

    public function categoryIsPrebenda(mixed $categoryId): bool
    {
        if (! $categoryId) {
            return false;
        }

        return FinancialCategory::query()->find($categoryId)?->isPrebendaPastoral() ?? false;
    }

    /**
     * Emite o recibo e anexa à transação, substituindo o que já havia sido emitido.
     */
    public function regenerate(FinancialTransaction $transaction): ?FinancialTransactionAttachment
    {
        $this->deleteGenerated($transaction);

        $transaction->loadMissing(['member', 'category']);
        $signer = $this->signer($transaction);

        if ($signer['name'] === '') {
            return null;
        }

        try {
            $pdf = Pdf::loadView('financial.transactions.receipt-pdf', [
                'transaction' => $transaction,
                'fundoPath' => FinancialReceiptLogo::backgroundPathForPdf(),
                'signerName' => $signer['name'],
                'signerSignatureSrc' => $signer['signatureSrc'],
                'signerCpfRg' => $signer['cpfRg'],
                'signerAddress' => $signer['address'],
            ])->setPaper(FinancialReceiptLogo::pdfPaper());

            $filePath = self::DIR.'/recibo-prebenda-'.$transaction->id.'-'.time().'.pdf';
            $contents = $pdf->output();
            Storage::disk(self::DISK)->put($filePath, $contents);

            return FinancialTransactionAttachment::create([
                'transaction_id' => $transaction->id,
                'file_path' => $filePath,
                'file_name' => 'recibo-prebenda-'.$transaction->id.'.pdf',
                'file_type' => 'application/pdf',
                'file_size' => strlen($contents),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao emitir recibo de prebenda pastoral', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function deleteGenerated(FinancialTransaction $transaction): void
    {
        foreach ($this->generatedAttachments($transaction) as $attachment) {
            Storage::disk(self::DISK)->delete($attachment->file_path);
            $attachment->delete();
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, FinancialTransactionAttachment>
     */
    public function generatedAttachments(FinancialTransaction $transaction): iterable
    {
        return $transaction->attachments()
            ->where('file_path', 'like', self::DIR.'/%')
            ->get();
    }

    /**
     * Anexos enviados pelo usuário, ignorando os recibos emitidos pelo sistema.
     *
     * @param  list<int|string>  $excludeIds
     */
    public function manualAttachmentsCount(FinancialTransaction $transaction, array $excludeIds = []): int
    {
        return $transaction->attachments()
            ->when($excludeIds !== [], fn ($query) => $query->whereNotIn('id', $excludeIds))
            ->where('file_path', 'not like', self::DIR.'/%')
            ->count();
    }

    /**
     * Quem assina o recibo: o pastor selecionado em "Pago à", com a imagem de
     * assinatura cadastrada em Relatórios → Assinaturas.
     *
     * @return array{name: string, signatureSrc: ?string, cpfRg: string, address: string}
     */
    public function signer(FinancialTransaction $transaction, bool $asDataUri = false): array
    {
        $transaction->loadMissing('member');
        $member = $transaction->member;

        $name = PdfText::stripEmoji((string) ($member?->name ?: $transaction->received_from_other));

        $signature = $asDataUri
            ? $this->signatures->imageSrc(PdfSignatureService::ROLE_PASTOR)
            : $this->normalizePath($this->signatures->imagePath(PdfSignatureService::ROLE_PASTOR));

        $cpf = trim((string) ($member?->cpf ?? ''));
        $rg = trim((string) ($member?->rg ?? ''));

        return [
            'name' => trim($name),
            'signatureSrc' => $signature,
            'cpfRg' => $cpf !== '' ? $cpf : $rg,
            'address' => $member ? $member->fullAddress() : '',
        ];
    }

    private function normalizePath(?string $path): ?string
    {
        return $path ? str_replace('\\', '/', $path) : null;
    }
}
