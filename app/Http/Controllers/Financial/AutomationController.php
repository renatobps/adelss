<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialAutomation;
use App\Models\FinancialCategory;
use App\Models\FinancialNotificationLog;
use App\Models\Member;
use App\Services\FinancialNotificationService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class AutomationController extends Controller
{
    public function __construct(
        private WhatsAppService $whatsAppService,
        private FinancialNotificationService $financialNotificationService
    ) {}

    public function index()
    {
        $this->authorize('financial.view-automations');

        $automation = FinancialAutomation::contributionThanks();
        $settings = $automation->mergedSettings();

        $treasurersAutomation = FinancialAutomation::treasurers();
        $treasurerRecipients = $treasurersAutomation->treasurerRecipients();
        if (count($treasurerRecipients) === 0) {
            $treasurerRecipients = [['name' => '', 'phone' => '', 'member_id' => null]];
        }

        $dueReminderAutomation = FinancialAutomation::dueReminder();
        $dueReminderSettings = $dueReminderAutomation->mergedSettings();

        $smartSummaryAutomation = FinancialAutomation::smartSummary();
        $smartSummarySettings = $smartSummaryAutomation->mergedSettings();

        $logQuery = FinancialNotificationLog::query()
            ->where('notification_type', FinancialNotificationLog::TYPE_RECEIPT_MEMBER);

        $stats = [
            'sent_today' => (clone $logQuery)->where('status', 'sent')->whereDate('created_at', today())->count(),
            'sent_7d' => (clone $logQuery)->where('status', 'sent')->where('created_at', '>=', now()->subDays(7))->count(),
            'queued' => 0,
            'failed' => (clone $logQuery)->where('status', 'failed')->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        $recentLogs = FinancialNotificationLog::query()
            ->where('notification_type', FinancialNotificationLog::TYPE_RECEIPT_MEMBER)
            ->with('member:id,name')
            ->latest()
            ->limit(10)
            ->get();

        $categories = FinancialCategory::receitas()->orderBy('name')->get(['id', 'name']);
        $maxTreasurers = FinancialAutomation::MAX_TREASURERS;

        $hourOptions = [];
        for ($h = 0; $h < 24; $h++) {
            $label = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
            $hourOptions[$label] = $label;
        }

        return view('financial.automations.index', compact(
            'automation',
            'settings',
            'stats',
            'recentLogs',
            'categories',
            'treasurersAutomation',
            'treasurerRecipients',
            'maxTreasurers',
            'dueReminderAutomation',
            'dueReminderSettings',
            'smartSummaryAutomation',
            'smartSummarySettings',
            'hourOptions'
        ));
    }

    public function toggle(Request $request, FinancialAutomation $automation)
    {
        $this->authorize('financial.manage-automations');

        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $automation->update([
            'enabled' => $validated['enabled'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'enabled' => $automation->enabled,
                'message' => $automation->enabled
                    ? 'Automação habilitada.'
                    : 'Automação desabilitada.',
            ]);
        }

        return back()->with('success', $automation->enabled
            ? 'Automação habilitada.'
            : 'Automação desabilitada.');
    }

    public function update(Request $request, FinancialAutomation $automation)
    {
        $this->authorize('financial.manage-automations');

        if ($automation->key === FinancialAutomation::KEY_CONTRIBUTION_THANKS) {
            return $this->updateContributionThanks($request, $automation);
        }

        if ($automation->key === FinancialAutomation::KEY_TREASURERS) {
            return $this->updateTreasurers($request, $automation);
        }

        if ($automation->key === FinancialAutomation::KEY_DUE_REMINDER) {
            return $this->updateDueReminder($request, $automation);
        }

        if ($automation->key === FinancialAutomation::KEY_SMART_SUMMARY) {
            return $this->updateSmartSummary($request, $automation);
        }

        abort(404);
    }

    public function searchMembers(Request $request)
    {
        $this->authorize('financial.view-automations');

        $q = trim((string) $request->query('q', ''));

        $query = Member::query()->orderBy('name');

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', '%' . $q . '%')
                    ->orWhere('phone', 'like', '%' . $q . '%');
            });
        }

        $members = $query
            ->limit($q === '' ? 50 : 20)
            ->get(['id', 'name', 'phone']);

        return response()->json([
            'data' => $members->map(fn (Member $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'phone' => $member->phone,
            ])->values(),
        ]);
    }

    public function sendTreasurerTest(Request $request, FinancialAutomation $automation)
    {
        $this->authorize('financial.manage-automations');

        if ($automation->key !== FinancialAutomation::KEY_TREASURERS) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:120',
            'phone' => 'required|string|max:30',
        ]);

        $phone = WhatsAppService::normalizarNumero($validated['phone']);
        if (strlen($phone) < 12) {
            return response()->json([
                'success' => false,
                'message' => 'Informe um WhatsApp válido com DDD.',
            ], 422);
        }

        $nome = trim((string) ($validated['name'] ?? '')) ?: 'Tesoureiro';
        $mensagem = implode("\n", [
            '✅ *ADEL São Sebastião*',
            '',
            "Olá, *{$nome}*!",
            '',
            'Este é um teste de notificação financeira.',
            'Se você recebeu esta mensagem, o WhatsApp está configurado corretamente.',
        ]);

        $resultado = $this->whatsAppService->enviarMensagem($validated['phone'], $mensagem);

        return response()->json([
            'success' => (bool) ($resultado['success'] ?? false),
            'message' => ($resultado['success'] ?? false)
                ? 'Mensagem de teste enviada.'
                : ($resultado['error'] ?? 'Falha ao enviar mensagem de teste.'),
        ], ($resultado['success'] ?? false) ? 200 : 422);
    }

    public function sendSmartSummaryNow(Request $request, FinancialAutomation $automation)
    {
        $this->authorize('financial.manage-automations');

        if ($automation->key !== FinancialAutomation::KEY_SMART_SUMMARY) {
            abort(404);
        }

        $resultado = $this->financialNotificationService->enviarResumoFinanceiroInteligente(true);

        $success = (bool) ($resultado['success'] ?? false);
        $enviados = (int) ($resultado['enviados'] ?? 0);

        $message = $success
            ? ($enviados > 0
                ? "Resumo enviado para {$enviados} destinatário(s)."
                : ($resultado['message'] ?? 'Nenhum destinatário recebeu o resumo.'))
            : ($resultado['error'] ?? 'Falha ao enviar o resumo.');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $success && $enviados > 0,
                'enviados' => $enviados,
                'message' => $message,
            ], ($success && $enviados > 0) ? 200 : 422);
        }

        return back()->with(
            ($success && $enviados > 0) ? 'success' : 'error',
            $message
        );
    }

    private function updateContributionThanks(Request $request, FinancialAutomation $automation)
    {
        $validated = $request->validate([
            'min_amount' => 'nullable|numeric|min:0',
            'daily_limit' => 'required|integer|min:1|max:30',
            'window_start' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'window_end' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'delay_minutes' => 'required|integer|in:0,5,15,30,60',
            'message_template' => 'nullable|string|max:2000',
            'eligible_category_ids' => 'nullable|array',
            'eligible_category_ids.*' => 'integer|exists:financial_categories,id',
        ]);

        $settings = array_merge($automation->mergedSettings(), [
            'min_amount' => (float) ($validated['min_amount'] ?? 0),
            'daily_limit' => (int) $validated['daily_limit'],
            'window_start' => $validated['window_start'],
            'window_end' => $validated['window_end'],
            'delay_minutes' => (int) $validated['delay_minutes'],
            'message_template' => trim((string) ($validated['message_template'] ?? '')) !== ''
                ? $validated['message_template']
                : FinancialAutomation::defaultMessageTemplate(),
            'eligible_category_ids' => array_values(array_map('intval', $validated['eligible_category_ids'] ?? [])),
        ]);

        $automation->update(['settings' => $settings]);

        return back()->with('success', 'Configuração salva com sucesso.');
    }

    private function updateTreasurers(Request $request, FinancialAutomation $automation)
    {
        $validated = $request->validate([
            'recipients' => 'nullable|array|max:' . FinancialAutomation::MAX_TREASURERS,
            'recipients.*.name' => 'nullable|string|max:120',
            'recipients.*.phone' => 'nullable|string|max:30',
            'recipients.*.member_id' => 'nullable|integer|exists:members,id',
        ]);

        $recipients = [];
        foreach ($validated['recipients'] ?? [] as $recipient) {
            $phone = trim((string) ($recipient['phone'] ?? ''));
            if ($phone === '') {
                continue;
            }

            $recipients[] = [
                'name' => trim((string) ($recipient['name'] ?? '')),
                'phone' => $phone,
                'member_id' => !empty($recipient['member_id']) ? (int) $recipient['member_id'] : null,
            ];

            if (count($recipients) >= FinancialAutomation::MAX_TREASURERS) {
                break;
            }
        }

        $automation->update([
            'settings' => ['recipients' => $recipients],
        ]);

        return back()->with('success', 'Tesoureiros salvos com sucesso.');
    }

    private function updateDueReminder(Request $request, FinancialAutomation $automation)
    {
        $validated = $request->validate([
            'days_ahead' => 'required|integer|in:0,1,2,3,5,7',
            'send_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'second_reminder' => 'nullable|boolean',
            'min_amount' => 'nullable|numeric|min:0',
            'message_template' => 'nullable|string|max:3000',
        ]);

        $settings = array_merge($automation->mergedSettings(), [
            'days_ahead' => (int) $validated['days_ahead'],
            'send_time' => $validated['send_time'],
            'second_reminder' => $request->boolean('second_reminder'),
            'min_amount' => (float) ($validated['min_amount'] ?? 0),
            'message_template' => trim((string) ($validated['message_template'] ?? '')) !== ''
                ? $validated['message_template']
                : FinancialAutomation::defaultDueReminderMessage(),
        ]);

        $automation->update(['settings' => $settings]);

        return back()->with('success', 'Lembrete de despesas salvo com sucesso.');
    }

    private function updateSmartSummary(Request $request, FinancialAutomation $automation)
    {
        $validated = $request->validate([
            'frequency' => 'required|in:weekly,monthly',
            'day_of_month' => 'required|integer|min:1|max:28',
            'send_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'compare_previous' => 'nullable|boolean',
            'opening_message' => 'nullable|string|max:1000',
        ]);

        $settings = array_merge($automation->mergedSettings(), [
            'frequency' => $validated['frequency'],
            'day_of_month' => (int) $validated['day_of_month'],
            'send_time' => $validated['send_time'],
            'compare_previous' => $request->boolean('compare_previous'),
            'opening_message' => trim((string) ($validated['opening_message'] ?? '')),
        ]);

        $automation->update(['settings' => $settings]);

        return back()->with('success', 'Resumo financeiro salvo com sucesso.');
    }
}
