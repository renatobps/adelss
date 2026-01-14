<?php

namespace App\Http\Controllers;

use App\Models\Volunteer;
use App\Models\Member;
use App\Models\ServiceArea;
use Illuminate\Http\Request;

class VolunteerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Volunteer::with(['member', 'serviceAreas']);

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

        // Filtro por nível de experiência
        if ($request->has('experience_level') && $request->experience_level) {
            $query->where('experience_level', $request->experience_level);
        }

        // Filtro por área de serviço
        if ($request->has('service_area_id') && $request->service_area_id) {
            $query->whereHas('serviceAreas', function($q) use ($request) {
                $q->where('service_areas.id', $request->service_area_id);
            });
        }

        $volunteers = $query->orderBy('created_at', 'desc')->paginate(15);
        $serviceAreas = ServiceArea::active()->orderBy('name')->get();

        return view('volunteers.index', compact('volunteers', 'serviceAreas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $members = Member::orderBy('name')->get();
        $serviceAreas = ServiceArea::active()->orderBy('name')->get();
        
        return view('volunteers.create', compact('members', 'serviceAreas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id|unique:volunteers,member_id',
            'experience_level' => 'required|in:novo,em_treinamento,experiente',
            'start_date' => 'required|date',
            'status' => 'required|in:ativo,inativo',
            'leader_notes' => 'nullable|string',
            'service_areas' => 'nullable|array',
            'service_areas.*' => 'exists:service_areas,id',
        ], [
            'member_id.required' => 'Selecione um membro.',
            'member_id.exists' => 'O membro selecionado não existe.',
            'member_id.unique' => 'Este membro já está cadastrado como voluntário.',
            'experience_level.required' => 'Selecione o nível de experiência.',
            'experience_level.in' => 'O nível de experiência deve ser: novo, em treinamento ou experiente.',
            'start_date.required' => 'A data de início é obrigatória.',
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'O status deve ser: ativo ou inativo.',
            'service_areas.array' => 'As áreas de serviço devem ser uma lista válida.',
            'service_areas.*.exists' => 'Uma ou mais áreas de serviço selecionadas não existem.',
        ]);

        $volunteer = Volunteer::create([
            'member_id' => $validated['member_id'],
            'experience_level' => $validated['experience_level'],
            'start_date' => $validated['start_date'],
            'status' => $validated['status'],
            'leader_notes' => $validated['leader_notes'] ?? null,
        ]);

        // Sincronizar áreas de serviço
        if (isset($validated['service_areas']) && is_array($validated['service_areas'])) {
            $volunteer->serviceAreas()->sync($validated['service_areas']);
        }

        return redirect()->route('voluntarios.cadastro.index')
            ->with('success', 'Voluntário cadastrado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Volunteer $volunteer)
    {
        $volunteer->load(['member', 'serviceAreas']);
        
        return view('volunteers.show', compact('volunteer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Volunteer $volunteer)
    {
        $members = Member::orderBy('name')->get();
        $serviceAreas = ServiceArea::active()->orderBy('name')->get();
        $volunteer->load('serviceAreas');
        
        return view('volunteers.edit', compact('volunteer', 'members', 'serviceAreas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Volunteer $volunteer)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id|unique:volunteers,member_id,' . $volunteer->id,
            'experience_level' => 'required|in:novo,em_treinamento,experiente',
            'start_date' => 'required|date',
            'status' => 'required|in:ativo,inativo',
            'leader_notes' => 'nullable|string',
            'service_areas' => 'nullable|array',
            'service_areas.*' => 'exists:service_areas,id',
        ], [
            'member_id.required' => 'Selecione um membro.',
            'member_id.exists' => 'O membro selecionado não existe.',
            'member_id.unique' => 'Este membro já está cadastrado como voluntário.',
            'experience_level.required' => 'Selecione o nível de experiência.',
            'experience_level.in' => 'O nível de experiência deve ser: novo, em treinamento ou experiente.',
            'start_date.required' => 'A data de início é obrigatória.',
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'O status deve ser: ativo ou inativo.',
            'service_areas.array' => 'As áreas de serviço devem ser uma lista válida.',
            'service_areas.*.exists' => 'Uma ou mais áreas de serviço selecionadas não existem.',
        ]);

        $volunteer->update([
            'member_id' => $validated['member_id'],
            'experience_level' => $validated['experience_level'],
            'start_date' => $validated['start_date'],
            'status' => $validated['status'],
            'leader_notes' => $validated['leader_notes'] ?? null,
        ]);

        // Sincronizar áreas de serviço
        if (isset($validated['service_areas']) && is_array($validated['service_areas'])) {
            $volunteer->serviceAreas()->sync($validated['service_areas']);
        } else {
            $volunteer->serviceAreas()->detach();
        }

        return redirect()->route('voluntarios.cadastro.index')
            ->with('success', 'Voluntário atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Volunteer $volunteer)
    {
        $volunteer->delete();

        return redirect()->route('voluntarios.cadastro.index')
            ->with('success', 'Voluntário removido com sucesso!');
    }
}