<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\ServiceReport;
use App\Models\ServiceReportAttendance;
use App\Models\ServiceReportSetting;
use App\Models\ServiceReportVisitor;
use App\Services\NotificacaoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPastoralAttendanceAlerts extends Command
{
    protected $signature = 'cultos:check-pastoral-alerts';

    protected $description = 'Dispara alertas pastorais de ausências consecutivas e visitantes sem retorno';

    public function handle(NotificacaoService $notificacaoService): int
    {
        $settings = ServiceReportSetting::current();
        $absenceThreshold = (int) ($settings->consecutive_absences_alert_threshold ?? 0);
        $visitorDays = (int) ($settings->visitor_no_return_days_alert ?? 0);

        $pastors = $this->buscarResponsaveisPastorais();
        if ($pastors->isEmpty()) {
            $this->warn('Nenhum responsável pastoral com telefone encontrado.');
            return Command::SUCCESS;
        }

        $sent = 0;

        if ($absenceThreshold > 0) {
            $sent += $this->alertAbsences($absenceThreshold, $pastors, $notificacaoService);
        }

        if ($visitorDays > 0) {
            $sent += $this->alertVisitors($visitorDays, $pastors, $notificacaoService);
        }

        $this->info("Alertas processados. Notificações enviadas: {$sent}");

        return Command::SUCCESS;
    }

    private function alertAbsences(int $threshold, $pastors, NotificacaoService $notificacaoService): int
    {
        $reportIds = ServiceReport::query()
            ->where('status', ServiceReport::STATUS_FINAL)
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->limit($threshold)
            ->pluck('id');

        if ($reportIds->count() < $threshold) {
            return 0;
        }

        $memberIds = ServiceReportAttendance::query()
            ->whereIn('service_report_id', $reportIds)
            ->where('present', false)
            ->select('member_id')
            ->groupBy('member_id')
            ->havingRaw('COUNT(DISTINCT service_report_id) = ?', [$threshold])
            ->pluck('member_id');

        // Garantir ausência em TODOS os últimos N (não só N ausências em qualquer relatório)
        $memberIds = $memberIds->filter(function ($memberId) use ($reportIds) {
            $absentCount = ServiceReportAttendance::query()
                ->whereIn('service_report_id', $reportIds)
                ->where('member_id', $memberId)
                ->where('present', false)
                ->count();

            $presentCount = ServiceReportAttendance::query()
                ->whereIn('service_report_id', $reportIds)
                ->where('member_id', $memberId)
                ->where('present', true)
                ->count();

            return $absentCount === $reportIds->count() && $presentCount === 0;
        });

        $sent = 0;
        foreach ($memberIds as $memberId) {
            $member = Member::find($memberId);
            if (!$member) {
                continue;
            }

            $mensagem = implode("\n", [
                '⚠️ *Alerta Pastoral — Ausência consecutiva*',
                '',
                "O membro *{$member->name}* esteve ausente nos últimos {$threshold} cultos registrados.",
                '',
                'Considere um contato pastoral de cuidado.',
            ]);

            $resultado = $notificacaoService->enviarParaMembros($pastors, $mensagem);
            if (!empty($resultado['success']) || (is_array($resultado) && ($resultado['enviadas'] ?? 0) > 0)) {
                $sent++;
            } else {
                // enviarParaMembros may return different shape
                try {
                    $notificacaoService->enviarParaMembros($pastors, $mensagem);
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('Falha alerta ausência', ['member_id' => $memberId, 'error' => $e->getMessage()]);
                }
            }
        }

        return $sent;
    }

    private function alertVisitors(int $days, $pastors, NotificacaoService $notificacaoService): int
    {
        $cutoff = now()->subDays($days);
        $visitors = ServiceReportVisitor::query()
            ->where('created_at', '<=', $cutoff)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        $sent = 0;
        foreach ($visitors as $visitor) {
            $returned = ServiceReportVisitor::query()
                ->where('id', '!=', $visitor->id)
                ->where(function ($q) use ($visitor) {
                    $q->where('phone', $visitor->phone);
                    if ($visitor->converted_to_member_id) {
                        $q->orWhere('converted_to_member_id', $visitor->converted_to_member_id);
                    }
                })
                ->where('created_at', '>', $visitor->created_at)
                ->exists();

            if ($returned) {
                continue;
            }

            $mensagem = implode("\n", [
                '👋 *Alerta Pastoral — Visitante sem retorno*',
                '',
                "O visitante *{$visitor->name}* (" . ($visitor->phone ?: 'sem telefone') . ") não retornou há {$days} dias ou mais.",
                '',
                'Considere um contato de acolhimento.',
            ]);

            try {
                $notificacaoService->enviarParaMembros($pastors, $mensagem);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Falha alerta visitante', ['visitor_id' => $visitor->id, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    private function buscarResponsaveisPastorais()
    {
        return Member::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where('status', 'ativo')
            ->whereHas('role', function ($q) {
                $q->where('is_active', true)
                    ->where(function ($qq) {
                        $qq->where('name', 'like', '%Pastor%')
                            ->orWhere('name', 'like', '%Discipul%')
                            ->orWhere('name', 'like', '%Líder%')
                            ->orWhere('name', 'like', '%Lider%');
                    });
            })
            ->get();
    }
}
