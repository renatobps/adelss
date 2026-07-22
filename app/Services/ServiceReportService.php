<?php

namespace App\Services;

use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\ServiceReport;
use App\Models\ServiceReportAttendance;
use App\Models\ServiceReportPhoto;
use App\Models\ServiceReportSetting;
use App\Models\ServiceReportSpiritualDecision;
use App\Models\ServiceReportVisitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceReportService
{
    public function storeFromRequest(Request $request, ?ServiceReport $report = null): ServiceReport
    {
        $settings = ServiceReportSetting::current();
        $isUpdate = (bool) $report;

        $validated = $this->validateRequest($request, $settings);

        return DB::transaction(function () use ($validated, $request, $settings, $report, $isUpdate) {
            $data = [
                'event_id' => $validated['event_id'] ?? null,
                'report_date' => $validated['report_date'],
                'service_type' => $validated['service_type'],
                'custom_type_label' => ($validated['service_type'] ?? '') === 'outro'
                    ? ($validated['custom_type_label'] ?? null)
                    : null,
                'start_time' => $validated['start_time'] ?? null,
                'preacher_member_id' => !empty($validated['external_preacher'])
                    ? null
                    : ($validated['preacher_member_id'] ?? null),
                'external_preacher_name' => !empty($validated['external_preacher'])
                    ? ($validated['external_preacher_name'] ?? null)
                    : null,
                'message_theme' => $validated['message_theme'] ?? null,
                'campaign_series' => $validated['campaign_series'] ?? null,
                'description' => $validated['description'] ?? null,
                'highlights' => $validated['highlights'] ?? null,
                'children_count' => $settings->enable_children_count
                    ? ($validated['children_count'] ?? null)
                    : null,
                'volunteers_count' => $settings->enable_volunteers_count
                    ? ($validated['volunteers_count'] ?? null)
                    : null,
                'status' => $validated['status'] ?? ServiceReport::STATUS_DRAFT,
            ];

            if ($settings->isDetailed()) {
                $data['members_present_count'] = null;
                $data['visitors_count'] = null;
            } else {
                $data['members_present_count'] = (int) ($validated['members_present_count'] ?? 0);
                $data['visitors_count'] = (int) ($validated['visitors_count'] ?? 0);
            }

            $financial = $this->financialSummaryForDate($validated['report_date']);
            $data['offering_total'] = $financial['total'];

            if ($isUpdate) {
                $report->update($data);
            } else {
                $data['created_by'] = Auth::id();
                $report = ServiceReport::create($data);
            }

            $this->syncAttendances($report, $settings, $validated);
            $this->syncVisitors($report, $settings, $validated);
            $this->syncSpiritualDecisions($report, $settings, $validated);
            $this->syncPhotos($report, $request);

            return $report->fresh([
                'attendances', 'visitors', 'offerings', 'photos', 'spiritualDecisions', 'preacher',
            ]);
        });
    }

    private function validateRequest(Request $request, ServiceReportSetting $settings): array
    {
        $rules = [
            'event_id' => 'nullable|exists:events,id',
            'report_date' => 'required|date',
            'service_type' => 'required|in:' . implode(',', array_keys(ServiceReport::TYPES)),
            'custom_type_label' => 'nullable|string|max:120',
            'start_time' => 'nullable|date_format:H:i',
            'external_preacher' => 'nullable|boolean',
            'preacher_member_id' => 'nullable|exists:members,id',
            'external_preacher_name' => 'nullable|string|max:120',
            'message_theme' => 'nullable|string|max:255',
            'campaign_series' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'highlights' => 'nullable|string',
            'members_present_count' => 'nullable|integer|min:0',
            'visitors_count' => 'nullable|integer|min:0',
            'children_count' => 'nullable|integer|min:0',
            'volunteers_count' => 'nullable|integer|min:0',
            'status' => 'nullable|in:rascunho,finalizado',
            'attendances' => 'nullable|array',
            'attendances.*.member_id' => 'required_with:attendances|exists:members,id',
            'attendances.*.present' => 'nullable|boolean',
            'visitors' => 'nullable|array',
            'visitors.*.name' => 'required_with:visitors|string|max:120',
            'visitors.*.phone' => 'nullable|string|max:30',
            'visitors.*.invited_by_member_id' => 'nullable|exists:members,id',
            'spiritual_decisions' => 'nullable|array',
            'spiritual_decisions.*.type' => 'required_with:spiritual_decisions|in:batismo_espirito,cura,decisao_por_cristo,outro',
            'spiritual_decisions.*.person_name' => 'nullable|string|max:120',
            'spiritual_decisions.*.member_id' => 'nullable|exists:members,id',
            'spiritual_decisions.*.notes' => 'nullable|string',
            'photos' => 'nullable|array|max:20',
            'photos.*' => 'image|max:5120',
            'remove_photo_ids' => 'nullable|array',
            'remove_photo_ids.*' => 'integer',
        ];

        return $request->validate($rules);
    }

    private function syncAttendances(ServiceReport $report, ServiceReportSetting $settings, array $validated): void
    {
        $report->attendances()->delete();

        if (!$settings->isDetailed()) {
            return;
        }

        foreach ($validated['attendances'] ?? [] as $row) {
            if (empty($row['member_id'])) {
                continue;
            }

            ServiceReportAttendance::create([
                'service_report_id' => $report->id,
                'member_id' => (int) $row['member_id'],
                'present' => !empty($row['present']),
            ]);
        }
    }

    private function syncVisitors(ServiceReport $report, ServiceReportSetting $settings, array $validated): void
    {
        $report->visitors()->delete();

        if (!$settings->isDetailed()) {
            return;
        }

        foreach ($validated['visitors'] ?? [] as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $visitor = ServiceReportVisitor::create([
                'service_report_id' => $report->id,
                'name' => $name,
                'phone' => $row['phone'] ?? null,
                'invited_by_member_id' => $row['invited_by_member_id'] ?? null,
                'created_at' => now(),
            ]);

            if ($settings->auto_register_visitor_as_member) {
                $phone = trim((string) ($row['phone'] ?? ''));
                $memberQuery = Member::query()->where('name', $name);
                if ($phone !== '') {
                    $memberQuery->where('phone', $phone);
                }
                $member = $memberQuery->first();

                if (!$member) {
                    $member = Member::create([
                        'name' => $name,
                        'phone' => $phone !== '' ? $phone : null,
                        'status' => 'visitante',
                    ]);
                }

                $visitor->update(['converted_to_member_id' => $member->id]);
            }
        }
    }

    /**
     * Arrecadação do culto a partir das receitas (dízimo/oferta) do módulo Financeiro na data.
     *
     * @return array{total: float, ofertas: float, dizimos: float, count: int, by_category: list<array{name: string, total: float, count: int}>}
     */
    public function financialSummaryForDate(string|\DateTimeInterface $date): array
    {
        $day = Carbon::parse($date)->toDateString();

        $hasReceiptCategories = FinancialCategory::query()
            ->receitas()
            ->where('sends_receipt', true)
            ->exists();

        $query = FinancialTransaction::query()
            ->receitas()
            ->where('is_paid', true)
            ->whereDate('transaction_date', $day);

        if ($hasReceiptCategories) {
            $query->whereHas('category', fn ($q) => $q->where('sends_receipt', true));
        }

        $transactions = $query
            ->with('category:id,name')
            ->get(['id', 'amount', 'category_id']);

        $byCategory = $transactions
            ->groupBy(fn ($tx) => $tx->category?->name ?: 'Sem categoria')
            ->map(fn ($items, $name) => [
                'name' => (string) $name,
                'total' => round((float) $items->sum('amount'), 2),
                'count' => $items->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        $ofertas = 0.0;
        $dizimos = 0.0;
        foreach ($byCategory as $row) {
            $name = mb_strtolower($row['name']);
            if (str_contains($name, 'dízimo') || str_contains($name, 'dizimo')) {
                $dizimos += $row['total'];
            } elseif (str_contains($name, 'oferta')) {
                $ofertas += $row['total'];
            }
        }

        // Se categorias não baterem pelo nome, divide o restante no total geral
        $classified = round($ofertas + $dizimos, 2);
        $total = round((float) $transactions->sum('amount'), 2);
        if ($classified <= 0 && $total > 0) {
            $ofertas = $total;
        }

        return [
            'total' => $total,
            'ofertas' => round($ofertas, 2),
            'dizimos' => round($dizimos, 2),
            'count' => $transactions->count(),
            'by_category' => $byCategory,
        ];
    }

    private function syncSpiritualDecisions(ServiceReport $report, ServiceReportSetting $settings, array $validated): void
    {
        $report->spiritualDecisions()->delete();

        if (!$settings->enable_spiritual_decisions) {
            return;
        }

        foreach ($validated['spiritual_decisions'] ?? [] as $row) {
            if (empty($row['type'])) {
                continue;
            }

            ServiceReportSpiritualDecision::create([
                'service_report_id' => $report->id,
                'type' => $row['type'],
                'person_name' => $row['person_name'] ?? null,
                'member_id' => $row['member_id'] ?? null,
                'notes' => $row['notes'] ?? null,
            ]);
        }
    }

    private function syncPhotos(ServiceReport $report, Request $request): void
    {
        $removeIds = $request->input('remove_photo_ids', []);
        if (is_array($removeIds) && count($removeIds) > 0) {
            $photos = $report->photos()->whereIn('id', $removeIds)->get();
            foreach ($photos as $photo) {
                Storage::disk('public')->delete($photo->path);
                $photo->delete();
            }
        }

        if (!$request->hasFile('photos')) {
            return;
        }

        foreach ($request->file('photos') as $file) {
            if (!$file->isValid()) {
                continue;
            }

            $path = $file->store('cultos/' . $report->id, 'public');
            ServiceReportPhoto::create([
                'service_report_id' => $report->id,
                'path' => $path,
                'uploaded_at' => now(),
            ]);
        }
    }
}
