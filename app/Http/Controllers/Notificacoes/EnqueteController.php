<?php

namespace App\Http\Controllers\Notificacoes;

use App\Http\Controllers\Controller;
use App\Models\Enquete;
use App\Models\Department;
use App\Models\Member;
use App\Services\EnqueteService;
use Illuminate\Http\Request;

class EnqueteController extends Controller
{
    public function index()
    {
        $enquetes = Enquete::withCount('respostas')
            ->orderByDesc('created_at')
            ->paginate(10);
        return view('notificacoes.enquetes.index', compact('enquetes'));
    }

    public function create()
    {
        return view('notificacoes.enquetes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'opcoes' => 'required|array|min:2',
            'opcoes.*' => 'required|string|max:255',
            'inicio_em' => 'nullable|date',
            'fim_em' => 'nullable|date|after_or_equal:inicio_em',
        ]);

        Enquete::create([
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'opcoes' => array_values($request->opcoes),
            'inicio_em' => $request->inicio_em,
            'fim_em' => $request->fim_em,
            'ativa' => true,
        ]);

        return redirect()->route('notificacoes.enquetes.index')
            ->with('success', 'Enquete criada com sucesso.');
    }

    public function show(Enquete $enquete)
    {
        $enquete->loadCount('respostas');
        $respostas = $enquete->respostas()->with('member:id,name')->latest('respondido_em')->paginate(20);
        $members = Member::active()->orderBy('name')->get(['id', 'name']);
        $departments = Department::active()->orderBy('name')->get(['id', 'name']);

        $estatisticas = [];
        $total = $enquete->respostas()->count();
        foreach ($enquete->opcoes ?? [] as $opcao) {
            $count = $enquete->respostas()->where('resposta', $opcao)->count();
            $estatisticas[$opcao] = [
                'count' => $count,
                'percentage' => $total > 0 ? round($count / $total * 100, 1) : 0,
            ];
        }

        return view('notificacoes.enquetes.show', compact('enquete', 'respostas', 'members', 'departments', 'estatisticas'));
    }

    public function edit(Enquete $enquete)
    {
        return view('notificacoes.enquetes.edit', compact('enquete'));
    }

    public function update(Request $request, Enquete $enquete)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'opcoes' => 'required|array|min:2',
            'opcoes.*' => 'required|string|max:255',
            'inicio_em' => 'nullable|date',
            'fim_em' => 'nullable|date|after_or_equal:inicio_em',
            'ativa' => 'boolean',
        ]);

        $enquete->update([
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'opcoes' => array_values($request->opcoes),
            'inicio_em' => $request->inicio_em,
            'fim_em' => $request->fim_em,
            'ativa' => $request->boolean('ativa', true),
        ]);

        return redirect()->route('notificacoes.enquetes.show', $enquete)
            ->with('success', 'Enquete atualizada com sucesso.');
    }

    public function destroy(Enquete $enquete)
    {
        $enquete->delete();
        return redirect()->route('notificacoes.enquetes.index')
            ->with('success', 'Enquete excluída com sucesso.');
    }

    public function enviar(Request $request, Enquete $enquete)
    {
        $request->validate([
            'members' => 'nullable|array',
            'members.*' => 'integer|exists:members,id',
            'departments' => 'nullable|array',
            'departments.*' => 'integer|exists:departments,id',
        ]);

        $memberIds = $request->input('members');
        $departmentIds = $request->input('departments');
        if (empty($memberIds) && empty($departmentIds)) {
            return back()->withErrors(['destinatarios' => 'Selecione pelo menos um membro ou departamento.'])->withInput();
        }

        $totais = app(EnqueteService::class)->enviarEnquete($enquete, $memberIds, $departmentIds);
        return back()->with('success', "Envio concluído: {$totais['enviadas']} enviadas, {$totais['erros']} erros.");
    }
}
