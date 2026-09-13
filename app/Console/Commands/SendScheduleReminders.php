<?php

namespace App\Console\Commands;

use App\Models\MonthlyCultoSchedule;
use App\Models\ScheduleNotificationSetting;
use App\Models\ScheduleReminderLog;
use App\Models\ServiceArea;
use App\Services\NotificacaoService;
use App\Services\ScheduleGroupNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SendScheduleReminders extends Command
{
    protected $signature = 'escalas:send-reminders {--force : Ignora dia/horário configurados}';

    protected $description = 'Envia lembretes automáticos de escala (início do mês, da semana e no dia)';

    public function handle(NotificacaoService $notificacaoService, ScheduleGroupNotificationService $groupNotifier): int
    {
        if (! Schema::hasTable('schedule_notification_settings') || ! Schema::hasTable('schedule_reminder_logs')) {
            $this->warn('Tabelas de configuração de escalas ainda não existem.');

            return self::SUCCESS;
        }

        $settings = ScheduleNotificationSetting::current();
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        if ($this->shouldSendMonth($settings)) {
            [$ok, $skip, $err] = $this->sendMonthReminders($settings, $notificacaoService);
            $sent += $ok;
            $skipped += $skip;
            $failed += $err;
        }

        if ($this->shouldSendWeekPeople($settings)) {
            [$ok, $skip, $err] = $this->sendWeekReminders($settings, $notificacaoService);
            $sent += $ok;
            $skipped += $skip;
            $failed += $err;
        }

        if ($this->shouldSendMondayGroups($settings)) {
            $groupResult = $groupNotifier->notifyWeekGroups();
            $sent += (int) ($groupResult['sent'] ?? 0);
            $skipped += (int) ($groupResult['skipped'] ?? 0);
            $failed += (int) ($groupResult['failed'] ?? 0);
            $this->info('PDF semanal nos grupos: enviados '.($groupResult['sent'] ?? 0).', já enviados '.($groupResult['skipped'] ?? 0).', falhas '.($groupResult['failed'] ?? 0).'.');
        }

        if ($this->shouldSendDay($settings)) {
            [$ok, $skip, $err] = $this->sendDayReminders($settings, $notificacaoService);
            $sent += $ok;
            $skipped += $skip;
            $failed += $err;
        }

        $this->info("Lembretes de escala: enviados {$sent}, já enviados {$skipped}, falhas {$failed}.");

        return self::SUCCESS;
    }

    private function shouldSendMonth(ScheduleNotificationSetting $settings): bool
    {
        if (! $settings->month_enabled) {
            return false;
        }

        if ($this->option('force')) {
            return true;
        }

        return now()->day === (int) $settings->month_day && $settings->reachedSendTime('month_time');
    }

    private function shouldSendWeek(ScheduleNotificationSetting $settings): bool
    {
        if (! $settings->week_enabled) {
            return false;
        }

        if ($this->option('force')) {
            return true;
        }

        return now()->dayOfWeekIso === (int) $settings->week_weekday && $settings->reachedSendTime('week_time');
    }

    private function shouldSendWeekPeople(ScheduleNotificationSetting $settings): bool
    {
        return $this->shouldSendWeek($settings) || $this->shouldSendMondayGroups($settings);
    }

    private function shouldSendMondayGroups(ScheduleNotificationSetting $settings): bool
    {
        if ($this->option('force')) {
            return true;
        }

        return now()->dayOfWeekIso === Carbon::MONDAY && $settings->reachedSendTime('week_time');
    }

    private function shouldSendDay(ScheduleNotificationSetting $settings): bool
    {
        if (! $settings->day_enabled) {
            return false;
        }

        if ($this->option('force')) {
            return true;
        }

        return $settings->reachedSendTime('day_time');
    }

    private function sendMonthReminders(ScheduleNotificationSetting $settings, NotificacaoService $notificacaoService): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $periodKey = now()->format('Y-m');
        $mes = now()->locale('pt_BR')->translatedFormat('F/Y');
        $template = $settings->month_template ?: '';

        return $this->sendGroupedReminders(
            ScheduleReminderLog::TYPE_MONTH,
            $periodKey,
            $this->assignmentsBetween($start, $end),
            $template,
            $notificacaoService,
            ['{mes}' => $mes]
        );
    }

    private function sendWeekReminders(ScheduleNotificationSetting $settings, NotificacaoService $notificacaoService): array
    {
        $start = now()->startOfWeek(Carbon::MONDAY);
        $end = now()->endOfWeek(Carbon::SUNDAY);
        $periodKey = now()->isoFormat('GGGG-[W]WW');
        $template = $settings->week_template
            ?: "Olá, {nome}! 🙏\n\nNesta semana você está escalado(a) em:\n{escalas}\n\nDeus abençoe!";

        return $this->sendGroupedReminders(
            ScheduleReminderLog::TYPE_WEEK,
            $periodKey,
            $this->assignmentsBetween($start, $end),
            $template,
            $notificacaoService,
            ['{mes}' => now()->locale('pt_BR')->translatedFormat('F/Y')]
        );
    }

    private function sendDayReminders(ScheduleNotificationSetting $settings, NotificacaoService $notificacaoService): array
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();
        $periodKey = now()->toDateString();
        $template = $settings->day_template ?: '';

        return $this->sendGroupedReminders(
            ScheduleReminderLog::TYPE_DAY,
            $periodKey,
            $this->assignmentsBetween($start, $end),
            $template,
            $notificacaoService,
            ['{mes}' => now()->locale('pt_BR')->translatedFormat('F/Y')]
        );
    }

    /**
     * @return Collection<int, Collection<int, object>>
     */
    private function assignmentsBetween(Carbon $start, Carbon $end): Collection
    {
        $schedules = MonthlyCultoSchedule::query()
            ->with(['event', 'serviceAreaVolunteers.member'])
            ->whereIn('status', ['rascunho', 'publicada'])
            ->whereHas('event', function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end]);
            })
            ->get();

        $areas = ServiceArea::query()->with(['leader', 'parent.leader'])->get()->keyBy('id');
        $grouped = collect();

        foreach ($schedules as $schedule) {
            $event = $schedule->event;
            if (! $event?->start_date) {
                continue;
            }

            foreach ($schedule->serviceAreaVolunteers as $volunteer) {
                $member = $volunteer->member;
                $pivotStatus = $volunteer->pivot->status ?? null;
                if (in_array($pivotStatus, ['cancelado', 'substituido'], true)) {
                    continue;
                }
                if (! $member || empty($member->phone)) {
                    continue;
                }

                $area = $areas->get((int) $volunteer->pivot->service_area_id);
                $grouped->push((object) [
                    'volunteer_id' => (int) $volunteer->id,
                    'member' => $member,
                    'culto' => $event->title ?? '',
                    'dia_culto' => $event->start_date->format('d/m/Y'),
                    'hora_culto' => $event->start_date->format('H:i'),
                    'area_servico' => $area?->displayName() ?? '',
                    'responsavel_area' => $area?->resolvedLeaderName() ?: 'liderança da área',
                    'sort' => $event->start_date->timestamp,
                ]);
            }
        }

        return $grouped->groupBy('volunteer_id');
    }

    /**
     * @param  Collection<int, Collection<int, object>>  $grouped
     * @return array{0:int,1:int,2:int}
     */
    private function sendGroupedReminders(
        string $type,
        string $periodKey,
        Collection $grouped,
        string $template,
        NotificacaoService $notificacaoService,
        array $extraVariables
    ): array {
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        if (trim($template) === '') {
            $this->warn("Template vazio para o tipo {$type}; nenhum lembrete foi enviado.");

            return [$sent, $skipped, $failed];
        }

        foreach ($grouped as $volunteerId => $items) {
            $volunteerId = (int) $volunteerId;
            if (ScheduleReminderLog::alreadySent($type, $periodKey, $volunteerId)) {
                $skipped++;
                continue;
            }

            $first = $items->sortBy('sort')->first();
            $escalas = $items->sortBy('sort')
                ->map(fn ($item) => "• {$item->dia_culto} {$item->hora_culto} — {$item->culto} ({$item->area_servico})")
                ->unique()
                ->implode("\n");

            $message = \App\Models\ConfiguracaoMensagem::aplicarVariaveis($template, array_merge($extraVariables, [
                '{nome}' => $first->member->name,
                '{culto}' => $first->culto,
                '{dia_culto}' => $first->dia_culto,
                '{hora_culto}' => $first->hora_culto,
                '{area_servico}' => $first->area_servico,
                '{responsavel_area}' => $first->responsavel_area,
                '{escalas}' => $escalas,
            ]));

            try {
                $result = $notificacaoService->enviarParaMembro($first->member, $message);
                if ($result['success'] ?? false) {
                    ScheduleReminderLog::markSent($type, $periodKey, $volunteerId);
                    $sent++;
                } else {
                    $failed++;
                    Log::warning('Escala: falha ao enviar lembrete automático.', [
                        'type' => $type,
                        'volunteer_id' => $volunteerId,
                        'error' => $result['error'] ?? 'desconhecido',
                    ]);
                }
            } catch (\Throwable $exception) {
                $failed++;
                Log::error('Escala: erro inesperado no lembrete automático.', [
                    'type' => $type,
                    'volunteer_id' => $volunteerId,
                    'error' => $exception->getMessage(),
                ]);
            }

            usleep(1500000);
        }

        return [$sent, $skipped, $failed];
    }
}
