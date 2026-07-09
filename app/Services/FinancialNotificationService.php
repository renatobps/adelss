<?php

namespace App\Services;

use App\Models\FinancialCategory;
use App\Models\FinancialNotificationLog;
use App\Models\FinancialTransaction;
use App\Models\Member;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FinancialNotificationService
{
    public function __construct(
        private WhatsAppService $whatsappService
    ) {}

    public function enviarComprovanteReceita(
        FinancialTransaction $transaction,
        ?int $triggeredByUserId = null,
        bool $force = false
    ): array {
        if (!config('financial.whatsapp.dizimo_receipt_enabled', true)) {
            return ['success' => false, 'error' => 'Notificações de comprovante desabilitadas.'];
        }

        $transaction->loadMissing(['member', 'category']);

        if (!$this->deveEnviarComprovante($transaction)) {
            return ['success' => false, 'error' => 'Transação não elegível para comprovante.'];
        }

        $member = $transaction->member;
        if (!$member || empty($member->phone)) {
            return ['success' => false, 'error' => 'Membro sem telefone cadastrado.'];
        }

        if (!$force && $this->jaNotificado($transaction->id, FinancialNotificationLog::TYPE_RECEIPT_MEMBER, $member->id)) {
            return ['success' => false, 'error' => 'Comprovante já enviado para este membro.'];
        }

        $mensagem = $this->montarMensagemComprovante($transaction);
        $resultado = ['success' => false, 'error' => 'Falha ao enviar comprovante.'];

        if (config('financial.whatsapp.send_pdf_receipt', true)) {
            $pdfPath = $this->gerarPdfRecibo($transaction);
            if ($pdfPath) {
                $resultado = $this->whatsappService->enviarDocumentoArquivo(
                    $member->phone,
                    $pdfPath,
                    'recibo-' . $transaction->id . '.pdf',
                    $mensagem
                );
                @unlink($pdfPath);
            }
        }

        if (!($resultado['success'] ?? false)) {
            $resultado = $this->whatsappService->enviarMensagem($member->phone, $mensagem);
        }

        $this->registrarLog(
            $transaction,
            FinancialNotificationLog::TYPE_RECEIPT_MEMBER,
            $member,
            $mensagem,
            $resultado,
            $triggeredByUserId
        );

        return $resultado;
    }

    public function notificarDespesaAPagar(
        FinancialTransaction $transaction,
        ?int $triggeredByUserId = null,
        bool $force = false
    ): array {
        if (!config('financial.whatsapp.expense_alert_enabled', true)) {
            return ['success' => false, 'error' => 'Alertas de despesa desabilitados.'];
        }

        $transaction->loadMissing(['contact', 'category']);

        if ($transaction->type !== 'despesa' || $transaction->is_paid || $transaction->status !== 'a_pagar') {
            return ['success' => false, 'error' => 'Despesa não está pendente de pagamento.'];
        }

        if (!$force && $this->jaNotificado($transaction->id, FinancialNotificationLog::TYPE_EXPENSE_TREASURER)) {
            return ['success' => false, 'error' => 'Tesoureiro já notificado sobre esta despesa.'];
        }

        $tesoureiros = $this->buscarTesoureiros();
        if ($tesoureiros->isEmpty()) {
            return ['success' => false, 'error' => 'Nenhum tesoureiro com telefone cadastrado.'];
        }

        $mensagem = $this->montarMensagemDespesa($transaction);
        $enviadas = 0;
        $erros = 0;
        $ultimoErro = null;

        foreach ($tesoureiros as $tesoureiro) {
            $resultado = $this->whatsappService->enviarMensagem($tesoureiro->phone, $mensagem);
            $this->registrarLog(
                $transaction,
                FinancialNotificationLog::TYPE_EXPENSE_TREASURER,
                $tesoureiro,
                $mensagem,
                $resultado,
                $triggeredByUserId
            );

            if ($resultado['success'] ?? false) {
                $enviadas++;
            } else {
                $erros++;
                $ultimoErro = $resultado['error'] ?? 'Erro desconhecido';
            }
        }

        if ($enviadas > 0) {
            return ['success' => true, 'enviadas' => $enviadas, 'erros' => $erros];
        }

        return ['success' => false, 'error' => $ultimoErro ?? 'Falha ao notificar tesoureiro(s).'];
    }

    public function notificarDespesasVencendo(): array
    {
        if (!config('financial.whatsapp.due_reminder_enabled', true)) {
            return ['success' => true, 'message' => 'Lembretes de vencimento desabilitados.', 'notificadas' => 0];
        }

        $daysAhead = max(0, (int) config('financial.whatsapp.due_reminder_days_ahead', 1));
        $start = now()->startOfDay();
        $end = now()->addDays($daysAhead)->endOfDay();

        $transactions = FinancialTransaction::query()
            ->despesas()
            ->where('is_paid', false)
            ->where('status', 'a_pagar')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->with(['contact', 'category'])
            ->get();

        $tesoureiros = $this->buscarTesoureiros();
        if ($tesoureiros->isEmpty()) {
            return ['success' => false, 'error' => 'Nenhum tesoureiro com telefone cadastrado.', 'notificadas' => 0];
        }

        $notificadas = 0;

        foreach ($transactions as $transaction) {
            if ($this->jaNotificadoHoje($transaction->id, FinancialNotificationLog::TYPE_EXPENSE_DUE_REMINDER)) {
                continue;
            }

            $mensagem = $this->montarMensagemVencimento($transaction);

            foreach ($tesoureiros as $tesoureiro) {
                $resultado = $this->whatsappService->enviarMensagem($tesoureiro->phone, $mensagem);
                $this->registrarLog(
                    $transaction,
                    FinancialNotificationLog::TYPE_EXPENSE_DUE_REMINDER,
                    $tesoureiro,
                    $mensagem,
                    $resultado
                );
            }

            $notificadas++;
        }

        return ['success' => true, 'notificadas' => $notificadas];
    }

    public function deveEnviarComprovante(FinancialTransaction $transaction): bool
    {
        if ($transaction->type !== 'receita' || !$transaction->is_paid || !$transaction->member_id) {
            return false;
        }

        return $this->categoriaEnviaComprovante($transaction->category);
    }

    public function categoriaEnviaComprovante(?FinancialCategory $category): bool
    {
        if (!$category) {
            return false;
        }

        if ($category->sends_receipt) {
            return true;
        }

        if ($category->slug && in_array($category->slug, ['dizimo', 'oferta'], true)) {
            return true;
        }

        $name = mb_strtolower($category->name);
        return str_contains($name, 'dízimo')
            || str_contains($name, 'dizimo')
            || str_contains($name, 'oferta');
    }

    /**
     * @return Collection<int, Member>
     */
    public function buscarTesoureiros(): Collection
    {
        return Member::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereHas('role', function ($query) {
                $query->where('name', 'like', '%Tesoureiro%')
                    ->where('is_active', true);
            })
            ->get();
    }

    public function gerarPdfRecibo(FinancialTransaction $transaction): ?string
    {
        $transaction->loadMissing(['member', 'contact', 'category']);

        try {
            $pdf = Pdf::loadView('financial.transactions.receipt-pdf', [
                'transaction' => $transaction,
                'logoPath' => public_path('img/img/LOG SS AZUL.png'),
            ])->setPaper('a4');

            $directory = storage_path('app/temp/receipts');
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $path = $directory . '/recibo-' . $transaction->id . '-' . time() . '.pdf';
            $pdf->save($path);

            return $path;
        } catch (\Throwable $e) {
            Log::warning('Falha ao gerar PDF de recibo financeiro', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function montarMensagemComprovante(FinancialTransaction $transaction): string
    {
        $nome = $transaction->member?->name ?? 'Membro';
        $categoria = $transaction->category?->name ?? 'Contribuição';
        $valor = number_format((float) $transaction->amount, 2, ',', '.');
        $data = $transaction->transaction_date?->format('d/m/Y') ?? now()->format('d/m/Y');

        return implode("\n", [
            '🙏 *ADEL São Sebastião*',
            '',
            "Olá, *{$nome}*!",
            '',
            "Registramos seu *{$categoria}* no valor de *R$ {$valor}* em {$data}.",
            '',
            'Segue em anexo o comprovante. Obrigado pela sua contribuição!',
        ]);
    }

    private function montarMensagemDespesa(FinancialTransaction $transaction): string
    {
        $valor = number_format((float) $transaction->amount, 2, ',', '.');
        $vencimento = $transaction->due_date
            ? $transaction->due_date->format('d/m/Y')
            : 'não informado';
        $fornecedor = $transaction->contact?->name ?? 'não informado';

        return implode("\n", [
            '💰 *ADEL São Sebastião — Despesa a pagar*',
            '',
            "*Descrição:* {$transaction->description}",
            "*Valor:* R$ {$valor}",
            "*Vencimento:* {$vencimento}",
            "*Fornecedor:* {$fornecedor}",
            '',
            'Acesse o módulo financeiro para registrar o pagamento.',
        ]);
    }

    private function montarMensagemVencimento(FinancialTransaction $transaction): string
    {
        $valor = number_format((float) $transaction->amount, 2, ',', '.');
        $vencimento = $transaction->due_date?->format('d/m/Y') ?? '-';
        $fornecedor = $transaction->contact?->name ?? 'não informado';

        return implode("\n", [
            '⏰ *ADEL São Sebastião — Despesa vencendo*',
            '',
            "*Descrição:* {$transaction->description}",
            "*Valor:* R$ {$valor}",
            "*Vencimento:* {$vencimento}",
            "*Fornecedor:* {$fornecedor}",
            '',
            'Esta despesa está pendente de pagamento.',
        ]);
    }

    private function jaNotificado(int $transactionId, string $type, ?int $memberId = null): bool
    {
        $query = FinancialNotificationLog::query()
            ->where('financial_transaction_id', $transactionId)
            ->where('notification_type', $type)
            ->where('status', 'sent');

        if ($memberId !== null) {
            $query->where('member_id', $memberId);
        }

        return $query->exists();
    }

    private function jaNotificadoHoje(int $transactionId, string $type): bool
    {
        return FinancialNotificationLog::query()
            ->where('financial_transaction_id', $transactionId)
            ->where('notification_type', $type)
            ->where('status', 'sent')
            ->whereDate('created_at', now()->toDateString())
            ->exists();
    }

    /**
     * @param array{success?: bool, error?: string} $resultado
     */
    private function registrarLog(
        FinancialTransaction $transaction,
        string $type,
        Member $member,
        string $mensagem,
        array $resultado,
        ?int $triggeredByUserId = null
    ): void {
        FinancialNotificationLog::create([
            'financial_transaction_id' => $transaction->id,
            'member_id' => $member->id,
            'phone' => WhatsAppService::normalizarNumero((string) $member->phone),
            'notification_type' => $type,
            'status' => ($resultado['success'] ?? false) ? 'sent' : 'failed',
            'message' => $mensagem,
            'error' => ($resultado['success'] ?? false) ? null : ($resultado['error'] ?? 'Erro desconhecido'),
            'triggered_by_user_id' => $triggeredByUserId,
        ]);
    }
}
