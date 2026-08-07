<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\CampaignInstallment;
use App\Services\CampaignReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CampaignInstallmentController extends Controller
{
    public function __construct(
        private readonly CampaignReceiptService $receiptService,
    ) {}

    /**
     * Marca a parcela como paga, gera número de recibo sequencial e
     * envia o comprovante por WhatsApp. Nenhum FinancialTransaction é criado.
     */
    public function pay(Request $request, CampaignInstallment $installment)
    {
        $data = $request->validate(
            [
                'payment_method' => 'required|in:dinheiro,pix,cartao,outro',
                'paid_at' => 'nullable|date',
            ],
            ['payment_method.required' => 'Informe a forma de pagamento.']
        );

        if ($installment->isPaid()) {
            return $this->redirectBack($installment)->with('error', 'Esta parcela já está paga.');
        }

        $this->markAsPaid($installment, $data['payment_method'], $data['paid_at'] ?? null);

        $envio = $this->receiptService->enviarComprovante($installment->fresh(['sponsor.campaign']));
        $message = "Parcela {$installment->installment_number} registrada como paga (recibo {$installment->fresh()->receipt_number}).";

        if ($envio['success']) {
            return $this->redirectBack($installment)->with('success', $message . ' Comprovante enviado por WhatsApp.');
        }

        if ($envio['skipped'] ?? false) {
            return $this->redirectBack($installment)
                ->with('warning', 'Pagamento registrado. Comprovante não enviado — patrocinador sem telefone cadastrado.');
        }

        return $this->redirectBack($installment)
            ->with('warning', $message . ' Falha ao enviar o comprovante por WhatsApp — use "Reenviar comprovante".');
    }

    /**
     * Pagamento em lote (ex: patrocinador quitou o carnê inteiro).
     */
    public function payBatch(Request $request)
    {
        $data = $request->validate(
            [
                'installment_ids' => 'required|array|min:1',
                'installment_ids.*' => 'integer|exists:campaign_installments,id',
                'payment_method' => 'required|in:dinheiro,pix,cartao,outro',
                'paid_at' => 'nullable|date',
            ],
            [
                'installment_ids.required' => 'Selecione ao menos uma parcela.',
                'payment_method.required' => 'Informe a forma de pagamento.',
            ]
        );

        $installments = CampaignInstallment::with('sponsor.campaign')
            ->whereIn('id', $data['installment_ids'])
            ->where('status', CampaignInstallment::STATUS_PENDENTE)
            ->orderBy('installment_number')
            ->get();

        if ($installments->isEmpty()) {
            return back()->with('error', 'Nenhuma parcela pendente entre as selecionadas.');
        }

        $paid = 0;
        $sent = 0;
        $failed = 0;

        foreach ($installments as $installment) {
            $this->markAsPaid($installment, $data['payment_method'], $data['paid_at'] ?? null);
            $paid++;

            $envio = $this->receiptService->enviarComprovante($installment->fresh(['sponsor.campaign']));
            if ($envio['success']) {
                $sent++;
            } elseif (!($envio['skipped'] ?? false)) {
                $failed++;
            }
        }

        $message = "{$paid} parcela(s) registrada(s) como paga(s). Comprovantes enviados: {$sent}.";
        if ($failed > 0) {
            $message .= " Falhas de envio: {$failed} (use \"Reenviar comprovante\").";
        }

        return back()->with($failed > 0 ? 'warning' : 'success', $message);
    }

    /**
     * Estorno: devolve a parcela para pendente, registrando quem estornou.
     * O número de recibo já emitido nunca é reutilizado (numeração por máximo).
     */
    public function reverse(CampaignInstallment $installment)
    {
        if (!$installment->isPaid()) {
            return $this->redirectBack($installment)->with('error', 'Apenas parcelas pagas podem ser estornadas.');
        }

        $installment->update([
            'status' => CampaignInstallment::STATUS_PENDENTE,
            'paid_at' => null,
            'payment_method' => null,
            'receipt_number' => null,
            'receipt_sent_at' => null,
            'paid_by' => null,
            'reversed_at' => now(),
            'reversed_by' => Auth::id(),
        ]);

        return $this->redirectBack($installment)
            ->with('success', "Pagamento da parcela {$installment->installment_number} estornado com sucesso.");
    }

    /**
     * Baixa o recibo em PDF da parcela paga.
     */
    public function receipt(CampaignInstallment $installment)
    {
        if (!$installment->isPaid()) {
            return $this->redirectBack($installment)->with('error', 'A parcela ainda não foi paga.');
        }

        $path = $this->receiptService->gerarPdfRecibo($installment);
        if (!$path) {
            return $this->redirectBack($installment)->with('error', 'Não foi possível gerar o recibo em PDF.');
        }

        return response()->download($path, 'recibo-' . str_replace('/', '-', (string) $installment->receipt_number) . '.pdf')
            ->deleteFileAfterSend(true);
    }

    public function resendReceipt(CampaignInstallment $installment)
    {
        if (!$installment->isPaid()) {
            return $this->redirectBack($installment)->with('error', 'A parcela ainda não foi paga.');
        }

        $envio = $this->receiptService->enviarComprovante($installment);

        if ($envio['success']) {
            return $this->redirectBack($installment)->with('success', 'Comprovante reenviado por WhatsApp.');
        }

        if ($envio['skipped'] ?? false) {
            return $this->redirectBack($installment)->with('warning', 'Patrocinador sem telefone cadastrado — comprovante não enviado.');
        }

        return $this->redirectBack($installment)->with('error', 'Falha ao reenviar o comprovante: ' . ($envio['error'] ?? 'erro desconhecido'));
    }

    private function markAsPaid(CampaignInstallment $installment, string $paymentMethod, ?string $paidAt): void
    {
        DB::transaction(function () use ($installment, $paymentMethod, $paidAt) {
            $campaign = $installment->sponsor->campaign;

            $installment->update([
                'status' => CampaignInstallment::STATUS_PAGO,
                'paid_at' => $paidAt ? \Illuminate\Support\Carbon::parse($paidAt) : now(),
                'paid_by' => Auth::id(),
                'payment_method' => $paymentMethod,
                'receipt_number' => $campaign->nextReceiptNumber(),
            ]);
        });
    }

    private function redirectBack(CampaignInstallment $installment)
    {
        return redirect()->route('financial.campaigns.show', $installment->sponsor->campaign_id);
    }
}
