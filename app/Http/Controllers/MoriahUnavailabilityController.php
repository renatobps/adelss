<?php

namespace App\Http\Controllers;

use App\Models\MoriahUnavailability;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MoriahUnavailabilityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        
        // Buscar apenas membros do Moriah
        $members = Member::where('status', 'ativo')
            ->whereHas('moriahFunctions')
            ->with('moriahFunctions')
            ->orderBy('name')
            ->get();
        
        // Buscar indisponibilidades do mês
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        
        $unavailabilities = MoriahUnavailability::where(function($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhere(function($q) use ($startDate, $endDate) {
                      $q->whereNotNull('end_date')
                        ->whereBetween('end_date', [$startDate, $endDate]);
                  })
                  ->orWhere(function($q) use ($startDate, $endDate) {
                      $q->where('start_date', '<=', $startDate)
                        ->where(function($query) use ($endDate) {
                            $query->whereNull('end_date')
                                  ->orWhere('end_date', '>=', $endDate);
                        });
                  });
        })
        ->with('member:id,name,photo_url')
        ->get();
        
        // Organizar indisponibilidades por data
        $unavailabilitiesByDate = [];
        foreach ($unavailabilities as $unavailability) {
            $currentDate = $unavailability->start_date->copy();
            $endDate = $unavailability->end_date ?? $unavailability->start_date;
            
            while ($currentDate <= $endDate && $currentDate->month == $month && $currentDate->year == $year) {
                $dateKey = $currentDate->format('Y-m-d');
                if (!isset($unavailabilitiesByDate[$dateKey])) {
                    $unavailabilitiesByDate[$dateKey] = [];
                }
                $unavailabilitiesByDate[$dateKey][] = $unavailability;
                $currentDate->addDay();
            }
        }
        
        return view('moriah.unavailabilities.index', compact('members', 'unavailabilitiesByDate', 'month', 'year'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'description' => 'nullable|string|max:500',
            'is_period' => 'nullable|boolean',
        ]);
        
        $isPeriod = $request->has('is_period') && ($request->is_period === true || $request->is_period === '1' || $request->is_period === 'on');
        
        $data = [
            'member_id' => $request->member_id,
            'start_date' => $request->date,
            'end_date' => $isPeriod ? $request->end_date : null,
            'description' => $request->description,
            'is_period' => $isPeriod,
        ];
        
        MoriahUnavailability::create($data);
        
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Indisponibilidade cadastrada com sucesso!']);
        }
        
        return redirect()->route('moriah.unavailabilities.index')
            ->with('success', 'Indisponibilidade cadastrada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $unavailability = MoriahUnavailability::findOrFail($id);
        $unavailability->delete();
        
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Indisponibilidade removida com sucesso!']);
        }
        
        return redirect()->back()->with('success', 'Indisponibilidade removida com sucesso!');
    }

    /**
     * Verificar indisponibilidades de membros em uma data
     */
    public function checkUnavailabilities(Request $request)
    {
        $request->validate([
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:members,id',
            'date' => 'required|date',
        ]);
        
        $date = Carbon::parse($request->date);
        $unavailableMembers = [];
        
        foreach ($request->member_ids as $memberId) {
            $unavailability = MoriahUnavailability::where('member_id', $memberId)
                ->where(function($query) use ($date) {
                    $query->where(function($q) use ($date) {
                        $q->where('start_date', '<=', $date)
                          ->where(function($query) use ($date) {
                              $query->whereNull('end_date')
                                    ->orWhere('end_date', '>=', $date);
                          });
                    });
                })
                ->first();
            
            if ($unavailability) {
                $member = Member::find($memberId);
                $unavailableMembers[] = [
                    'member_id' => $memberId,
                    'member_name' => $member->name,
                    'unavailability' => $unavailability
                ];
            }
        }
        
        return response()->json([
            'has_unavailabilities' => count($unavailableMembers) > 0,
            'unavailable_members' => $unavailableMembers
        ]);
    }
}
