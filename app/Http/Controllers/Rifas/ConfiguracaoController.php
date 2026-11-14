<?php

namespace App\Http\Controllers\Rifas;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoMensagem;
use App\Models\Rifa;
use App\Services\AuditLogger;
use App\Services\RifaCartelaMensagemService;
use Illuminate\Http\Request;

class ConfiguracaoController extends Controller
{
    public function edit(RifaCartelaMensagemService $mensagens)
    {
        $this->authorize('update', new Rifa());
        return view('rifas.configuracao', [
            'mensagem' => $mensagens->cadastrada(), 'variaveis' => $mensagens->variaveis(),
        ]);
    }

    public function update(Request $request, RifaCartelaMensagemService $mensagens)
    {
        $this->authorize('update', new Rifa());
        $dados = $request->validate([
            'template' => ['required', 'string', 'max:2000', function ($attribute, $value, $fail) use ($mensagens) {
                preg_match_all('/\{[a-z_]+\}/i', $value, $matches);
                $invalidas = array_diff($matches[0], array_keys($mensagens->variaveis()));
                if ($invalidas) $fail('Variáveis não reconhecidas: '.implode(', ', array_unique($invalidas)).'. Use as opções exibidas na tela.');
            }],
        ], ['template.required' => 'Escreva a mensagem que deseja cadastrar.', 'template.max' => 'A mensagem deve ter no máximo 2.000 caracteres.']);
        ConfiguracaoMensagem::updateOrCreate(['tipo_notificacao' => RifaCartelaMensagemService::TIPO], ['template' => $dados['template'], 'ativo' => true]);
        AuditLogger::log('rifas', 'message_updated', 'Mensagem de envio de cartelas atualizada.');
        return redirect()->route('rifas.configuracao.edit')->with('success', 'Mensagem cadastrada. Você poderá escolhê-la antes de enviar as cartelas.');
    }
}
