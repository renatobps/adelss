<?php

namespace App\Services;

use App\Mail\CampaignReminderBatchAlert;
use App\Models\Campaign;
use App\Models\CampaignInstallment;
use App\Models\CampaignReminderLog;
use App\Models\CampaignReminderSetting;
use App\Models\CampaignSponsor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Lembretes de parcelas em atraso.
 *
 * Duas responsabilidades que não se misturam: decidir *quem* pode receber
 * (regras de negócio, imutáveis) e *como* a fila é despachada (proteções
 * contra banimento do WhatsApp, já que a Evolution GO é uma API não-oficial).
 */
class CampaignReminderService
{
    /** Falhas seguidas que interrompem o lote e alertam o administrador. */
    public const CONSECUTIVE_FAILURE_LIMIT = 5;

    public function __construct(
        private readonly WhatsAppService $whatsapp,
    ) {}

    // ---------------------------------------------------------------- regras

    /**
     * Situação de atraso do patrocinador. Devolve null quando não há parcela
     * vencida em aberto — ou seja, quando ele não deve ser cobrado.
     *
     * @return array{installments:Collection,count:int,amount:float,oldest:CampaignInstallment,days:int}|null
     */
    public function overdueContext(CampaignSponsor $sponsor): ?array
    {
        $overdue = $sponsor->overdueInstallments();

        if ($overdue->isEmpty()) {
            return null;
        }

        $oldest = $overdue->first();

        return [
            'installments' => $overdue,
            'count' => $overdue->count(),
            'amount' => (float) $overdue->sum('amount'),
            'oldest' => $oldest,
            'days' => (int) $oldest->due_date->diffInDays(now()->startOfDay()),
        ];
    }

    /**
     * Parcela a vencer dentro da janela de cortesia, se houver.
     * Só vale para quem não tem nenhuma parcela vencida.
     */
    public function courtesyContext(CampaignSponsor $sponsor, CampaignReminderSetting $settings): ?array
    {
        if ($this->overdueContext($sponsor) !== null) {
            return null;
        }

        $limit = now()->startOfDay()->addDays(max(0, $settings->courtesy_days_before));

        $next = $sponsor->installments
            ->filter(fn ($i) => $i->status === CampaignInstallment::STATUS_PENDENTE
                && $i->due_date !== null
                && $i->due_date->gte(now()->startOfDay())
                && $i->due_date->lte($limit))
            ->sortBy('due_date')
            ->first();

        if (! $next) {
            return null;
        }

        return [
            'installments' => collect([$next]),
            'count' => 1,
            'amount' => (float) $next->amount,
            'oldest' => $next,
            'days' => 0,
        ];
    }

    /**
     * Decide se o patrocinador recebe lembrete e, quando não, por quê.
     * O motivo é gravado no histórico, então precisa ser legível pela liderança.
     *
     * @return array{eligible:bool,reason:?string,context:?array}
     */
    public function eligibility(
        CampaignSponsor $sponsor,
        CampaignReminderSetting $settings,
        string $type = CampaignReminderLog::TYPE_ATRASO,
        bool $manual = false
    ): array {
        $deny = fn (string $reason) => ['eligible' => false, 'reason' => $reason, 'context' => null];

        if (! $sponsor->reminders_enabled) {
            return $deny('Lembretes desativados para este patrocinador.');
        }

        if (trim((string) $sponsor->phone) === '') {
            return $deny('Patrocinador sem telefone cadastrado.');
        }

        if ($type === CampaignReminderLog::TYPE_CORTESIA) {
            $context = $this->courtesyContext($sponsor, $settings);

            if (! $context) {
                return $deny('Nenhuma parcela a vencer na janela de cortesia.');
            }

            // A janela dura vários dias: um aviso por parcela, não um por dia.
            $alreadyWarned = CampaignReminderLog::query()
                ->where('campaign_sponsor_id', $sponsor->id)
                ->where('campaign_installment_id', $context['oldest']->id)
                ->where('type', CampaignReminderLog::TYPE_CORTESIA)
                ->where('status', CampaignReminderLog::STATUS_ENVIADO)
                ->exists();

            return $alreadyWarned
                ? $deny('Aviso de vencimento próximo já enviado para esta parcela.')
                : ['eligible' => true, 'reason' => null, 'context' => $context];
        }

        $context = $this->overdueContext($sponsor);

        if (! $context) {
            // Quitado, em dia ou não iniciado: nunca recebe cobrança.
            return $deny(match ($sponsor->situacaoDetalhada()) {
                'quitado' => 'Patrocinador quitado — não recebe cobrança.',
                'em_dia' => 'Patrocinador em dia — não recebe cobrança.',
                default => 'Nenhuma parcela vencida — não recebe cobrança.',
            });
        }

        // As checagens de cadência abaixo controlam o ritmo do automático; um
        // envio manual é uma decisão consciente da liderança e passa por cima.
        if (! $manual) {
            $sentForInstallment = $this->remindersSentFor($sponsor, $context['oldest']->id);

            if ($sentForInstallment >= $settings->max_reminders) {
                return $deny("Limite de {$settings->max_reminders} lembretes atingido para a parcela {$context['oldest']->installment_number}.");
            }

            $last = $this->lastSentAt($sponsor);
            if ($last && $last->diffInDays(now()) < $settings->days_between) {
                return $deny('Último lembrete enviado em ' . $last->format('d/m/Y')
                    . " (intervalo configurado: {$settings->days_between} dias).");
            }
        }

        return ['eligible' => true, 'reason' => null, 'context' => $context];
    }

    public function remindersSentFor(CampaignSponsor $sponsor, int $installmentId): int
    {
        return CampaignReminderLog::query()
            ->where('campaign_sponsor_id', $sponsor->id)
            ->where('campaign_installment_id', $installmentId)
            ->where('type', CampaignReminderLog::TYPE_ATRASO)
            ->where('status', CampaignReminderLog::STATUS_ENVIADO)
            ->count();
    }

    public function lastSentAt(CampaignSponsor $sponsor): ?Carbon
    {
        $last = CampaignReminderLog::query()
            ->where('campaign_sponsor_id', $sponsor->id)
            ->where('status', CampaignReminderLog::STATUS_ENVIADO)
            ->whereIn('type', [CampaignReminderLog::TYPE_ATRASO, CampaignReminderLog::TYPE_CORTESIA])
            ->max('sent_at');

        return $last ? Carbon::parse($last) : null;
    }

    /** O patrocinador já esgotou os lembretes da parcela mais antiga em aberto? */
    public function reachedLimit(CampaignSponsor $sponsor, CampaignReminderSetting $settings): bool
    {
        $context = $this->overdueContext($sponsor);

        return $context !== null
            && $this->remindersSentFor($sponsor, $context['oldest']->id) >= $settings->max_reminders;
    }

    // ------------------------------------------------------------- mensagem

    public function variables(CampaignSponsor $sponsor, array $context): array
    {
        $campaign = $sponsor->campaign;
        $summary = $sponsor->summary();
        $primeiroNome = explode(' ', trim($sponsor->name))[0] ?: $sponsor->name;

        return [
            '{nome}' => $primeiroNome,
            '{nome_completo}' => $sponsor->name,
            '{campanha}' => $campaign->name,
            '{parcelas_atraso}' => (string) $context['count'],
            '{valor_atraso}' => 'R$ ' . number_format($context['amount'], 2, ',', '.'),
            '{vencimento}' => $context['oldest']->due_date?->format('d/m/Y') ?? '—',
            '{dias_atraso}' => (string) $context['days'],
            '{total_pago}' => 'R$ ' . number_format($summary['paid_amount'], 2, ',', '.'),
            '{total_restante}' => 'R$ ' . number_format($summary['pending_amount'], 2, ',', '.'),
            '{parcelas_pagas}' => $summary['paid'] . '/' . $summary['total'],
        ];
    }

    public function renderTemplate(string $template, CampaignSponsor $sponsor, array $context): string
    {
        return strtr($template, $this->variables($sponsor, $context));
    }

    /**
     * Prévia com dados reais de um patrocinador em atraso da campanha, para o
     * usuário conferir o texto antes de disparar para a lista inteira.
     *
     * @return array{sponsor:CampaignSponsor,message:string,template:string}|null
     */
    public function preview(Campaign $campaign, CampaignReminderSetting $settings, ?string $template = null): ?array
    {
        $sponsor = $campaign->sponsors()
            ->situacao('em_atraso')
            ->with('installments', 'campaign')
            ->orderBy('name')
            ->first();

        if (! $sponsor) {
            return null;
        }

        $context = $this->overdueContext($sponsor);
        if (! $context) {
            return null;
        }

        [$used, $default] = $settings->templateForDays($context['days']);

        return [
            'sponsor' => $sponsor,
            'template' => $used,
            'message' => $this->renderTemplate($template ?? $default, $sponsor, $context),
        ];
    }

    // ---------------------------------------------------------------- envio

    /**
     * Envia o lembrete de um patrocinador e registra o resultado no histórico.
     * Nunca lança exceção: uma falha de WhatsApp não pode derrubar o lote.
     *
     * @return array{status:string,reason:?string}
     */
    public function send(
        CampaignSponsor $sponsor,
        CampaignReminderSetting $settings,
        string $type = CampaignReminderLog::TYPE_ATRASO,
        string $trigger = CampaignReminderLog::TRIGGER_AUTOMATICO,
        ?int $userId = null,
        bool $manual = false
    ): array {
        $sponsor->loadMissing('campaign');

        $check = $this->eligibility($sponsor, $settings, $type, $manual);

        if (! $check['eligible']) {
            $this->log($sponsor, $type, CampaignReminderLog::STATUS_PULADO, $trigger, $userId, [
                'reason' => $check['reason'],
            ]);

            return ['status' => CampaignReminderLog::STATUS_PULADO, 'reason' => $check['reason']];
        }

        $context = $check['context'];
        $isCourtesy = $type === CampaignReminderLog::TYPE_CORTESIA;

        if ($isCourtesy) {
            $templateUsed = 'cortesia';
            $template = $settings->courtesy_template ?: CampaignReminderSetting::defaultCourtesyTemplate();
        } else {
            [$templateUsed, $template] = $settings->templateForDays($context['days']);
        }

        $message = $this->renderTemplate($template, $sponsor, $context);

        // O carnê completo ajuda no primeiro contato; repetido a cada lembrete
        // só consome dados do destinatário.
        $firstOfCycle = $this->remindersSentFor($sponsor, $context['oldest']->id) === 0;
        $attachPdf = ! $isCourtesy
            && $settings->attach_pdf
            && (! $settings->attach_pdf_first_only || $firstOfCycle);

        $result = $this->dispatchMessage($sponsor, $message, $attachPdf);

        $status = ($result['success'] ?? false)
            ? CampaignReminderLog::STATUS_ENVIADO
            : CampaignReminderLog::STATUS_FALHOU;

        $this->log($sponsor, $type, $status, $trigger, $userId, [
            'installment_id' => $context['oldest']->id,
            'template_used' => $templateUsed,
            'message' => $message,
            'overdue_count' => $context['count'],
            'overdue_amount' => $context['amount'],
            'days_overdue' => $context['days'],
            'pdf_attached' => $attachPdf && ($result['pdf_sent'] ?? false),
            'reason' => $status === CampaignReminderLog::STATUS_FALHOU ? ($result['error'] ?? 'Falha no envio.') : null,
            'sent_at' => $status === CampaignReminderLog::STATUS_ENVIADO ? now() : null,
        ]);

        return ['status' => $status, 'reason' => $result['error'] ?? null];
    }

    /** Texto e, quando aplicável, o carnê em PDF — na mesma conversa. */
    private function dispatchMessage(CampaignSponsor $sponsor, string $message, bool $attachPdf): array
    {
        try {
            if ($attachPdf) {
                $path = $this->gerarCarnePdf($sponsor);
                if ($path) {
                    $result = $this->whatsapp->enviarDocumentoArquivo(
                        $sponsor->phone,
                        $path,
                        'carne-' . \Illuminate\Support\Str::slug($sponsor->name) . '.pdf',
                        $message
                    );
                    @unlink($path);

                    if ($result['success'] ?? false) {
                        return $result + ['pdf_sent' => true];
                    }
                }
            }

            return $this->whatsapp->enviarMensagem($sponsor->phone, $message) + ['pdf_sent' => false];
        } catch (\Throwable $e) {
            Log::error('Lembrete de campanha: erro inesperado no envio.', [
                'sponsor_id' => $sponsor->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage(), 'pdf_sent' => false];
        }
    }

    private function gerarCarnePdf(CampaignSponsor $sponsor): ?string
    {
        try {
            $sponsor->loadMissing(['campaign.department', 'installments']);

            $pdf = Pdf::loadView('financial.campaigns.pdf.carne', [
                'campaign' => $sponsor->campaign,
                'groups' => [['sponsor' => $sponsor, 'installments' => $sponsor->installments]],
            ])->setPaper('a4');

            $dir = storage_path('app/temp/campaign-reminders');
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $path = $dir . '/carne-' . $sponsor->id . '-' . time() . '.pdf';
            file_put_contents($path, $pdf->output());

            return $path;
        } catch (\Throwable $e) {
            Log::error('Lembrete de campanha: erro ao gerar o carnê em PDF.', [
                'sponsor_id' => $sponsor->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function log(
        CampaignSponsor $sponsor,
        string $type,
        string $status,
        string $trigger,
        ?int $userId,
        array $extra = []
    ): CampaignReminderLog {
        return CampaignReminderLog::create([
            'campaign_id' => $sponsor->campaign_id,
            'campaign_sponsor_id' => $sponsor->id,
            'campaign_installment_id' => $extra['installment_id'] ?? null,
            'type' => $type,
            'template_used' => $extra['template_used'] ?? null,
            'status' => $status,
            'trigger' => $trigger,
            'user_id' => $userId,
            'reason' => $extra['reason'] ?? null,
            'message' => $extra['message'] ?? null,
            'overdue_count' => $extra['overdue_count'] ?? 0,
            'overdue_amount' => $extra['overdue_amount'] ?? 0,
            'days_overdue' => $extra['days_overdue'] ?? 0,
            'pdf_attached' => $extra['pdf_attached'] ?? false,
            'sent_at' => $extra['sent_at'] ?? null,
        ]);
    }

    // ------------------------------------------------------------------ lote

    /**
     * Patrocinadores que receberiam lembrete agora, para a prévia do lote e
     * para a execução do comando agendado.
     */
    public function batchCandidates(Campaign $campaign, CampaignReminderSetting $settings, string $type): Collection
    {
        return $campaign->sponsors()
            ->with(['installments', 'campaign'])
            ->orderBy('name')
            ->get()
            ->filter(fn (CampaignSponsor $s) => $this->eligibility($s, $settings, $type)['eligible'])
            ->values();
    }

    public function sentToday(): int
    {
        return CampaignReminderLog::query()
            ->where('status', CampaignReminderLog::STATUS_ENVIADO)
            ->whereDate('sent_at', now()->toDateString())
            ->count();
    }

    /**
     * Executa a fila de uma campanha com as proteções da API não-oficial:
     * conexão verificada antes de começar, teto diário, intervalo aleatório
     * entre mensagens e parada automática após falhas seguidas.
     *
     * @return array{sent:int,failed:int,skipped:int,aborted:?string}
     */
    public function runBatch(
        Campaign $campaign,
        CampaignReminderSetting $settings,
        string $type = CampaignReminderLog::TYPE_ATRASO,
        ?callable $onProgress = null,
        bool $dryRun = false
    ): array {
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'aborted' => null];

        $candidates = $this->batchCandidates($campaign, $settings, $type);
        if ($candidates->isEmpty()) {
            return $result;
        }

        // Não faz sentido enfileirar dezenas de mensagens contra uma instância fora do ar.
        $connection = $this->whatsapp->checkConnectionStatus();
        if (! ($connection['connected'] ?? false)) {
            $result['aborted'] = 'WhatsApp desconectado (instância: ' . ($connection['instance_name'] ?: 'padrão') . ').';
            $this->alertAdmin($campaign, $result['aborted'], $result);

            return $result;
        }

        $remainingToday = max(0, $settings->daily_limit - $this->sentToday());
        if ($remainingToday === 0) {
            $result['aborted'] = "Teto diário de {$settings->daily_limit} mensagens já atingido.";

            return $result;
        }

        $consecutiveFailures = 0;
        $first = true;

        foreach ($candidates as $sponsor) {
            if ($result['sent'] >= $remainingToday) {
                $result['aborted'] = "Teto diário de {$settings->daily_limit} mensagens atingido; o restante segue amanhã.";
                break;
            }

            if (! $first && ! $dryRun) {
                // Cadência perfeitamente regular é padrão de robô: o intervalo varia.
                sleep(random_int(
                    CampaignReminderSetting::MIN_INTERVAL_SECONDS,
                    CampaignReminderSetting::MAX_INTERVAL_SECONDS
                ));
            }
            $first = false;

            // O lote leva minutos; alguém pode ter pago nesse meio-tempo. O
            // send() reavalia a elegibilidade e registra o motivo de pular,
            // então ninguém recebe cobrança depois de já ter quitado.
            $sponsor->load('installments');

            if ($dryRun) {
                $result['sent']++;
                $onProgress && $onProgress($sponsor, ['status' => 'simulado', 'reason' => null]);
                continue;
            }

            $sent = $this->send($sponsor, $settings, $type);
            $onProgress && $onProgress($sponsor, $sent);

            if ($sent['status'] === CampaignReminderLog::STATUS_ENVIADO) {
                $result['sent']++;
                $consecutiveFailures = 0;
                continue;
            }

            if ($sent['status'] === CampaignReminderLog::STATUS_PULADO) {
                $result['skipped']++;
                continue;
            }

            $result['failed']++;
            $consecutiveFailures++;

            if ($consecutiveFailures >= self::CONSECUTIVE_FAILURE_LIMIT) {
                $result['aborted'] = self::CONSECUTIVE_FAILURE_LIMIT . ' envios seguidos falharam; o lote foi interrompido.';
                $this->alertAdmin($campaign, $result['aborted'], $result);
                break;
            }
        }

        return $result;
    }

    private function alertAdmin(Campaign $campaign, string $reason, array $result): void
    {
        $recipients = (array) config('whatsapp.alert_emails', []);
        if ($recipients === []) {
            Log::warning('Lembretes de campanha: WHATSAPP_ALERT_EMAILS não configurado; alerta não enviado.', [
                'campaign_id' => $campaign->id,
                'reason' => $reason,
            ]);

            return;
        }

        try {
            Mail::to($recipients)->send(new CampaignReminderBatchAlert(
                $campaign->name,
                $reason,
                (int) $result['sent'],
                (int) $result['failed'],
                now(),
            ));
        } catch (\Throwable $e) {
            Log::critical('Lembretes de campanha: falha ao enviar alerta por e-mail.', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
