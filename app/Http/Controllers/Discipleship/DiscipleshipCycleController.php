<?php

namespace App\Http\Controllers\Discipleship;

use App\Http\Controllers\Controller;
use App\Models\Discipleship\DiscipleshipCycle;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DiscipleshipCycleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', DiscipleshipCycle::class);
        $status = $request->get('status', 'ativo');
        
        $query = DiscipleshipCycle::with(['creator', 'members.member']);
        
        if ($status === 'encerrado') {
            $query->encerrados();
        } else {
            $query->ativos();
        }
        
        $cycles = $query->orderBy('data_inicio', 'desc')->get();
        
        return view('discipleship.cycles.index', compact('cycles', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', DiscipleshipCycle::class);
        return view('discipleship.cycles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', DiscipleshipCycle::class);
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'status' => 'required|in:ativo,encerrado',
        ], [
            'nome.required' => 'O campo nome é obrigatório.',
            'data_inicio.required' => 'A data de início é obrigatória.',
            'data_fim.after_or_equal' => 'A data de fim deve ser igual ou posterior à data de início.',
        ]);

        $validated['created_by'] = auth()->id();
        
        DiscipleshipCycle::create($validated);

        return redirect()->route('discipleship.cycles.index')
            ->with('success', 'Ciclo de discipulado criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(DiscipleshipCycle $cycle)
    {
        $this->authorize('view', $cycle);
        $cycle->load(['members.member', 'members.discipulador', 'members.meetings', 'creator']);
        
        return view('discipleship.cycles.show', compact('cycle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DiscipleshipCycle $cycle)
    {
        $this->authorize('update', $cycle);
        return view('discipleship.cycles.edit', compact('cycle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DiscipleshipCycle $cycle)
    {
        $this->authorize('update', $cycle);
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'status' => 'required|in:ativo,encerrado',
        ], [
            'nome.required' => 'O campo nome é obrigatório.',
            'data_inicio.required' => 'A data de início é obrigatória.',
            'data_fim.after_or_equal' => 'A data de fim deve ser igual ou posterior à data de início.',
        ]);

        $cycle->update($validated);

        return redirect()->route('discipleship.cycles.index')
            ->with('success', 'Ciclo de discipulado atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DiscipleshipCycle $cycle)
    {
        $this->authorize('delete', $cycle);
        $cycle->delete();

        return redirect()->route('discipleship.cycles.index')
            ->with('success', 'Ciclo de discipulado excluído com sucesso!');
    }
}
