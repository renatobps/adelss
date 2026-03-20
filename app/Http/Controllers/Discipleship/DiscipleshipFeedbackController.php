<?php

namespace App\Http\Controllers\Discipleship;

use App\Http\Controllers\Controller;
use App\Models\Discipleship\DiscipleshipFeedback;
use App\Models\Discipleship\DiscipleshipMember;
use Illuminate\Http\Request;

class DiscipleshipFeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $memberId = $request->get('discipleship_member_id');
        
        $query = DiscipleshipFeedback::with(['discipleshipMember.member', 'autor']);
        
        if ($memberId) {
            $query->where('discipleship_member_id', $memberId);
        }
        
        $feedbacks = $query->recentes()->paginate(15);
        
        return view('discipleship.feedbacks.index', compact('feedbacks', 'memberId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $memberId = $request->get('discipleship_member_id');
        $members = DiscipleshipMember::ativos()->with('member')->get();
        
        return view('discipleship.feedbacks.create', compact('members', 'memberId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'discipleship_member_id' => 'required|exists:discipleship_members,id',
            'visibilidade' => 'required|in:discipulador,pastor,admin',
            'conteudo' => 'required|string',
        ], [
            'discipleship_member_id.required' => 'O membro é obrigatório.',
            'visibilidade.required' => 'A visibilidade é obrigatória.',
            'conteudo.required' => 'O conteúdo é obrigatório.',
        ]);

        $validated['autor_id'] = auth()->id();
        
        DiscipleshipFeedback::create($validated);

        return redirect()->route('discipleship.feedbacks.index', ['discipleship_member_id' => $validated['discipleship_member_id']])
            ->with('success', 'Feedback registrado com sucesso!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DiscipleshipFeedback $feedback)
    {
        $members = DiscipleshipMember::ativos()->with('member')->get();
        
        return view('discipleship.feedbacks.edit', compact('feedback', 'members'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DiscipleshipFeedback $feedback)
    {
        $validated = $request->validate([
            'discipleship_member_id' => 'required|exists:discipleship_members,id',
            'visibilidade' => 'required|in:discipulador,pastor,admin',
            'conteudo' => 'required|string',
        ], [
            'discipleship_member_id.required' => 'O membro é obrigatório.',
            'visibilidade.required' => 'A visibilidade é obrigatória.',
            'conteudo.required' => 'O conteúdo é obrigatório.',
        ]);

        $feedback->update($validated);

        return redirect()->route('discipleship.feedbacks.index', ['discipleship_member_id' => $feedback->discipleship_member_id])
            ->with('success', 'Feedback atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DiscipleshipFeedback $feedback)
    {
        $memberId = $feedback->discipleship_member_id;
        $feedback->delete();

        return redirect()->route('discipleship.feedbacks.index', ['discipleship_member_id' => $memberId])
            ->with('success', 'Feedback excluído com sucesso!');
    }
}
