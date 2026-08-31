<?php

namespace App\Services;

use App\Models\FinancialAutomation;
use App\Models\FinancialCategory;
use App\Models\FinancialNotificationLog;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Services\Financial\PdfSignatureService;
use App\Support\FinancialReceiptLogo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FinancialNotificationService
{
    public function __construct(
        private WhatsAppService $whatsappService,
        private PdfSignatureService $signatures,
    ) {}

    public function enviarComprovanteReceita(
        FinancialTransaction $transaction,
        ?int $triggeredByUserId = null,
        bool $force = false
    ): array {
        $automation = $this->contributionThanksAutomation();

        if (! $force) {
            if ($automation) {
                if (! $automation->enabled) {
                    return ['success' => false, 'error' => 'Automação de agradecimento desabilitada. Use o envio manual ou ative a automação em Financeiro → Automações.'];
                }
            } elseif (! config('financial.whatsapp.dizimo_receipt_enabled', true)) {
                return ['success' => false, 'error' => 'Notificações de comprovante desabilitadas.'];
            }
        }

        $transaction->loadMissing(['member', 'category']);

        if (! $this->deveEnviarComprovante($transaction, $force ? null : $automation)) {
            return ['success' => false, 'error' => 'Esta receita não é dízimo/oferta pago de um membro. Só esse tipo gera comprovante.'];
        }

        $member = $transaction->member;
        if (!$member || empty($member->phone)) {
            return ['success' => false, 'error' => 'Membro sem telefone cadastrado.'];
        }

        if (!$force && $this->jaNotificado($transaction->id, FinancialNotificationLog::TYPE_RECEIPT_MEMBER, $member->id)) {
            return ['success' => false, 'error' => 'Comprovante já enviado para este membro.'];
        }

        if ($automation && !$force) {
            $settingsGate = $this->validarRegrasAutomacao($automation, $transaction);
            if ($settingsGate !== null) {
                return $settingsGate;
            }
        }

        $mensagem = $this->montarMensagemComprovante($transaction, $automation);
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

        $transaction->loadMissing(['member', 'contact', 'category']);

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

    public function notificarDespesasVencendo(bool $force = false): array
    {
        $automation = null;
        try {
            if (Schema::hasTable('financial_automations')) {
                $automation = FinancialAutomation::dueReminder();
                if (!$automation->enabled) {
                    return ['success' => true, 'message' => 'Lembrete de despesas desabilitado.', 'notificadas' => 0];
                }
            }
        } catch (\Throwable $e) {
            // fallback para config
        }

        if (!$automation && !config('financial.whatsapp.due_reminder_enabled', true)) {
            return ['success' => true, 'message' => 'Lembretes de vencimento desabilitados.', 'notificadas' => 0];
        }

        $settings = $automation?->mergedSettings() ?? [
            'days_ahead' => (int) config('financial.whatsapp.due_reminder_days_ahead', 1),
            'send_time' => '08:00',
            'second_reminder' => false,
            'min_amount' => 0,
            'message_template' => FinancialAutomation::defaultDueReminderMessage(),
        ];

        $sendTime = (string) ($settings['send_time'] ?? '08:00');
        if (!$force && now()->format('H:i') !== $sendTime) {
            return ['success' => true, 'message' => 'Fora do horário de envio.', 'notificadas' => 0];
        }

        $tesoureiros = $this->buscarTesoureiros();
        if ($tesoureiros->isEmpty()) {
            return ['success' => false, 'error' => 'Nenhum tesoureiro com telefone cadastrado.', 'notificadas' => 0];
        }

        $daysAhead = max(0, (int) ($settings['days_ahead'] ?? 1));
        $minAmount = (float) ($settings['min_amount'] ?? 0);
        $notificadas = 0;

        $primary = $this->enviarLoteDespesasVencendo(
            $tesoureiros,
            $daysAhead,
            $minAmount,
            (string) ($settings['message_template'] ?? ''),
            FinancialNotificationLog::TYPE_EXPENSE_DUE_REMINDER,
            $force
        );
        $notificadas += $primary;

        if (!empty($settings['second_reminder']) && $daysAhead !== 0) {
            $notificadas += $this->enviarLoteDespesasVencendo(
                $tesoureiros,
                0,
                $minAmount,
                (string) ($settings['message_template'] ?? ''),
                FinancialNotificationLog::TYPE_EXPENSE_DUE_REMINDER_SECOND,
                $force
            );
        }

        return ['success' => true, 'notificadas' => $notificadas];
    }

    /**
     * @param Collection<int, Member> $tesoureiros
     */
    private function enviarLoteDespesasVencendo(
        Collection $tesoureiros,
        int $daysAhead,
        float $minAmount,
        string $template,
        string $notificationType,
        bool $force = false
    ): int {
        $targetDate = now()->addDays($daysAhead)->toDateString();

        $transactions = FinancialTransaction::query()
            ->despesas()
            ->where('is_paid', false)
            ->where('status', 'a_pagar')
            ->whereNotNull('due_date')
            ->whereDate('due_date', $targetDate)
            ->when($minAmount > 0, fn ($q) => $q->where('amount', '>=', $minAmount))
            ->with(['member', 'contact', 'category'])
            ->orderBy('due_date')
            ->get();

        if ($transactions->isEmpty()) {
            return 0;
        }

        if (!$force && $this->jaEnviouTipoHoje($notificationType)) {
            return 0;
        }

        $total = (float) $transactions->sum('amount');
        $lista = $transactions->map(function (FinancialTransaction $tx) {
            $valor = number_format((float) $tx->amount, 2, ',', '.');
            $venc = $tx->due_date?->format('d/m') ?? '-';
            $desc = $tx->description ?: ($tx->category?->name ?? 'Despesa');

            return "• {$desc} — R$ {$valor} ({$venc})";
        })->implode("\n");

        $enviados = 0;

        foreach ($tesoureiros as $tesoureiro) {
            $mensagem = $this->montarMensagemLoteVencimento(
                $template,
                $tesoureiro->name ?? 'Tesoureiro',
                $daysAhead,
                $transactions->count(),
                $total,
                $lista
            );

            $resultado = $this->whatsappService->enviarMensagem($tesoureiro->phone, $mensagem);
            $this->registrarLogAvulso($notificationType, $tesoureiro, $mensagem, $resultado);

            if ($resultado['success'] ?? false) {
                $enviados++;
            }
        }

        return $enviados > 0 ? $transactions->count() : 0;
    }

    public function enviarResumoFinanceiroInteligente(bool $force = false): array
    {
        $automation = null;
        try {
            if (Schema::hasTable('financial_automations')) {
                $automation = FinancialAutomation::smartSummary();
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Automação de resumo indisponível.'];
        }

        if (!$automation) {
            return ['success' => false, 'error' => 'Automação de resumo indisponível.'];
        }

        if (!$force && !$automation->enabled) {
            return ['success' => true, 'message' => 'Resumo financeiro desabilitado.', 'enviados' => 0];
        }

        $settings = $automation->mergedSettings();
        $sendTime = (string) ($settings['send_time'] ?? '08:00');
        $frequency = (string) ($settings['frequency'] ?? 'monthly');
        $dayOfMonth = (int) ($settings['day_of_month'] ?? 1);

        if (!$force) {
            if (now()->format('H:i') !== $sendTime) {
                return ['success' => true, 'message' => 'Fora do horário de envio.', 'enviados' => 0];
            }

            if ($frequency === 'monthly' && (int) now()->day !== $dayOfMonth) {
                return ['success' => true, 'message' => 'Fora do dia configurado.', 'enviados' => 0];
            }

            if ($frequency === 'weekly' && (int) now()->dayOfWeekIso !== min(7, max(1, $dayOfMonth))) {
                // day_of_month 1-7 = segunda a domingo quando weekly
                return ['success' => true, 'message' => 'Fora do dia da semana configurado.', 'enviados' => 0];
            }

            if ($this->jaEnviouTipoHoje(FinancialNotificationLog::TYPE_SMART_SUMMARY)) {
                return ['success' => true, 'message' => 'Resumo já enviado hoje.', 'enviados' => 0];
            }
        }

        // Envio manual: usa destinatários configurados mesmo se o card Tesoureiros estiver desligado
        $tesoureiros = $force
            ? $this->buscarTesoureirosParaEnvioManual()
            : $this->buscarTesoureiros();
        if ($tesoureiros->isEmpty()) {
            return ['success' => false, 'error' => 'Nenhum tesoureiro com telefone cadastrado.', 'enviados' => 0];
        }

        $mensagem = $this->montarMensagemResumoInteligente($settings);
        $enviados = 0;
        $ultimoErro = null;

        foreach ($tesoureiros as $tesoureiro) {
            $resultado = $this->whatsappService->enviarMensagem($tesoureiro->phone, $mensagem);
            $this->registrarLogAvulso(
                FinancialNotificationLog::TYPE_SMART_SUMMARY,
                $tesoureiro,
                $mensagem,
                $resultado
            );

            if ($resultado['success'] ?? false) {
                $enviados++;
            } else {
                $ultimoErro = $resultado['error'] ?? 'Erro desconhecido';
            }
        }

        if ($enviados > 0) {
            return ['success' => true, 'enviados' => $enviados];
        }

        return ['success' => false, 'error' => $ultimoErro ?? 'Falha ao enviar resumo.', 'enviados' => 0];
    }

    public function deveEnviarComprovante(
        FinancialTransaction $transaction,
        ?FinancialAutomation $automation = null
    ): bool {
        if ($transaction->type !== 'receita' || !$transaction->is_paid || !$transaction->member_id) {
            return false;
        }

        $automation ??= $this->contributionThanksAutomation();

        return $this->categoriaEnviaComprovante($transaction->category, $automation);
    }

    public function categoriaEnviaComprovante(
        ?FinancialCategory $category,
        ?FinancialAutomation $automation = null
    ): bool {
        if (!$category) {
            return false;
        }

        $eligibleIds = $automation?->setting('eligible_category_ids', []) ?? [];
        if (is_array($eligibleIds) && count($eligibleIds) > 0) {
            return in_array((int) $category->id, array_map('intval', $eligibleIds), true);
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
        try {
            if (Schema::hasTable('financial_automations')) {
                $automation = FinancialAutomation::treasurers();
                if (!$automation->enabled) {
                    return collect();
                }

                $configured = $this->buscarTesoureirosConfigurados($automation);
                if ($configured->isNotEmpty()) {
                    return $configured;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Não foi possível carregar tesoureiros configurados', [
                'error' => $e->getMessage(),
            ]);
        }

        return Member::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereHas('role', function ($query) {
                $query->where('name', 'like', '%Tesoureiro%')
                    ->where('is_active', true);
            })
            ->get();
    }

    /**
     * Destinatários para envio manual (ignora o toggle do card Tesoureiros).
     *
     * @return Collection<int, Member>
     */
    public function buscarTesoureirosParaEnvioManual(): Collection
    {
        try {
            if (Schema::hasTable('financial_automations')) {
                $configured = $this->buscarTesoureirosConfigurados(FinancialAutomation::treasurers());
                if ($configured->isNotEmpty()) {
                    return $configured;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Não foi possível carregar tesoureiros para envio manual', [
                'error' => $e->getMessage(),
            ]);
        }

        return Member::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereHas('role', function ($query) {
                $query->where('name', 'like', '%Tesoureiro%')
                    ->where('is_active', true);
            })
            ->get();
    }

    /**
     * @return Collection<int, Member>
     */
    private function buscarTesoureirosConfigurados(?FinancialAutomation $automation = null): Collection
    {
        try {
            if (!Schema::hasTable('financial_automations')) {
                return collect();
            }

            $automation ??= FinancialAutomation::treasurers();
            $recipients = $automation->treasurerRecipients();
        } catch (\Throwable $e) {
            Log::warning('Não foi possível carregar tesoureiros configurados', [
                'error' => $e->getMessage(),
            ]);

            return collect();
        }

        if (count($recipients) === 0) {
            return collect();
        }

        $memberIds = collect($recipients)
            ->pluck('member_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $membersById = $memberIds
            ? Member::query()->whereIn('id', $memberIds)->get()->keyBy('id')
            : collect();

        return collect($recipients)->map(function (array $recipient) use ($membersById) {
            $memberId = $recipient['member_id'] ?? null;
            $base = ($memberId && $membersById->has($memberId))
                ? $membersById->get($memberId)
                : null;

            $member = new Member();
            if ($base) {
                $member->id = $base->id;
                $member->name = $base->name;
                $member->phone = $base->phone;
            }

            if ($recipient['name'] !== '') {
                $member->name = $recipient['name'];
            } elseif (empty($member->name)) {
                $member->name = 'Tesoureiro';
            }

            $member->phone = $recipient['phone'];

            return $member;
        })->values();
    }

    public function gerarPdfRecibo(FinancialTransaction $transaction): ?string
    {
        $transaction->loadMissing(['member', 'contact', 'category']);

        try {
            $dir = storage_path('app/temp/receipts');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $fundoSource = FinancialReceiptLogo::backgroundAbsolutePath();
            $fundoPath = null;
            if ($fundoSource) {
                $fundoPath = $dir.DIRECTORY_SEPARATOR.'recibo-fundo.png';
                if (! is_file($fundoPath) || filemtime($fundoPath) < filemtime($fundoSource)) {
                    @copy($fundoSource, $fundoPath);
                }
                $fundoPath = str_replace('\\', '/', $fundoPath);
            }

            $assinaturaPath = $this->signatures->imagePath(PdfSignatureService::ROLE_TESOUREIRO);
            if ($assinaturaPath) {
                $assinaturaPath = str_replace('\\', '/', $assinaturaPath);
            }

            $pdf = Pdf::loadView('financial.transactions.receipt-pdf', [
                'transaction' => $transaction,
                'fundoPath' => $fundoPath,
                'tesoureiroNome' => $this->signatures->tesoureiroNome(),
                'tesoureiroAssinaturaSrc' => $assinaturaPath,
            ])->setPaper(FinancialReceiptLogo::pdfPaper());

            $path = $dir.DIRECTORY_SEPARATOR.'recibo-'.$transaction->id.'-'.time().'.pdf';
            $pdf->save($path);

            return is_file($path) ? $path : null;
        } catch (\Throwable $e) {
            Log::warning('Falha ao gerar PDF de recibo financeiro', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function montarMensagemComprovante(
        FinancialTransaction $transaction,
        ?FinancialAutomation $automation = null
    ): string {
        $nome = $transaction->member?->name ?? 'Membro';
        $categoria = $transaction->category?->name ?? 'Contribuição';
        $valor = number_format((float) $transaction->amount, 2, ',', '.');
        $igreja = 'ADEL São Sebastião';

        $template = trim((string) ($automation?->setting('message_template') ?? ''));
        if ($template !== '') {
            return str_replace(
                ['{igreja}', '{nome}', '{valor}', '{tipo}'],
                [$igreja, $nome, $valor, $categoria],
                $template
            );
        }

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

    private function contributionThanksAutomation(): ?FinancialAutomation
    {
        try {
            if (!Schema::hasTable('financial_automations')) {
                return null;
            }

            return FinancialAutomation::contributionThanks();
        } catch (\Throwable $e) {
            Log::warning('Não foi possível carregar automação de agradecimento', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{success: bool, error: string}|null
     */
    private function validarRegrasAutomacao(
        FinancialAutomation $automation,
        FinancialTransaction $transaction
    ): ?array {
        $settings = $automation->mergedSettings();
        $minAmount = (float) ($settings['min_amount'] ?? 0);

        if ((float) $transaction->amount < $minAmount) {
            return ['success' => false, 'error' => 'Valor abaixo do mínimo configurado para agradecimento.'];
        }

        $dailyLimit = max(1, min(30, (int) ($settings['daily_limit'] ?? 30)));
        $sentToday = FinancialNotificationLog::query()
            ->where('notification_type', FinancialNotificationLog::TYPE_RECEIPT_MEMBER)
            ->where('status', 'sent')
            ->whereDate('created_at', today())
            ->count();

        if ($sentToday >= $dailyLimit) {
            return ['success' => false, 'error' => 'Limite diário de agradecimentos atingido.'];
        }

        $windowStart = (string) ($settings['window_start'] ?? '08:00');
        $windowEnd = (string) ($settings['window_end'] ?? '20:00');
        $now = Carbon::now();

        try {
            $start = Carbon::today()->setTimeFromTimeString($windowStart . (strlen($windowStart) === 5 ? ':00' : ''));
            $end = Carbon::today()->setTimeFromTimeString($windowEnd . (strlen($windowEnd) === 5 ? ':00' : ''));
        } catch (\Throwable $e) {
            return null;
        }

        if ($start->lte($end)) {
            if ($now->lt($start) || $now->gt($end)) {
                return ['success' => false, 'error' => 'Fora da janela horária configurada para envio.'];
            }
        } else {
            // Janela atravessa meia-noite (ex.: 22:00–06:00)
            if ($now->gt($end) && $now->lt($start)) {
                return ['success' => false, 'error' => 'Fora da janela horária configurada para envio.'];
            }
        }

        return null;
    }

    private function montarMensagemDespesa(FinancialTransaction $transaction): string
    {
        $valor = number_format((float) $transaction->amount, 2, ',', '.');
        $vencimento = $transaction->due_date
            ? $transaction->due_date->format('d/m/Y')
            : 'não informado';
        $fornecedor = $transaction->source_name ?: 'não informado';

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
        $fornecedor = $transaction->source_name ?: 'não informado';

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

    private function montarMensagemLoteVencimento(
        string $template,
        string $tesoureiro,
        int $dias,
        int $quantidade,
        float $total,
        string $lista
    ): string {
        $template = trim($template) !== ''
            ? $template
            : FinancialAutomation::defaultDueReminderMessage();

        $totalFmt = 'R$ ' . number_format($total, 2, ',', '.');

        return str_replace(
            ['{igreja}', '{tesoureiro}', '{dias}', '{quantidade}', '{total}', '{lista}'],
            ['ADEL São Sebastião', $tesoureiro, (string) $dias, (string) $quantidade, $totalFmt, $lista],
            $template
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function montarMensagemResumoInteligente(array $settings): string
    {
        $periodEnd = now()->subDay()->endOfDay();
        $periodStart = now()->subDay()->startOfMonth();
        if (($settings['frequency'] ?? 'monthly') === 'weekly') {
            $periodStart = now()->subDays(7)->startOfDay();
            $periodEnd = now()->subDay()->endOfDay();
        }

        $prevStart = (clone $periodStart)->subMonth()->startOfMonth();
        $prevEnd = (clone $periodStart)->subMonth()->endOfMonth();
        if (($settings['frequency'] ?? 'monthly') === 'weekly') {
            $prevStart = (clone $periodStart)->subDays(7);
            $prevEnd = (clone $periodStart)->subDay()->endOfDay();
        }

        $entradas = (float) FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->sum('amount');
        $saidas = (float) FinancialTransaction::despesas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->sum('amount');
        $saldo = $entradas - $saidas;

        $compare = !empty($settings['compare_previous']);
        $varEntradas = $varSaidas = $varSaldo = null;
        if ($compare) {
            $entradasPrev = (float) FinancialTransaction::receitas()
                ->where('is_paid', true)
                ->whereBetween('transaction_date', [$prevStart->toDateString(), $prevEnd->toDateString()])
                ->sum('amount');
            $saidasPrev = (float) FinancialTransaction::despesas()
                ->where('is_paid', true)
                ->whereBetween('transaction_date', [$prevStart->toDateString(), $prevEnd->toDateString()])
                ->sum('amount');
            $saldoPrev = $entradasPrev - $saidasPrev;
            $varEntradas = $this->variacaoPercentual($entradas, $entradasPrev);
            $varSaidas = $this->variacaoPercentual($saidas, $saidasPrev);
            $varSaldo = $this->variacaoPercentual($saldo, $saldoPrev);
        }

        $topEntradas = FinancialTransaction::receitas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        $topSaidas = FinancialTransaction::despesas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        $categoryIds = $topEntradas->pluck('category_id')
            ->merge($topSaidas->pluck('category_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $categoriesById = $categoryIds
            ? FinancialCategory::query()->whereIn('id', $categoryIds)->get()->keyBy('id')
            : collect();

        $aVencer = FinancialTransaction::despesas()
            ->where('is_paid', false)
            ->where('status', 'a_pagar')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->sum('amount');

        $vencidas = FinancialTransaction::despesas()
            ->where('is_paid', false)
            ->where('status', 'a_pagar')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->sum('amount');

        $fmt = fn (float $v) => 'R$ ' . number_format($v, 2, ',', '.');
        $periodoLabel = $periodStart->format('d/m') . ' a ' . $periodEnd->format('d/m/Y');

        $linhas = [
            '📊 *ADEL São Sebastião — Resumo financeiro*',
            "*Período:* {$periodoLabel}",
            '',
        ];

        $opening = trim((string) ($settings['opening_message'] ?? ''));
        if ($opening !== '') {
            $linhas[] = $opening;
            $linhas[] = '';
        }

        $linhas[] = '🟢 *Entradas:* ' . $fmt($entradas) . ($varEntradas !== null ? " ({$varEntradas})" : '');
        $linhas[] = '🔴 *Saídas:* ' . $fmt($saidas) . ($varSaidas !== null ? " ({$varSaidas})" : '');
        $linhas[] = '💰 *Saldo:* ' . $fmt($saldo) . ($varSaldo !== null ? " ({$varSaldo})" : '');
        $linhas[] = '';
        $linhas[] = '*Top 3 Entradas*';
        if ($topEntradas->isEmpty()) {
            $linhas[] = '• Sem registros';
        } else {
            foreach ($topEntradas as $item) {
                $nome = $categoriesById->get($item->category_id)?->name ?? 'Outros';
                $linhas[] = '• ' . $nome . ' — ' . $fmt((float) $item->total);
            }
        }
        $linhas[] = '';
        $linhas[] = '*Top 3 Saídas*';
        if ($topSaidas->isEmpty()) {
            $linhas[] = '• Sem registros';
        } else {
            foreach ($topSaidas as $item) {
                $nome = $categoriesById->get($item->category_id)?->name ?? 'Outros';
                $linhas[] = '• ' . $nome . ' — ' . $fmt((float) $item->total);
            }
        }
        $linhas[] = '';
        $linhas[] = '⏳ *A vencer (7 dias):* ' . $fmt((float) $aVencer);
        $linhas[] = '⚠️ *Vencidas em aberto:* ' . $fmt((float) $vencidas);

        return implode("\n", $linhas);
    }

    private function variacaoPercentual(float $atual, float $anterior): string
    {
        if (abs($anterior) < 0.00001) {
            return $atual > 0 ? '+100%' : '0%';
        }

        $pct = (($atual - $anterior) / abs($anterior)) * 100;
        $sinal = $pct > 0 ? '+' : '';

        return $sinal . number_format($pct, 0, ',', '.') . '%';
    }

    private function jaEnviouTipoHoje(string $type): bool
    {
        return FinancialNotificationLog::query()
            ->where('notification_type', $type)
            ->where('status', 'sent')
            ->whereDate('created_at', today())
            ->exists();
    }

    /**
     * @param array{success?: bool, error?: string} $resultado
     */
    private function registrarLogAvulso(
        string $type,
        Member $member,
        string $mensagem,
        array $resultado,
        ?int $triggeredByUserId = null
    ): void {
        FinancialNotificationLog::create([
            'financial_transaction_id' => null,
            'member_id' => $member->id,
            'phone' => WhatsAppService::normalizarNumero((string) $member->phone),
            'notification_type' => $type,
            'status' => ($resultado['success'] ?? false) ? 'sent' : 'failed',
            'message' => $mensagem,
            'error' => ($resultado['success'] ?? false) ? null : ($resultado['error'] ?? 'Erro desconhecido'),
            'triggered_by_user_id' => $triggeredByUserId,
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
