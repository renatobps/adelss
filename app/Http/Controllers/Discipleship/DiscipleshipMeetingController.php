<?php

namespace App\Http\Controllers\Discipleship;

use App\Http\Controllers\Controller;
use App\Models\Discipleship\DiscipleshipGoal;
use App\Models\Discipleship\DiscipleshipMember;
use App\Models\Discipleship\DiscipleshipMeeting;
use Illuminate\Http\Request;

class DiscipleshipMeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $memberId = $request->get('discipleship_member_id');
        
        $query = DiscipleshipMeeting::with(['discipleshipMember.member', 'discipleshipMember.cycle']);
        
        if ($memberId) {
            $query->where('discipleship_member_id', $memberId);
        }
        
        $meetings = $query->recentes()->paginate(15);
        
        return view('discipleship.meetings.index', compact('meetings', 'memberId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $memberId = $request->get('discipleship_member_id');
        $members = DiscipleshipMember::ativos()->with('member')->get();
        $goalsByMember = DiscipleshipGoal::whereIn('discipleship_member_id', $members->pluck('id'))
            ->whereIn('status', ['em_andamento', 'pausado'])
            ->get()
            ->groupBy('discipleship_member_id');
        
        return view('discipleship.meetings.create', compact('members', 'memberId', 'goalsByMember'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'discipleship_member_id' => 'required|exists:discipleship_members,id',
            'data' => 'required|date',
            'tipo' => 'required|in:presencial,online',
            'assuntos_tratados' => 'nullable|string',
            'observacoes_privadas' => 'nullable|string',
            'proximo_passo' => 'nullable|string',
            'oracao_tempo_dia' => 'required|string|in:0,5,10,15,20,25,30,35,40,45,50,55,60,mais_1h',
            'oracao_como_sao' => 'required|string',
            'oracao_observacoes' => 'nullable|string',
            'jejum_horas_semana' => 'nullable|string|in:0,6,12,18,24,mais_24',
            'jejum_tipo' => 'required|string|in:total,parcial,nenhum',
            'jejum_com_proposito' => 'required|string|in:sim,nao',
            'jejum_observacoes' => 'nullable|string',
            'leitura_capitulos_dia' => 'required|string|in:0,1,2,3,4,5,6,7,8,9,10,mais_10',
            'leitura_estuda' => 'required|string|in:sim,nao',
            'leitura_observacoes' => 'nullable|string',
        ], [
            'discipleship_member_id.required' => 'O membro é obrigatório.',
            'data.required' => 'A data é obrigatória.',
            'tipo.required' => 'O tipo de encontro é obrigatório.',
            'oracao_tempo_dia.required' => 'Informe quanto tempo tem orado por dia.',
            'oracao_como_sao.required' => 'Descreva como são suas orações.',
            'jejum_tipo.required' => 'Informe se tem feito jejum.',
            'jejum_com_proposito.required' => 'Informe se seu jejum é com propósito.',
            'leitura_capitulos_dia.required' => 'Informe quantos capítulos tem lido por dia.',
            'leitura_estuda.required' => 'Informe se estuda os capítulos que lê.',
        ]);

        $meeting = DiscipleshipMeeting::create($validated);
        $goalIds = $request->input('goal_ids', []);
        if (!empty($goalIds)) {
            $meeting->goals()->sync($goalIds);
        }

        return redirect()->route('discipleship.meetings.index', ['discipleship_member_id' => $validated['discipleship_member_id']])
            ->with('success', 'Encontro registrado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(DiscipleshipMeeting $meeting)
    {
        $meeting->load(['discipleshipMember.member', 'discipleshipMember.cycle', 'goals']);
        
        return view('discipleship.meetings.show', compact('meeting'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DiscipleshipMeeting $meeting)
    {
        $members = DiscipleshipMember::ativos()->with('member')->get();
        $goalsByMember = DiscipleshipGoal::whereIn('discipleship_member_id', $members->pluck('id'))
            ->whereIn('status', ['em_andamento', 'pausado'])
            ->get()
            ->groupBy('discipleship_member_id');
        
        return view('discipleship.meetings.edit', compact('meeting', 'members', 'goalsByMember'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DiscipleshipMeeting $meeting)
    {
        $validated = $request->validate([
            'discipleship_member_id' => 'required|exists:discipleship_members,id',
            'data' => 'required|date',
            'tipo' => 'required|in:presencial,online',
            'assuntos_tratados' => 'nullable|string',
            'observacoes_privadas' => 'nullable|string',
            'proximo_passo' => 'nullable|string',
            'oracao_tempo_dia' => 'required|string|in:0,5,10,15,20,25,30,35,40,45,50,55,60,mais_1h',
            'oracao_como_sao' => 'required|string',
            'oracao_observacoes' => 'nullable|string',
            'jejum_horas_semana' => 'nullable|string|in:0,6,12,18,24,mais_24',
            'jejum_tipo' => 'required|string|in:total,parcial,nenhum',
            'jejum_com_proposito' => 'required|string|in:sim,nao',
            'jejum_observacoes' => 'nullable|string',
            'leitura_capitulos_dia' => 'required|string|in:0,1,2,3,4,5,6,7,8,9,10,mais_10',
            'leitura_estuda' => 'required|string|in:sim,nao',
            'leitura_observacoes' => 'nullable|string',
        ], [
            'discipleship_member_id.required' => 'O membro é obrigatório.',
            'data.required' => 'A data é obrigatória.',
            'tipo.required' => 'O tipo de encontro é obrigatório.',
            'oracao_tempo_dia.required' => 'Informe quanto tempo tem orado por dia.',
            'oracao_como_sao.required' => 'Descreva como são suas orações.',
            'jejum_tipo.required' => 'Informe se tem feito jejum.',
            'jejum_com_proposito.required' => 'Informe se seu jejum é com propósito.',
            'leitura_capitulos_dia.required' => 'Informe quantos capítulos tem lido por dia.',
            'leitura_estuda.required' => 'Informe se estuda os capítulos que lê.',
        ]);

        $meeting->update($validated);
        $meeting->goals()->sync($request->input('goal_ids', []));

        return redirect()->route('discipleship.meetings.show', $meeting)
            ->with('success', 'Encontro atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DiscipleshipMeeting $meeting)
    {
        $memberId = $meeting->discipleship_member_id;
        $meeting->delete();

        return redirect()->route('discipleship.meetings.index', ['discipleship_member_id' => $memberId])
            ->with('success', 'Encontro excluído com sucesso!');
    }
}
