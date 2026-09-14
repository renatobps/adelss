<?php

namespace App\Services;

use App\Models\ConfiguracaoMensagem;
use App\Models\MonthlyCultoSchedule;
use App\Models\ScheduleNotificationSetting;
use App\Models\ScheduleReminderLog;
use App\Models\ServiceArea;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ScheduleGroupNotificationService
{
    public function __construct(private WhatsAppService $whatsapp)
    {
    }

    /**
     * @return array{sent:int, failed:int, skipped:int}
     */
    public function notifyPublishedSchedule(MonthlyCultoSchedule $escala): array
    {
        $escala->loadMissing(['event', 'serviceAreaVolunteers.member']);
        $groups = $this->groupsForSchedules(collect([$escala]));

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($groups as $group) {
            $schedules = $group->is_preletor_only
                ? $this->publishedSchedulesForMonth((int) $escala->month, (int) $escala->year)
                : collect([$escala]);

            $caption = $group->is_preletor_only
                ? 'Preletores de '.$this->monthLabel((int) $escala->month, (int) $escala->year)
                : $this->groupCaption($escala, '', $group);

            $result = $this->sendPdfToGroup($group, $schedules, $caption, $group->is_preletor_only ? 'Preletores do mês' : 'Escala do culto');
            if ($result === 'sent') {
                $sent++;
            } elseif ($result === 'failed') {
                $failed++;
            } else {
                $skipped++;
            }
        }

        return compact('sent', 'failed', 'skipped');
    }

    /**
     * Envia mensagem, mídia e/ou PDF filtrado aos grupos das áreas desta escala.
     *
     * @return array{sent:int, failed:int, skipped:int, groups:int}
     */
    public function notifyManualToGroups(
        MonthlyCultoSchedule $escala,
        string $message = '',
        bool $sendPdf = true,
        ?UploadedFile $media = null
    ): array {
        $escala->loadMissing(['event', 'serviceAreaVolunteers.member']);
        $groups = $this->groupsForSchedules(collect([$escala]));
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        if ($groups->isEmpty()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 1, 'groups' => 0];
        }

        foreach ($groups as $group) {
            $ok = false;
            $hadError = false;
            $caption = $this->groupCaption($escala, $message, $group);

            if ($media) {
                $mediaMeta = $this->detectMediaType($media);
                $mediaResult = $this->whatsapp->enviarMidiaArquivo(
                    $group->jid,
                    $media,
                    $mediaMeta['tipo'],
                    $mediaMeta['is_pdf'],
                    $media->getClientOriginalName(),
                    $caption
                );
                if ($mediaResult['success'] ?? false) {
                    $ok = true;
                } else {
                    $hadError = true;
                }
                usleep(800000);
            }

            if ($sendPdf) {
                $pdfResult = $this->sendPdfToGroup(
                    $group,
                    collect([$escala]),
                    $caption,
                    'Escala do culto'
                );
                if ($pdfResult === 'sent') {
                    $ok = true;
                } elseif ($pdfResult === 'failed') {
                    $hadError = true;
                }
            } elseif (! $media) {
                $textResult = $this->whatsapp->enviarMensagem($group->jid, $caption);
                if ($textResult['success'] ?? false) {
                    $ok = true;
                } else {
                    $hadError = true;
                }
            }

            if ($ok) {
                $sent++;
            } elseif ($hadError) {
                $failed++;
            } else {
                $skipped++;
            }

            usleep(1500000);
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped, 'groups' => $groups->count()];
    }

    /**
     * @return array{sent:int, failed:int, skipped:int}
     */
    public function notifyWeekGroups(?Carbon $now = null): array
    {
        $now = $now ?: now();
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $now->copy()->endOfWeek(Carbon::SUNDAY);
        $weekSchedules = $this->publishedSchedulesBetween($weekStart, $weekEnd);
        $periodKeyBase = $now->isoFormat('GGGG-[W]WW');

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($this->configuredGroups() as $group) {
            $logKey = $periodKeyBase.'|'.$group->jid;
            if (ScheduleReminderLog::alreadySent(ScheduleReminderLog::TYPE_WEEK_GROUP, $logKey, 0)) {
                $skipped++;
                continue;
            }

            $schedules = $group->is_preletor_only
                ? $this->publishedSchedulesForMonth((int) $now->month, (int) $now->year)
                : $weekSchedules;

            $caption = $group->is_preletor_only
                ? 'Preletores de '.$this->monthLabel((int) $now->month, (int) $now->year)
                : 'Escala da semana '.$weekStart->format('d/m').' a '.$weekEnd->format('d/m');

            $title = $group->is_preletor_only ? 'Preletores do mês' : 'Escala da semana';
            $result = $this->sendPdfToGroup($group, $schedules, $caption, $title);

            if ($result === 'sent') {
                ScheduleReminderLog::markSent(ScheduleReminderLog::TYPE_WEEK_GROUP, $logKey, 0);
                $sent++;
            } elseif ($result === 'failed') {
                $failed++;
            } else {
                $skipped++;
            }

            usleep(1500000);
        }

        return compact('sent', 'failed', 'skipped');
    }

    /**
     * @param  Collection<int, MonthlyCultoSchedule>  $schedules
     */
    private function sendPdfToGroup(object $group, Collection $schedules, string $caption, string $documentTitle): string
    {
        $pdfBinary = $this->buildGroupPdf($schedules, $group->area_ids, $documentTitle);
        if ($pdfBinary === null) {
            return 'skipped';
        }

        $tempDirectory = storage_path('app/tmp');
        if (! is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0775, true);
        }

        $fileName = 'escala-'.Str::slug($group->name ?: 'grupo').'-'.now()->format('Ymd-His').'.pdf';
        $tempPath = $tempDirectory.DIRECTORY_SEPARATOR.$fileName;
        file_put_contents($tempPath, $pdfBinary);

        try {
            $result = $this->whatsapp->enviarDocumentoArquivo($group->jid, $tempPath, $fileName, $caption);
            if ($result['success'] ?? false) {
                return 'sent';
            }

            Log::warning('Escala: falha ao enviar PDF ao grupo WhatsApp.', [
                'jid' => $group->jid,
                'error' => $result['error'] ?? 'desconhecido',
            ]);

            return 'failed';
        } catch (\Throwable $exception) {
            Log::error('Escala: erro ao enviar PDF ao grupo WhatsApp.', [
                'jid' => $group->jid,
                'error' => $exception->getMessage(),
            ]);

            return 'failed';
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * @param  Collection<int, MonthlyCultoSchedule>  $schedules
     * @param  array<int, int>  $areaIds
     */
    public function buildGroupPdf(Collection $schedules, array $areaIds, string $documentTitle): ?string
    {
        $schedules = $schedules->filter()->values();
        if ($schedules->isEmpty() || $areaIds === []) {
            return null;
        }

        $serviceAreas = ServiceArea::query()
            ->active()
            ->whereIn('id', $areaIds)
            ->with(['parent', 'children' => function ($query) {
                $query->active()->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($serviceAreas->isEmpty()) {
            return null;
        }

        $volunteersBySchedule = [];
        $hasContent = false;

        foreach ($schedules as $schedule) {
            $schedule->loadMissing(['event', 'serviceAreaVolunteers.member']);
            $isSunday = optional($schedule->event?->start_date)->dayOfWeek === Carbon::SUNDAY;

            foreach ($serviceAreas as $area) {
                if (! $isSunday && $area->isSundayOnly()) {
                    $volunteersBySchedule[$schedule->id][$area->id] = collect();
                    continue;
                }

                $volunteers = $schedule->getVolunteersByServiceArea($area->id);
                $volunteersBySchedule[$schedule->id][$area->id] = $volunteers;
                if ($volunteers->isNotEmpty() || $schedule->guestPreletorNameForArea($area)) {
                    $hasContent = true;
                }
            }
        }

        if (! $hasContent) {
            return null;
        }

        $logoBase64 = null;
        $logoPath = null;
        $logoPublicPath = public_path('img/img/LOG SS branca.png');
        if (file_exists($logoPublicPath)) {
            $logoPath = $logoPublicPath;
            $logoBase64 = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPublicPath));
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.escalas.escala-grupo', [
            'escalas' => $schedules,
            'serviceAreas' => $serviceAreas,
            'volunteersBySchedule' => $volunteersBySchedule,
            'logoPath' => $logoPath,
            'logoBase64' => $logoBase64,
            'churchName' => 'ADELSS',
            'documentTitle' => $documentTitle,
            'generatedAt' => now(),
        ])->setPaper('A4', 'portrait')
            ->setOption('enable-local-file-access', true)
            ->output();
    }

    /**
     * @param  Collection<int, MonthlyCultoSchedule>  $schedules
     * @return Collection<int, object>
     */
    private function groupsForSchedules(Collection $schedules): Collection
    {
        $assignedIds = $schedules
            ->flatMap(fn (MonthlyCultoSchedule $schedule) => $schedule->serviceAreaVolunteers->pluck('pivot.service_area_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $this->configuredGroups()->filter(function ($group) use ($assignedIds) {
            return $assignedIds->intersect($group->area_ids)->isNotEmpty();
        })->values();
    }

    /**
     * @return Collection<int, object>
     */
    public function configuredGroups(): Collection
    {
        $areas = ServiceArea::query()->with(['parent.leader', 'leader'])->get();

        return $areas
            ->filter(fn (ServiceArea $area) => filled($area->resolvedWhatsAppGroupJid()))
            ->groupBy(fn (ServiceArea $area) => $area->resolvedWhatsAppGroupJid())
            ->map(function (Collection $items, string $jid) {
                $named = $items->first(fn (ServiceArea $area) => filled($area->whatsapp_group_jid) && filled($area->whatsapp_group_name));
                $roots = $items->filter(fn (ServiceArea $area) => ! $area->parent_id);
                $check = $roots->isNotEmpty() ? $roots : $items;

                return (object) [
                    'jid' => $jid,
                    'name' => $named?->whatsapp_group_name ?: ($items->first()->resolvedWhatsAppGroupName() ?: $jid),
                    'area_ids' => $items->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all(),
                    'is_preletor_only' => $check->every(fn (ServiceArea $area) => $this->isPreletorArea($area)),
                    'leaders' => $check
                        ->map(fn (ServiceArea $area) => $area->resolvedLeaderName())
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->values();
    }

    private function publishedSchedulesBetween(Carbon $start, Carbon $end): Collection
    {
        return MonthlyCultoSchedule::query()
            ->with(['event', 'serviceAreaVolunteers.member'])
            ->where('status', 'publicada')
            ->whereHas('event', function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end]);
            })
            ->get()
            ->sortBy(fn ($schedule) => optional($schedule->event?->start_date)->timestamp ?? 0)
            ->values();
    }

    private function publishedSchedulesForMonth(int $month, int $year): Collection
    {
        return MonthlyCultoSchedule::query()
            ->with(['event', 'serviceAreaVolunteers.member'])
            ->where('status', 'publicada')
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->sortBy(fn ($schedule) => optional($schedule->event?->start_date)->timestamp ?? 0)
            ->values();
    }

    private function groupCaption(MonthlyCultoSchedule $escala, string $message = '', ?object $group = null): string
    {
        $culto = trim((string) ($escala->event->title ?? ''));
        $dia = optional($escala->event?->start_date)->format('d/m/Y') ?: '';
        $hora = optional($escala->event?->start_date)->format('H:i') ?: '';
        $local = trim((string) ($escala->event->location ?? '')) ?: 'Não informado';
        $message = html_entity_decode(trim($message), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($message === '') {
            $message = ScheduleNotificationSetting::current()->resolvedImmediateGroupTemplate();
        }

        return trim(ConfiguracaoMensagem::aplicarVariaveis($message, [
            '{nome}' => '',
            '{culto}' => $culto,
            '{dia_culto}' => $dia,
            '{hora_culto}' => $hora,
            '{area_servico}' => $group->name ?? '',
            '{local_servico}' => $local,
            '{responsavel_area}' => $this->leaderNames($group),
        ]));
    }

    private function leaderNames(?object $group): string
    {
        $leaders = $group->leaders ?? [];

        return $leaders === [] ? 'liderança da área' : implode(', ', $leaders);
    }

    /**
     * @return array{tipo:string, is_pdf:bool}
     */
    private function detectMediaType(UploadedFile $arquivo): array
    {
        $mime = strtolower((string) $arquivo->getMimeType());
        if (str_starts_with($mime, 'image/')) {
            return ['tipo' => 'image', 'is_pdf' => false];
        }
        if (str_starts_with($mime, 'video/')) {
            return ['tipo' => 'video', 'is_pdf' => false];
        }
        if (str_starts_with($mime, 'audio/')) {
            return ['tipo' => 'audio', 'is_pdf' => false];
        }

        return ['tipo' => 'document', 'is_pdf' => str_contains($mime, 'pdf')];
    }

    private function isPreletorArea(ServiceArea $area): bool
    {
        $normalized = Str::of($area->name)->lower()->ascii()->value();

        return str_contains($normalized, 'preletor') || str_contains($normalized, 'pregador');
    }

    private function monthLabel(int $month, int $year): string
    {
        return Carbon::create($year, $month, 1)->locale('pt_BR')->translatedFormat('F/Y');
    }
}
