<?php

namespace App\Http\Controllers;

use App\Models\Volunteer;
use App\Models\ServiceHistory;
use App\Models\ServiceArea;
use App\Models\ServiceSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VolunteerReportController extends Controller
{
    /**
     * Relatório: Voluntários Ativos por Área
     */
    public function activeByArea(Request $request)
    {
        $areas = ServiceArea::active()->with('leader')->get();
        
        $dateFrom = $request->input('date_from', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));

        $reportData = [];
        foreach ($areas as $area) {
            $totalVolunteers = $area->volunteers()->where('volunteers.status', 'ativo')->count();
            
            $servedCount = ServiceHistory::where('service_area_id', $area->id)
                                        ->where('status', 'serviu')
                                        ->whereBetween('date', [$dateFrom, $dateTo])
                                        ->distinct('volunteer_id')
                                        ->count('volunteer_id');

            $reportData[] = [
                'area' => $area,
                'total_volunteers' => $totalVolunteers,
                'served_count' => $servedCount,
                'participation_rate' => $totalVolunteers > 0 ? round(($servedCount / $totalVolunteers) * 100, 2) : 0,
            ];
        }

        return view('volunteer-reports.active-by-area', compact('reportData', 'dateFrom', 'dateTo'));
    }

    /**
     * Relatório: Voluntários que Mais Servem
     */
    public function topVolunteers(Request $request)
    {
        $limit = $request->input('limit', 20);
        $dateFrom = $request->input('date_from', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));

        $topVolunteers = ServiceHistory::where('status', 'serviu')
                                      ->whereBetween('date', [$dateFrom, $dateTo])
                                      ->selectRaw('volunteer_id, COUNT(*) as total_services')
                                      ->groupBy('volunteer_id')
                                      ->orderBy('total_services', 'desc')
                                      ->limit($limit)
                                      ->with(['volunteer.member'])
                                      ->get();

        return view('volunteer-reports.top-volunteers', compact('topVolunteers', 'dateFrom', 'dateTo', 'limit'));
    }

    /**
     * Relatório: Voluntários Sem Servir Há X Dias
     */
    public function inactiveVolunteers(Request $request)
    {
        $days = $request->input('days', 30);
        $dateThreshold = Carbon::now()->subDays($days);

        $activeVolunteers = Volunteer::where('status', 'ativo')
                                    ->with('member')
                                    ->get();

        $inactiveVolunteers = [];
        foreach ($activeVolunteers as $volunteer) {
            $lastService = ServiceHistory::where('volunteer_id', $volunteer->id)
                                        ->where('status', 'serviu')
                                        ->orderBy('date', 'desc')
                                        ->first();

            if (!$lastService || $lastService->date < $dateThreshold) {
                $daysSince = $lastService 
                    ? $lastService->date->diffInDays(Carbon::now())
                    : null;

                $inactiveVolunteers[] = [
                    'volunteer' => $volunteer,
                    'last_service' => $lastService,
                    'days_since' => $daysSince,
                ];
            }
        }

        // Ordenar por dias sem servir (mais dias primeiro)
        usort($inactiveVolunteers, function($a, $b) {
            $daysA = $a['days_since'] ?? 9999;
            $daysB = $b['days_since'] ?? 9999;
            return $daysB <=> $daysA;
        });

        return view('volunteer-reports.inactive-volunteers', compact('inactiveVolunteers', 'days'));
    }

    /**
     * Relatório: Déficit por Área
     */
    public function deficitByArea(Request $request)
    {
        $dateFrom = $request->input('date_from', Carbon::now()->subMonths(1)->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));

        $areas = ServiceArea::active()->with('leader')->get();

        $reportData = [];
        foreach ($areas as $area) {
            $minQuantity = $area->min_quantity ?? 0;
            
            // Calcular média real de voluntários que serviram por culto/evento
            $avgServices = ServiceHistory::where('service_area_id', $area->id)
                                        ->where('status', 'serviu')
                                        ->whereBetween('date', [$dateFrom, $dateTo])
                                        ->selectRaw('schedule_id, COUNT(DISTINCT volunteer_id) as volunteer_count')
                                        ->groupBy('schedule_id')
                                        ->get()
                                        ->avg('volunteer_count');

            $avgServices = round($avgServices ?? 0, 1);
            $deficit = max(0, $minQuantity - $avgServices);

            $reportData[] = [
                'area' => $area,
                'min_quantity' => $minQuantity,
                'avg_real' => $avgServices,
                'deficit' => $deficit,
                'status' => $avgServices >= $minQuantity ? 'ok' : 'deficit',
            ];
        }

        // Ordenar por déficit (maior déficit primeiro)
        usort($reportData, function($a, $b) {
            return $b['deficit'] <=> $a['deficit'];
        });

        return view('volunteer-reports.deficit-by-area', compact('reportData', 'dateFrom', 'dateTo'));
    }

    /**
     * Relatório: Por Culto / Evento
     */
    public function bySchedule(Request $request)
    {
        $query = ServiceSchedule::with(['areas.serviceArea', 'areas.volunteers.volunteer.member', 'serviceHistories']);

        if ($request->has('schedule_id') && $request->schedule_id) {
            $query->where('id', $request->schedule_id);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $schedules = $query->orderBy('date', 'desc')
                          ->orderBy('start_time', 'desc')
                          ->paginate(20);

        $allSchedules = ServiceSchedule::orderBy('date', 'desc')->limit(100)->get();

        return view('volunteer-reports.by-schedule', compact('schedules', 'allSchedules'));
    }

    /**
     * Dashboard de Relatórios
     */
    public function dashboard()
    {
        // Estatísticas gerais
        $totalVolunteers = Volunteer::where('status', 'ativo')->count();
        $totalActiveAreas = ServiceArea::active()->count();
        
        $lastMonth = Carbon::now()->subMonth();
        $servicesLastMonth = ServiceHistory::where('status', 'serviu')
                                          ->where('date', '>=', $lastMonth)
                                          ->count();

        // Top 5 áreas com mais serviços
        $topAreas = ServiceHistory::where('status', 'serviu')
                                 ->where('date', '>=', $lastMonth)
                                 ->selectRaw('service_area_id, COUNT(*) as total')
                                 ->groupBy('service_area_id')
                                 ->orderBy('total', 'desc')
                                 ->limit(5)
                                 ->with('serviceArea')
                                 ->get();

        // Voluntários inativos (últimos 60 dias)
        $inactiveCount = 0;
        $activeVolunteers = Volunteer::where('status', 'ativo')->get();
        foreach ($activeVolunteers as $volunteer) {
            $lastService = ServiceHistory::where('volunteer_id', $volunteer->id)
                                        ->where('status', 'serviu')
                                        ->orderBy('date', 'desc')
                                        ->first();
            
            if (!$lastService || $lastService->date < Carbon::now()->subDays(60)) {
                $inactiveCount++;
            }
        }

        return view('volunteer-reports.dashboard', compact(
            'totalVolunteers',
            'totalActiveAreas',
            'servicesLastMonth',
            'topAreas',
            'inactiveCount'
        ));
    }
}
