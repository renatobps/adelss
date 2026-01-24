<?php

namespace App\Http\Controllers;

use App\Models\ServiceHistory;
use App\Models\Volunteer;
use App\Models\ServiceArea;
use App\Models\ServiceSchedule;
use Illuminate\Http\Request;

class ServiceHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ServiceHistory::with(['member', 'volunteer.member', 'serviceArea', 'schedule']);

        // Filtros
        if ($request->has('volunteer') && $request->volunteer) {
            $query->where('volunteer_id', $request->volunteer);
        }

        if ($request->has('area') && $request->area) {
            $query->where('service_area_id', $request->area);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->has('service_type') && $request->service_type) {
            $query->where('service_type', $request->service_type);
        }

        $histories = $query->orderBy('date', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->paginate(20);

        $volunteers = Volunteer::with('member')->active()->get();
        $serviceAreas = ServiceArea::active()->get();

        $statusLabels = [
            'serviu' => 'Serviu',
            'confirmado_nao_compareceu' => 'Confirmado, não compareceu',
            'indisponivel' => 'Indisponível',
            'substituido' => 'Substituído',
        ];

        return view('service-history.index', compact('histories', 'volunteers', 'serviceAreas', 'statusLabels'));
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceHistory $history)
    {
        $history->load(['member', 'volunteer.member', 'serviceArea', 'schedule.areas']);

        $statusLabels = [
            'serviu' => 'Serviu',
            'confirmado_nao_compareceu' => 'Confirmado, não compareceu',
            'indisponivel' => 'Indisponível',
            'substituido' => 'Substituído',
        ];

        return view('service-history.show', compact('history', 'statusLabels'));
    }

    /**
     * Mostrar histórico individual por voluntário
     */
    public function showByVolunteer(Volunteer $volunteer, Request $request)
    {
        $volunteer->load('member');

        $query = ServiceHistory::with(['serviceArea', 'schedule'])
                               ->where('volunteer_id', $volunteer->id);

        // Filtro por período
        if ($request->has('date_from') && $request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        $histories = $query->orderBy('date', 'desc')->paginate(20);

        // Estatísticas
        $totalServices = ServiceHistory::where('volunteer_id', $volunteer->id)
                                       ->where('status', 'serviu')
                                       ->count();

        $lastService = ServiceHistory::where('volunteer_id', $volunteer->id)
                                     ->where('status', 'serviu')
                                     ->orderBy('date', 'desc')
                                     ->first();

        // Área principal (que mais serviu)
        $mainArea = ServiceHistory::where('volunteer_id', $volunteer->id)
                                  ->where('status', 'serviu')
                                  ->selectRaw('service_area_id, COUNT(*) as total')
                                  ->groupBy('service_area_id')
                                  ->orderBy('total', 'desc')
                                  ->first();

        $mainAreaData = null;
        if ($mainArea) {
            $mainAreaData = ServiceArea::find($mainArea->service_area_id);
        }

        $statusLabels = [
            'serviu' => 'Serviu',
            'confirmado_nao_compareceu' => 'Confirmado, não compareceu',
            'indisponivel' => 'Indisponível',
            'substituido' => 'Substituído',
        ];

        return view('service-history.volunteer', compact(
            'volunteer',
            'histories',
            'totalServices',
            'lastService',
            'mainAreaData',
            'statusLabels'
        ));
    }
}
