<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignReminderLog;
use App\Models\CampaignReminderSetting;
use App\Models\CampaignSponsor;
use App\Services\CampaignReminderService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignReminderController extends Controller
{
    public function __construct(
        private readonly CampaignReminderService $reminders,
    ) {}

    /** Padrão do sistema, herdado por toda campanha que não sobrescreve. */
    public function globalEdit()
    {
        return view('financial.campaigns.reminders', [
            'campaign' => null,
            'settings' => CampaignReminderSetting::globalSettings(),
            'preview' => null,
            'nextRun' => null,
        ]);
    }

    public function globalUpdate(Request $request)
    {
        $data = $this->validated($request, global: true);
        $data['use_global'] = false;

        CampaignReminderSetting::globalSettings()->update($data);

        return redirect()
            ->route('financial.campaigns.reminders.global')
            ->with('success', 'Configuração padrão de lembretes salva.');
    }

    public function edit(Campaign $campaign)
    {
        $settings = CampaignReminderSetting::effectiveFor($campaign);

        return view('financial.campaigns.reminders', [
            'campaign' => $campaign,
            'settings' => $settings,
            'preview' => $this->reminders->preview($campaign, $settings),
            'nextRun' => $this->nextRun($campaign, $settings),
        ]);
    }

    public function update(Request $request, Campaign $campaign)
    {
        $useGlobal = $request->boolean('use_global');

        $data = $useGlobal
            // Herdando o global, só a pausa local faz sentido guardar.
            ? ['use_global' => true, 'paused' => $request->boolean('paused')]
            : $this->validated($request) + ['use_global' => false];

        CampaignReminderSetting::updateOrCreate(['campaign_id' => $campaign->id], $data);

        return redirect()
            ->route('financial.campaigns.reminders.edit', $campaign)
            ->with('success', 'Configuração de lembretes salva.');
    }

    /** Prévia ao vivo com dados reais de um patrocinador em atraso. */
    public function preview(Request $request, Campaign $campaign)
    {
        $settings = CampaignReminderSetting::effectiveFor($campaign);
        $preview = $this->reminders->preview($campaign, $settings, $request->input('template'));

        if (! $preview) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum patrocinador em atraso nesta campanha para gerar a prévia.',
            ]);
        }

        return response()->json([
            'success' => true,
            'sponsor' => $preview['sponsor']->name,
            'message' => $preview['message'],
        ]);
    }

    /** Envia a mensagem para um número informado, sem tocar na lista real. */
    public function test(Request $request, Campaign $campaign, WhatsAppService $whatsapp)
    {
        $request->validate(
            ['phone' => 'required|string|max:30', 'template' => 'nullable|string|max:2000'],
            ['phone.required' => 'Informe o número que vai receber o teste.']
        );

        $settings = CampaignReminderSetting::effectiveFor($campaign);
        $preview = $this->reminders->preview($campaign, $settings, $request->input('template'));

        if (! $preview) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum patrocinador em atraso para montar a mensagem de teste.',
            ], 422);
        }

        $result = $whatsapp->enviarMensagem(
            $request->input('phone'),
            "*[TESTE]*\n\n" . $preview['message']
        );

        return response()->json([
            'success' => (bool) ($result['success'] ?? false),
            'message' => ($result['success'] ?? false)
                ? 'Mensagem de teste enviada.'
                : ($result['error'] ?? 'Falha ao enviar a mensagem de teste.'),
        ], ($result['success'] ?? false) ? 200 : 422);
    }

    /** Quem receberia no próximo lote — para revisão antes do disparo. */
    public function batch(Campaign $campaign)
    {
        $settings = CampaignReminderSetting::effectiveFor($campaign);
        $candidates = $this->reminders->batchCandidates($campaign, $settings, CampaignReminderLog::TYPE_ATRASO);

        return view('financial.campaigns.reminder-batch', [
            'campaign' => $campaign,
            'settings' => $settings,
            'candidates' => $candidates,
            'reminders' => $this->reminders,
            'nextRun' => $this->nextRun($campaign, $settings),
        ]);
    }

    /** Envio avulso, fora do ciclo automático. */
    public function sendNow(CampaignSponsor $sponsor)
    {
        $sponsor->load(['campaign', 'installments']);
        $settings = CampaignReminderSetting::effectiveFor($sponsor->campaign);

        $result = $this->reminders->send(
            $sponsor,
            $settings,
            CampaignReminderLog::TYPE_ATRASO,
            CampaignReminderLog::TRIGGER_MANUAL,
            Auth::id(),
            manual: true
        );

        $back = redirect()->route('financial.campaigns.show', $sponsor->campaign_id);

        return match ($result['status']) {
            CampaignReminderLog::STATUS_ENVIADO => $back->with('success', "Lembrete enviado para {$sponsor->name}."),
            CampaignReminderLog::STATUS_PULADO => $back->with('warning', 'Lembrete não enviado: ' . $result['reason']),
            default => $back->with('error', 'Falha ao enviar o lembrete: ' . ($result['reason'] ?? 'erro desconhecido')),
        };
    }

    public function toggleSponsor(CampaignSponsor $sponsor)
    {
        $sponsor->update(['reminders_enabled' => ! $sponsor->reminders_enabled]);

        return redirect()
            ->route('financial.campaigns.show', $sponsor->campaign_id)
            ->with('success', $sponsor->reminders_enabled
                ? "Lembretes reativados para {$sponsor->name}."
                : "{$sponsor->name} não receberá mais lembretes automáticos.");
    }

    /**
     * Próxima execução possível conforme horário e dias configurados.
     * Serve para a tela dizer "Ativo — próximo envio em DD/MM às HH:MM".
     */
    private function nextRun(Campaign $campaign, CampaignReminderSetting $settings): ?\Illuminate\Support\Carbon
    {
        if (! $settings->isActiveFor($campaign)) {
            return null;
        }

        $candidate = now()->setTime($settings->sendHour(), $settings->sendMinute(), 0);
        if ($candidate->lte(now())) {
            $candidate->addDay();
        }

        for ($i = 0; $i < 7; $i++) {
            if (in_array((int) $candidate->isoWeekday(), $settings->weekdays(), true)) {
                return $candidate;
            }
            $candidate->addDay();
        }

        return null;
    }

    private function validated(Request $request, bool $global = false): array
    {
        $quietStart = CampaignReminderSetting::QUIET_HOUR_START;
        $quietEnd = CampaignReminderSetting::QUIET_HOUR_END;

        $data = $request->validate(
            [
                'days_between' => 'required|integer|min:1|max:90',
                'max_reminders' => 'required|integer|min:1|max:10',
                'send_time' => "required|date_format:H:i|after_or_equal:{$quietStart}:00|before:{$quietEnd}:00",
                'send_days' => 'required|array|min:1',
                'send_days.*' => 'integer|min:1|max:7',
                'daily_limit' => 'required|integer|min:1|max:500',
                'template_1' => 'required|string|max:2000',
                'template_2' => 'nullable|string|max:2000',
                'template_3' => 'nullable|string|max:2000',
                'tier_2_days' => 'required|integer|min:1|max:365',
                'tier_3_days' => 'required|integer|min:1|max:365|gt:tier_2_days',
                'courtesy_days_before' => 'required|integer|min:1|max:30',
                'courtesy_template' => 'nullable|string|max:2000',
            ],
            [
                'send_time.after_or_equal' => "O horário de envio não pode ser antes das {$quietStart}h.",
                'send_time.before' => "O horário de envio não pode ser depois das {$quietEnd}h.",
                'send_days.required' => 'Selecione ao menos um dia da semana para o envio.',
                'template_1.required' => 'O texto do primeiro lembrete é obrigatório.',
                'tier_3_days.gt' => 'A faixa do terceiro texto deve ser maior que a do segundo.',
            ]
        );

        $data['enabled'] = $request->boolean('enabled');
        $data['attach_pdf'] = $request->boolean('attach_pdf');
        $data['attach_pdf_first_only'] = $request->boolean('attach_pdf_first_only');
        $data['courtesy_enabled'] = $request->boolean('courtesy_enabled');
        $data['paused'] = $global ? false : $request->boolean('paused');

        return $data;
    }
}
