<?php

namespace App\Http\Controllers\Notificacoes;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoMensagem;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = ConfiguracaoMensagem::orderBy('tipo_notificacao')->orderByDesc('id')->get();
        $variaveis = ConfiguracaoMensagem::variaveisDisponiveis();
        return view('notificacoes.templates.index', compact('templates', 'variaveis'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_notificacao' => 'required|string|max:64|unique:configuracoes_mensagens,tipo_notificacao',
            'template' => 'required|string',
            'ativo' => 'boolean',
        ], [
            'tipo_notificacao.required' => 'O tipo do template é obrigatório.',
            'tipo_notificacao.max' => 'O tipo deve ter no máximo 64 caracteres.',
            'tipo_notificacao.unique' => 'Já existe um template cadastrado com esse tipo.',
            'template.required' => 'O conteúdo do template é obrigatório.',
        ]);

        ConfiguracaoMensagem::create([
            'tipo_notificacao' => $request->tipo_notificacao,
            'template' => $request->template,
            'ativo' => $request->boolean('ativo', true),
        ]);

        return back()->with('success', 'Template criado com sucesso.');
    }

    public function update(Request $request, ConfiguracaoMensagem $template)
    {
        $request->validate([
            'tipo_notificacao' => 'required|string|max:64|unique:configuracoes_mensagens,tipo_notificacao,' . $template->id,
            'template' => 'required|string',
            'ativo' => 'boolean',
        ], [
            'tipo_notificacao.required' => 'O tipo do template é obrigatório.',
            'tipo_notificacao.max' => 'O tipo deve ter no máximo 64 caracteres.',
            'tipo_notificacao.unique' => 'Já existe um template cadastrado com esse tipo.',
            'template.required' => 'O conteúdo do template é obrigatório.',
        ]);

        $template->update([
            'tipo_notificacao' => $request->tipo_notificacao,
            'template' => $request->template,
            'ativo' => $request->boolean('ativo', false),
        ]);

        return back()->with('success', 'Template atualizado com sucesso.');
    }

    public function destroy(ConfiguracaoMensagem $template)
    {
        $template->delete();

        return back()->with('success', 'Template removido com sucesso.');
    }
}
