<?php

namespace App\Http\Controllers\Cultos;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Member;
use App\Models\ServiceReport;
use App\Models\ServiceReportSetting;
use App\Models\ServiceReportSpiritualDecision;
use App\Services\ServiceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceReportController extends Controller
{
    public function __construct(
        private ServiceReportService $serviceReportService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', ServiceReport::class);

        $settings = ServiceReportSetting::current();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthQuery = ServiceReport::query()
            ->whereBetween('report_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $kpiReports = (clone $monthQuery)->count();
        $monthReports = (clone $monthQuery)->with(['attendances', 'visitors'])->get();

        $membersPresent = 0;
        $visitorsPresent = 0;
        $offeringTotal = 0.0;
        foreach ($monthReports as $report) {
            $membersPresent += $report->presentMembersCount();
            $visitorsPresent += $report->resolvedVisitorsCount();
            $offeringTotal += (float) ($report->offering_total ?? 0);
        }

        $reports = $this->filteredReportsQuery($request)
            ->with(['preacher:id,name'])
            ->latest('report_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('cultos.index', array_merge($this->formData(), [
            'reports' => $reports,
            'kpi' => [
                'reports' => $kpiReports,
                'present' => $membersPresent + $visitorsPresent,
                'members' => $membersPresent,
                'visitors' => $visitorsPresent,
                'offering' => $offeringTotal,
            ],
            'filters' => [
                'q' => trim((string) $request->input('q', '')),
                'status' => $request->input('status', ''),
                'period' => $request->input('period', ''),
            ],
            'openNewReportModal' => $request->boolean('novo') || $request->session()->has('errors'),
        ]));
    }

    public function show(ServiceReport $serviceReport)
    {
        $this->authorize('view', $serviceReport);

        $serviceReport->load([
            'attendances.member:id,name',
            'visitors.invitedBy:id,name',
            'photos',
            'spiritualDecisions',
            'preacher:id,name',
            'event:id,title,start_date',
        ]);

        $financial = $this->serviceReportService->financialSummaryForDate($serviceReport->report_date);
        $presentAttendances = $serviceReport->attendances
            ->where('present', true)
            ->values()
            ->map(fn ($a) => [
                'id' => $a->member_id,
                'name' => $a->member?->name ?? '—',
                'initials' => $this->initials($a->member?->name),
            ]);

        return response()->json([
            'id' => $serviceReport->id,
            'service_type_label' => $serviceReport->service_type_label,
            'status' => $serviceReport->status,
            'status_label' => $serviceReport->status === ServiceReport::STATUS_FINAL ? 'Finalizado' : 'Rascunho',
            'report_date' => $serviceReport->report_date?->toDateString(),
            'report_date_label' => $serviceReport->report_date
                ? $serviceReport->report_date->locale('pt_BR')->translatedFormat('l, d \\d\\e F \\d\\e Y')
                : '—',
            'start_time' => $serviceReport->start_time ? substr((string) $serviceReport->start_time, 0, 5) : null,
            'preacher_name' => $serviceReport->preacher_name,
            'message_theme' => $serviceReport->message_theme,
            'campaign_series' => $serviceReport->campaign_series,
            'description' => $serviceReport->description,
            'highlights' => $serviceReport->highlights,
            'event_title' => $serviceReport->event?->title,
            'members_count' => $serviceReport->presentMembersCount(),
            'visitors_count' => $serviceReport->resolvedVisitorsCount(),
            'total_present' => $serviceReport->totalPresent(),
            'children_count' => $serviceReport->children_count,
            'volunteers_count' => $serviceReport->volunteers_count,
            'offering_total' => (float) ($serviceReport->offering_total ?? $financial['total']),
            'financial' => $financial,
            'present_members' => $presentAttendances,
            'visitors' => $serviceReport->visitors->map(fn ($v) => [
                'name' => $v->name,
                'phone' => $v->phone,
                'invited_by' => $v->invitedBy?->name,
            ])->values(),
            'spiritual_decisions' => $serviceReport->spiritualDecisions->map(fn ($s) => [
                'type' => $s->type,
                'type_label' => ServiceReportSpiritualDecision::TYPES[$s->type] ?? $s->type,
                'person_name' => $s->person_name,
                'notes' => $s->notes,
            ])->values(),
            'photos' => $serviceReport->photos->map(fn ($p) => [
                'id' => $p->id,
                'url' => $p->url,
            ])->values(),
            'edit_url' => auth()->user()?->can('update', $serviceReport)
                ? route('cultos.edit', $serviceReport)
                : null,
            'pdf_url' => route('cultos.pdf', $serviceReport),
        ]);
    }

    public function pdf(ServiceReport $serviceReport)
    {
        $this->authorize('view', $serviceReport);

        $serviceReport->load([
            'attendances.member:id,name',
            'visitors',
            'spiritualDecisions',
            'preacher:id,name',
            'photos',
        ]);

        $financial = $this->serviceReportService->financialSummaryForDate($serviceReport->report_date);

        $pdf = Pdf::loadView('cultos.pdf.show', [
            'report' => $serviceReport,
            'financial' => $financial,
            'churchName' => config('app.name', 'ADELSS'),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $fileName = 'relatorio-culto-' . $serviceReport->report_date->format('Y-m-d') . '-' . $serviceReport->id . '.pdf';

        return $pdf->download($fileName);
    }

    public function pdfConsolidated(Request $request)
    {
        $this->authorize('viewAny', ServiceReport::class);

        $reports = $this->filteredReportsQuery($request)
            ->with(['preacher:id,name', 'attendances', 'visitors'])
            ->orderBy('report_date')
            ->orderBy('id')
            ->get();

        $rows = $reports->map(function (ServiceReport $report) {
            $financial = $this->serviceReportService->financialSummaryForDate($report->report_date);
            $members = $report->presentMembersCount();
            $visitors = $report->resolvedVisitorsCount();

            return [
                'date' => $report->report_date,
                'type' => $report->service_type_label,
                'preacher' => $report->preacher_name,
                'present' => $members + $visitors,
                'members' => $members,
                'visitors' => $visitors,
                'ofertas' => $financial['ofertas'],
                'dizimos' => $financial['dizimos'],
                'total' => $financial['total'],
            ];
        });

        $summary = [
            'cultos' => $rows->count(),
            'present' => $rows->sum('present'),
            'members' => $rows->sum('members'),
            'visitors' => $rows->sum('visitors'),
            'ofertas' => round($rows->sum('ofertas'), 2),
            'dizimos' => round($rows->sum('dizimos'), 2),
            'total' => round($rows->sum('total'), 2),
            'avg_present' => $rows->count() > 0
                ? round($rows->sum('present') / $rows->count(), 1)
                : 0,
        ];

        $pdf = Pdf::loadView('cultos.pdf.consolidated', [
            'rows' => $rows,
            'summary' => $summary,
            'periodLabel' => $this->periodLabel($request),
            'churchName' => config('app.name', 'ADELSS'),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('relatorio-consolidado-cultos.pdf');
    }

    public function financialSummary(Request $request)
    {
        $this->authorize('viewAny', ServiceReport::class);

        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        return response()->json(
            $this->serviceReportService->financialSummaryForDate($validated['date'])
        );
    }

    public function create()
    {
        $this->authorize('create', ServiceReport::class);

        return redirect()->route('cultos.index', ['novo' => 1]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', ServiceReport::class);

        $this->serviceReportService->storeFromRequest($request);

        return redirect()
            ->route('cultos.index')
            ->with('success', 'Relatório de culto salvo com sucesso.');
    }

    public function edit(ServiceReport $serviceReport)
    {
        $this->authorize('update', $serviceReport);

        $serviceReport->load([
            'attendances', 'visitors', 'offerings', 'photos', 'spiritualDecisions', 'preacher',
        ]);

        return view('cultos.form', array_merge($this->formData($serviceReport), [
            'report' => $serviceReport,
        ]));
    }

    public function update(Request $request, ServiceReport $serviceReport)
    {
        $this->authorize('update', $serviceReport);

        $this->serviceReportService->storeFromRequest($request, $serviceReport);

        return redirect()
            ->route('cultos.index')
            ->with('success', 'Relatório atualizado com sucesso.');
    }

    public function destroy(ServiceReport $serviceReport)
    {
        $this->authorize('delete', $serviceReport);

        $serviceReport->delete();

        return redirect()
            ->route('cultos.index')
            ->with('success', 'Relatório excluído com sucesso.');
    }

    public function finalize(ServiceReport $serviceReport)
    {
        $this->authorize('update', $serviceReport);

        $serviceReport->update(['status' => ServiceReport::STATUS_FINAL]);

        return back()->with('success', 'Relatório finalizado.');
    }

    public function analyses()
    {
        $this->authorize('viewAny', ServiceReport::class);

        $byType = ServiceReport::query()
            ->selectRaw('service_type, COUNT(*) as total')
            ->groupBy('service_type')
            ->orderByDesc('total')
            ->get();

        $monthly = ServiceReport::query()
            ->where('report_date', '>=', now()->subMonths(5)->startOfMonth())
            ->get()
            ->groupBy(fn (ServiceReport $r) => $r->report_date->format('Y-m'))
            ->map(fn ($items, $key) => [
                'month' => Carbon::createFromFormat('Y-m', $key)->locale('pt_BR')->translatedFormat('M/Y'),
                'count' => $items->count(),
                'offering' => $items->sum(fn ($i) => (float) ($i->offering_total ?? 0)),
                'present' => $items->sum(fn ($i) => $i->totalPresent()),
            ])
            ->values();

        return view('cultos.analyses', compact('byType', 'monthly'));
    }

    public function alerts()
    {
        $this->authorize('viewAny', ServiceReport::class);

        $settings = ServiceReportSetting::current();

        return view('cultos.alerts', compact('settings'));
    }

    private function filteredReportsQuery(Request $request)
    {
        $query = ServiceReport::query();

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('message_theme', 'like', "%{$search}%")
                    ->orWhere('external_preacher_name', 'like', "%{$search}%")
                    ->orWhere('custom_type_label', 'like', "%{$search}%")
                    ->orWhere('service_type', 'like', "%{$search}%")
                    ->orWhereHas('preacher', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            if (in_array($status, [ServiceReport::STATUS_DRAFT, ServiceReport::STATUS_FINAL], true)) {
                $query->where('status', $status);
            }
        }

        if ($period = $request->input('period')) {
            if ($period === 'mes') {
                $query->whereBetween('report_date', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString(),
                ]);
            } elseif ($period === '30d') {
                $query->where('report_date', '>=', now()->subDays(30)->toDateString());
            } elseif ($period === 'ano') {
                $query->whereYear('report_date', now()->year);
            }
        }

        return $query;
    }

    private function periodLabel(Request $request): string
    {
        return match ($request->input('period')) {
            'mes' => 'Mês atual',
            '30d' => 'Últimos 30 dias',
            'ano' => 'Ano atual',
            default => 'Todos os períodos',
        };
    }

    private function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $first = Str::upper(Str::substr($parts[0] ?? '', 0, 1));
        $last = count($parts) > 1
            ? Str::upper(Str::substr($parts[count($parts) - 1], 0, 1))
            : '';

        return $first . $last;
    }

    private function formData(?ServiceReport $report = null): array
    {
        $settings = ServiceReportSetting::current();
        $members = Member::query()
            ->where('status', 'ativo')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $events = Event::query()
            ->where(function ($q) use ($report) {
                // Uma semana antes de hoje até o fim do mês atual
                $q->whereBetween('start_date', [
                    now()->subWeek()->startOfDay()->toDateTimeString(),
                    now()->endOfMonth()->toDateTimeString(),
                ]);
                if ($report?->event_id) {
                    $q->orWhere('id', $report->event_id);
                }
            })
            ->orderBy('start_date')
            ->get(['id', 'title', 'start_date']);

        return [
            'settings' => $settings,
            'members' => $members,
            'events' => $events,
            'types' => ServiceReport::TYPES,
            'report' => $report,
        ];
    }
}
