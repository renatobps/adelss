<?php

namespace App\Http\Controllers\Discipleship;

use App\Http\Controllers\Controller;
use App\Models\Discipleship\DiscipleshipCycle;
use App\Models\Discipleship\DiscipleshipMember;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;

class DiscipleshipMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cycleId = $request->get('cycle_id');
        $status = $request->get('status', 'ativo');
        
        $query = DiscipleshipMember::with(['member', 'cycle', 'discipulador']);
        
        if ($cycleId) {
            $query->where('cycle_id', $cycleId);
        }
        
        if ($status === 'concluido') {
            $query->concluidos();
        } else {
            $query->ativos();
        }
        
        $members = $query->orderBy('data_inicio', 'desc')->paginate(15);
        
        $cycles = DiscipleshipCycle::ativos()->orderBy('nome')->get();
        
        return view('discipleship.members.index', compact('members', 'cycles', 'cycleId', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $cycleId = $request->get('cycle_id');
        $cycles = DiscipleshipCycle::ativos()->orderBy('nome')->get();
        $members = Member::active()->orderBy('name')->get();
        $discipuladores = User::orderBy('name')->get();
        
        return view('discipleship.members.create', compact('cycles', 'members', 'discipuladores', 'cycleId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cycle_id' => 'required|exists:discipleship_cycles,id',
            'member_id' => 'required|exists:members,id',
            'discipulador_id' => 'nullable|exists:users,id',
            'status' => 'required|in:ativo,concluido,pausado',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
        ], [
            'cycle_id.required' => 'O ciclo é obrigatório.',
            'member_id.required' => 'O membro é obrigatório.',
            'data_inicio.required' => 'A data de início é obrigatória.',
            'data_fim.after_or_equal' => 'A data de fim deve ser igual ou posterior à data de início.',
        ]);

        // Verificar se já existe vínculo ativo
        $exists = DiscipleshipMember::where('cycle_id', $validated['cycle_id'])
            ->where('member_id', $validated['member_id'])
            ->where('status', 'ativo')
            ->exists();
            
        if ($exists) {
            return back()->withErrors(['member_id' => 'Este membro já está vinculado a este ciclo.'])->withInput();
        }
        
        DiscipleshipMember::create($validated);

        return redirect()->route('discipleship.members.index', ['cycle_id' => $validated['cycle_id']])
            ->with('success', 'Membro vinculado ao ciclo com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(DiscipleshipMember $member)
    {
        $member->load([
            'member',
            'cycle',
            'discipulador',
            'meetings' => function($query) {
                $query->recentes()->with('goals');
            },
            'indicatorValues.indicator',
            'goals',
            'feedbacks.autor'
        ]);

        $meetingsForChart = $member->meetings()->orderBy('data', 'asc')->get();
        $chartData = $meetingsForChart->map(function ($m) {
            return [
                'data' => $m->data->format('d/m/Y'),
                'label' => $m->data->format('d/m'),
                'oracao_min' => $m->oracao_tempo_numero,
                'jejum_horas' => $m->jejum_horas_numero,
                'leitura_cap' => $m->leitura_capitulos_numero,
            ];
        })->values()->toArray();
        
        return view('discipleship.members.show', compact('member', 'chartData'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DiscipleshipMember $member)
    {
        $cycles = DiscipleshipCycle::ativos()->orderBy('nome')->get();
        $members = Member::active()->orderBy('name')->get();
        $discipuladores = User::orderBy('name')->get();
        
        return view('discipleship.members.edit', compact('member', 'cycles', 'members', 'discipuladores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DiscipleshipMember $member)
    {
        $validated = $request->validate([
            'cycle_id' => 'required|exists:discipleship_cycles,id',
            'member_id' => 'required|exists:members,id',
            'discipulador_id' => 'nullable|exists:users,id',
            'status' => 'required|in:ativo,concluido,pausado',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
        ], [
            'cycle_id.required' => 'O ciclo é obrigatório.',
            'member_id.required' => 'O membro é obrigatório.',
            'data_inicio.required' => 'A data de início é obrigatória.',
            'data_fim.after_or_equal' => 'A data de fim deve ser igual ou posterior à data de início.',
        ]);

        $member->update($validated);

        return redirect()->route('discipleship.members.show', $member)
            ->with('success', 'Vínculo atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DiscipleshipMember $member)
    {
        $cycleId = $member->cycle_id;
        $member->delete();

        return redirect()->route('discipleship.members.index', ['cycle_id' => $cycleId])
            ->with('success', 'Vínculo removido com sucesso!');
    }
}
