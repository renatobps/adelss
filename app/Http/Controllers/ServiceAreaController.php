<?php

namespace App\Http\Controllers;

use App\Models\ServiceArea;
use App\Models\Member;
use Illuminate\Http\Request;

class ServiceAreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ServiceArea::with('leader');

        // Busca
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filtro por status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filtro por público permitido
        if ($request->has('allowed_audience') && $request->allowed_audience) {
            $query->where('allowed_audience', $request->allowed_audience);
        }

        $serviceAreas = $query->orderBy('name')->paginate(15);

        return view('service-areas.index', compact('serviceAreas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $members = Member::orderBy('name')->get();
        
        return view('service-areas.create', compact('members'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:ativo,inativo',
            'leader_id' => 'nullable|exists:members,id',
            'min_quantity' => 'required|integer|min:1',
            'allowed_audience' => 'required|in:adulto,jovem,ambos',
        ], [
            'name.required' => 'O campo nome da área é obrigatório.',
            'name.string' => 'O nome da área deve ser um texto.',
            'name.max' => 'O nome da área não pode ter mais de 255 caracteres.',
            'status.required' => 'O campo status é obrigatório.',
            'status.in' => 'O status deve ser: ativo ou inativo.',
            'leader_id.exists' => 'O responsável selecionado não existe.',
            'min_quantity.required' => 'A quantidade mínima é obrigatória.',
            'min_quantity.integer' => 'A quantidade mínima deve ser um número inteiro.',
            'min_quantity.min' => 'A quantidade mínima deve ser pelo menos 1.',
            'allowed_audience.required' => 'O campo público permitido é obrigatório.',
            'allowed_audience.in' => 'O público permitido deve ser: adulto, jovem ou ambos.',
        ]);

        ServiceArea::create($validated);

        return redirect()->route('voluntarios.areas.index')
            ->with('success', 'Área de serviço cadastrada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceArea $area)
    {
        $area->load('leader', 'volunteers.member');
        
        return view('service-areas.show', compact('area'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceArea $area)
    {
        $members = Member::orderBy('name')->get();
        
        return view('service-areas.edit', compact('area', 'members'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceArea $area)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:ativo,inativo',
            'leader_id' => 'nullable|exists:members,id',
            'min_quantity' => 'required|integer|min:1',
            'allowed_audience' => 'required|in:adulto,jovem,ambos',
        ], [
            'name.required' => 'O campo nome da área é obrigatório.',
            'name.string' => 'O nome da área deve ser um texto.',
            'name.max' => 'O nome da área não pode ter mais de 255 caracteres.',
            'status.required' => 'O campo status é obrigatório.',
            'status.in' => 'O status deve ser: ativo ou inativo.',
            'leader_id.exists' => 'O responsável selecionado não existe.',
            'min_quantity.required' => 'A quantidade mínima é obrigatória.',
            'min_quantity.integer' => 'A quantidade mínima deve ser um número inteiro.',
            'min_quantity.min' => 'A quantidade mínima deve ser pelo menos 1.',
            'allowed_audience.required' => 'O campo público permitido é obrigatório.',
            'allowed_audience.in' => 'O público permitido deve ser: adulto, jovem ou ambos.',
        ]);

        $area->update($validated);

        return redirect()->route('voluntarios.areas.index')
            ->with('success', 'Área de serviço atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceArea $area)
    {
        $area->delete();

        return redirect()->route('voluntarios.areas.index')
            ->with('success', 'Área de serviço removida com sucesso!');
    }
}
