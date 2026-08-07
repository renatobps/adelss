<?php

namespace App\Services;

use App\Models\CampaignInstallment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

/**
 * Recibos de campanha: PDF + envio por WhatsApp.
 * Segue o padrão do FinancialNotificationService, mas é totalmente
 * independente do módulo Financeiro (nunca cria FinancialTransaction).
 */
class CampaignReceiptService
{
    public function __construct(
        private readonly WhatsAppService $whatsappService,
    ) {}

    /**
     * Envia o comprovante da parcela paga por WhatsApp (texto + PDF).
     * Nunca lança exceção: falha de envio não pode reverter o pagamento.
     *
     * @return array{success: bool, skipped?: bool, error?: string}
     */
    public function enviarComprovante(CampaignInstallment $installment): array
    {
        $installment->loadMissing('sponsor.campaign');
        $sponsor = $installment->sponsor;
        $campaign = $sponsor->campaign;

        $phone = trim((string) $sponsor->phone);
        if ($phone === '') {
            return [
                'success' => false,
                'skipped' => true,
                'error' => 'Patrocinador sem telefone cadastrado.',
            ];
        }

        $mensagem = "🧾 *Comprovante de contribuição*\n\n"
            . "Campanha: *{$campaign->name}*\n"
            . "Parcela: {$installment->installment_number}/{$campaign->installments_count}\n"
            . 'Valor: R$ ' . number_format((float) $installment->amount, 2, ',', '.') . "\n"
            . 'Data: ' . $installment->paid_at?->format('d/m/Y H:i') . "\n"
            . "Recibo nº: {$installment->receipt_number}\n\n"
            . trim((string) ($campaign->receipt_message ?: 'Deus abençoe sua generosidade!'));

        try {
            $resultado = ['success' => false];
            $pdfPath = $this->gerarPdfRecibo($installment);
            if ($pdfPath) {
                $resultado = $this->whatsappService->enviarDocumentoArquivo(
                    $phone,
                    $pdfPath,
                    'recibo-campanha-' . str_replace('/', '-', (string) $installment->receipt_number) . '.pdf',
                    $mensagem
                );
                @unlink($pdfPath);
            }

            if (!($resultado['success'] ?? false)) {
                $resultado = $this->whatsappService->enviarMensagem($phone, $mensagem);
            }

            if ($resultado['success'] ?? false) {
                $installment->update(['receipt_sent_at' => now()]);

                return ['success' => true];
            }

            Log::warning('Campanha: falha ao enviar comprovante por WhatsApp.', [
                'installment_id' => $installment->id,
                'error' => $resultado['error'] ?? 'desconhecido',
            ]);

            return ['success' => false, 'error' => $resultado['error'] ?? 'Falha no envio do WhatsApp.'];
        } catch (\Throwable $e) {
            Log::error('Campanha: erro inesperado ao enviar comprovante.', [
                'installment_id' => $installment->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Gera o PDF do recibo individual e retorna o caminho do arquivo temporário.
     */
    public function gerarPdfRecibo(CampaignInstallment $installment): ?string
    {
        try {
            $installment->loadMissing('sponsor.campaign.department');

            $pdf = Pdf::loadView('financial.campaigns.pdf.receipt', [
                'installments' => collect([$installment]),
                'campaign' => $installment->sponsor->campaign,
                'logoPath' => public_path('img/img/LOG SS AZUL.png'),
            ])->setPaper('a4');

            $dir = storage_path('app/temp/campaign-receipts');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $path = $dir . '/recibo-' . $installment->id . '-' . time() . '.pdf';
            file_put_contents($path, $pdf->output());

            return $path;
        } catch (\Throwable $e) {
            Log::error('Campanha: erro ao gerar PDF do recibo.', [
                'installment_id' => $installment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
