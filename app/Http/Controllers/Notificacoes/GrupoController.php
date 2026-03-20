<?php

namespace App\Http\Controllers\Notificacoes;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\NotificacaoGrupo;
use Illuminate\Http\Request;

class GrupoController extends Controller
{
    public function index()
    {
        $grupos = NotificacaoGrupo::withCount('members')
            ->with(['members:id,name,phone'])
            ->orderBy('nome')
            ->paginate(15);
        return view('notificacoes.grupos.index', compact('grupos'));
    }

    public function create()
    {
        $members = Member::active()->orderBy('name')->get(['id', 'name', 'phone']);
        return view('notificacoes.grupos.create', compact('members'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:120',
            'descricao' => 'nullable|string',
            'members' => 'nullable|array',
            'members.*' => 'integer|exists:members,id',
        ]);

        $grupo = NotificacaoGrupo::create([
            'nome' => $data['nome'],
            'descricao' => $data['descricao'] ?? null,
            'ativo' => $request->boolean('ativo', true),
        ]);

        if (!empty($data['members'])) {
            $grupo->members()->sync($data['members']);
        }

        return redirect()->route('notificacoes.grupos.index')
            ->with('success', 'Grupo criado com sucesso.');
    }

    public function edit(NotificacaoGrupo $grupo)
    {
        $members = Member::active()->orderBy('name')->get(['id', 'name', 'phone']);
        $selecionados = $grupo->members()->pluck('id')->toArray();
        return view('notificacoes.grupos.edit', compact('grupo', 'members', 'selecionados'));
    }

    public function update(Request $request, NotificacaoGrupo $grupo)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:120',
            'descricao' => 'nullable|string',
            'members' => 'nullable|array',
            'members.*' => 'integer|exists:members,id',
        ]);

        $grupo->update([
            'nome' => $data['nome'],
            'descricao' => $data['descricao'] ?? null,
            'ativo' => $request->boolean('ativo', true),
        ]);

        $grupo->members()->sync($data['members'] ?? []);

        return redirect()->route('notificacoes.grupos.index')
            ->with('success', 'Grupo atualizado com sucesso.');
    }

    public function destroy(NotificacaoGrupo $grupo)
    {
        $grupo->delete();
        return redirect()->route('notificacoes.grupos.index')
            ->with('success', 'Grupo excluído com sucesso.');
    }
}
