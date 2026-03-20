<?php

namespace App\Http\Controllers\Notificacoes;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoMensagem;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = ConfiguracaoMensagem::orderBy('tipo_notificacao')->get();
        $tiposPadrao = ['dia_evento', 'lembrete', 'custom', 'rapida'];
        foreach ($tiposPadrao as $tipo) {
            if (!$templates->contains('tipo_notificacao', $tipo)) {
                $templates->push(new ConfiguracaoMensagem([
                    'tipo_notificacao' => $tipo,
                    'template' => "Olá {nome}! " . ($tipo === 'custom' ? 'Mensagem personalizada.' : 'Lembrete.'),
                    'ativo' => false,
                ]));
            }
        }
        $templates = $templates->sortBy('tipo_notificacao')->values();
        $variaveis = ConfiguracaoMensagem::variaveisDisponiveis();
        return view('notificacoes.templates.index', compact('templates', 'variaveis'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'tipo' => 'required|string|max:64',
            'template' => 'required|string',
            'ativo' => 'boolean',
        ]);

        ConfiguracaoMensagem::updateOrCreate(
            ['tipo_notificacao' => $request->tipo],
            [
                'template' => $request->template,
                'ativo' => $request->boolean('ativo', true),
            ]
        );

        return back()->with('success', 'Template atualizado com sucesso.');
    }
}
