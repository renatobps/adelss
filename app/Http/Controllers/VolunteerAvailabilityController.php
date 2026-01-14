<?php

namespace App\Http\Controllers;

use App\Models\Volunteer;
use App\Models\VolunteerAvailability;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VolunteerAvailabilityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Volunteer::with(['member', 'availability']);

        // Busca
        if ($request->has('search') && $request->search) {
            $query->whereHas('member', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        // Filtro por status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $volunteers = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('volunteer-availability.index', compact('volunteers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $volunteerId = $request->get('volunteer_id');
        
        if (!$volunteerId) {
            return redirect()->route('voluntarios.disponibilidade.index')
                ->with('error', 'Selecione um voluntário para cadastrar disponibilidade.');
        }

        $volunteer = Volunteer::with('member')->findOrFail($volunteerId);
        
        // Verificar se já existe disponibilidade
        if ($volunteer->availability) {
            return redirect()->route('voluntarios.disponibilidade.edit', $volunteer->availability)
                ->with('info', 'Este voluntário já possui disponibilidade cadastrada. Redirecionando para edição.');
        }

        $events = Event::where('start_date', '>=', now())
                      ->orderBy('start_date')
                      ->get();

        return view('volunteer-availability.create', compact('volunteer', 'events'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'volunteer_id' => 'required|exists:volunteers,id|unique:volunteer_availability,volunteer_id',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'in:segunda,terça,quarta,quinta,sexta,sábado,domingo',
            'time_start' => 'nullable|date_format:H:i',
            'time_end' => 'nullable|date_format:H:i|after:time_start',
            'unavailable_start' => 'nullable|date',
            'unavailable_end' => 'nullable|date|after_or_equal:unavailable_start',
            'notes' => 'nullable|string',
            'events' => 'nullable|array',
            'events.*' => 'exists:events,id',
        ], [
            'volunteer_id.required' => 'Selecione um voluntário.',
            'volunteer_id.exists' => 'O voluntário selecionado não existe.',
            'volunteer_id.unique' => 'Este voluntário já possui disponibilidade cadastrada.',
            'days_of_week.array' => 'Os dias da semana devem ser uma lista válida.',
            'time_end.after' => 'O horário de término deve ser posterior ao horário de início.',
            'unavailable_end.after_or_equal' => 'A data de término da indisponibilidade deve ser posterior ou igual à data de início.',
            'events.array' => 'Os eventos devem ser uma lista válida.',
            'events.*.exists' => 'Um ou mais eventos selecionados não existem.',
        ]);

        DB::beginTransaction();
        try {
            $availability = VolunteerAvailability::create([
                'volunteer_id' => $validated['volunteer_id'],
                'days_of_week' => $validated['days_of_week'] ?? null,
                'time_start' => $validated['time_start'] ?? null,
                'time_end' => $validated['time_end'] ?? null,
                'unavailable_start' => $validated['unavailable_start'] ?? null,
                'unavailable_end' => $validated['unavailable_end'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Sincronizar eventos (todos como disponíveis por padrão)
            if (isset($validated['events']) && is_array($validated['events'])) {
                $eventsData = [];
                foreach ($validated['events'] as $eventId) {
                    $eventsData[$eventId] = ['available' => true];
                }
                $availability->volunteer->availabilityEvents()->sync($eventsData);
            }

            DB::commit();

            return redirect()->route('voluntarios.disponibilidade.index')
                ->with('success', 'Disponibilidade cadastrada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao cadastrar disponibilidade: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(VolunteerAvailability $disponibilidade)
    {
        $disponibilidade->load(['volunteer.member', 'volunteer.availabilityEvents']);
        
        return view('volunteer-availability.show', compact('disponibilidade'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VolunteerAvailability $disponibilidade)
    {
        $disponibilidade->load(['volunteer.member', 'volunteer.availabilityEvents']);
        
        $events = Event::where('start_date', '>=', now()->subDays(30))
                      ->orderBy('start_date')
                      ->get();

        return view('volunteer-availability.edit', compact('disponibilidade', 'events'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VolunteerAvailability $disponibilidade)
    {
        $validated = $request->validate([
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'in:segunda,terça,quarta,quinta,sexta,sábado,domingo',
            'time_start' => 'nullable|date_format:H:i',
            'time_end' => 'nullable|date_format:H:i|after:time_start',
            'unavailable_start' => 'nullable|date',
            'unavailable_end' => 'nullable|date|after_or_equal:unavailable_start',
            'notes' => 'nullable|string',
            'events' => 'nullable|array',
            'events.*' => 'exists:events,id',
        ], [
            'days_of_week.array' => 'Os dias da semana devem ser uma lista válida.',
            'time_end.after' => 'O horário de término deve ser posterior ao horário de início.',
            'unavailable_end.after_or_equal' => 'A data de término da indisponibilidade deve ser posterior ou igual à data de início.',
            'events.array' => 'Os eventos devem ser uma lista válida.',
            'events.*.exists' => 'Um ou mais eventos selecionados não existem.',
        ]);

        DB::beginTransaction();
        try {
            $disponibilidade->update([
                'days_of_week' => $validated['days_of_week'] ?? null,
                'time_start' => $validated['time_start'] ?? null,
                'time_end' => $validated['time_end'] ?? null,
                'unavailable_start' => $validated['unavailable_start'] ?? null,
                'unavailable_end' => $validated['unavailable_end'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Sincronizar eventos
            if (isset($validated['events']) && is_array($validated['events'])) {
                $eventsData = [];
                foreach ($validated['events'] as $eventId) {
                    $eventsData[$eventId] = ['available' => true];
                }
                $disponibilidade->volunteer->availabilityEvents()->sync($eventsData);
            } else {
                $disponibilidade->volunteer->availabilityEvents()->detach();
            }

            DB::commit();

            return redirect()->route('voluntarios.disponibilidade.index')
                ->with('success', 'Disponibilidade atualizada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar disponibilidade: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VolunteerAvailability $disponibilidade)
    {
        $disponibilidade->delete();

        return redirect()->route('voluntarios.disponibilidade.index')
            ->with('success', 'Disponibilidade removida com sucesso!');
    }
}
