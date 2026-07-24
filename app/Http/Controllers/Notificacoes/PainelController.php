<?php

namespace App\Http\Controllers\Notificacoes;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Member;
use App\Models\NotificacaoEnviada;
use App\Services\NotificacaoService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class PainelController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('notificacoes.view');
        $query = NotificacaoEnviada::query()->with('member:id,name,phone');

        if ($request->filled('status') && array_key_exists($request->status, NotificacaoEnviada::STATUSES)) {
            $query->where('status', $request->status);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('data_envio', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('data_envio', '<=', $request->data_fim);
        }

        $notificacoes = $query->orderByDesc('data_envio')->paginate(15);

        $ultimoMes = now()->subDays(30);
        $stats = [
            'enviadas' => NotificacaoEnviada::where('status', 'enviada')->where('data_envio', '>=', $ultimoMes)->count(),
            'erros' => NotificacaoEnviada::where('status', 'erro')->where('data_envio', '>=', $ultimoMes)->count(),
            'total' => NotificacaoEnviada::where('data_envio', '>=', $ultimoMes)->count(),
        ];

        $members = Member::active()->whereNotNull('phone')->where('phone', '!=', '')->orderBy('name')->get(['id', 'name', 'phone']);
        $departments = Department::active()->orderBy('name')->get(['id', 'name']);

        return view('notificacoes.painel.index', compact('notificacoes', 'stats', 'members', 'departments'));
    }

    public function enviar(Request $request)
    {
        $this->authorize('notificacoes.manage');
        $request->validate([
            'mensagem' => 'nullable|string|max:4096|required_without:arquivo',
            'members' => 'nullable|array',
            'members.*' => 'integer|exists:members,id',
            'departments' => 'nullable|array',
            'departments.*' => 'integer|exists:departments,id',
            'telefones_manual' => 'nullable|string|max:5000',
            'arquivo' => 'nullable|file|max:20480|required_without:mensagem',
        ]);

        $mensagem = (string) $request->input('mensagem', '');
        $memberIds = $request->input('members', []);
        $departmentIds = $request->input('departments', []);
        $telefonesManuais = $this->parseTelefonesManuais($request->input('telefones_manual'));
        $arquivo = $request->file('arquivo');
        $isEnvioMidia = $arquivo !== null;

        if (empty($memberIds) && empty($departmentIds) && empty($telefonesManuais)) {
            $rawManual = trim((string) $request->input('telefones_manual', ''));
            if ($rawManual !== '') {
                return back()->withErrors(['telefones_manual' => 'Nenhum telefone válido encontrado. Use DDD + número (ex.: 61999999999), um por linha ou separados por vírgula.'])->withInput();
            }

            return back()->withErrors(['destinatarios' => 'Selecione pelo menos um membro, um departamento ou informe um telefone.'])->withInput();
        }

        $service = app(NotificacaoService::class);
        $enviadas = 0;
        $erros = 0;
        $detalhesErros = [];

        if (!empty($memberIds)) {
            $members = Member::whereIn('id', $memberIds)->get();
            $r = $isEnvioMidia
                ? $service->enviarMidiaParaMembros($members, $arquivo, null, $mensagem)
                : $service->enviarParaMembros($members, $mensagem);
            $enviadas += $r['enviadas'];
            $erros += $r['erros'];
            $detalhesErros = array_merge($detalhesErros, $r['detalhes_erros'] ?? []);
        }
        if (!empty($departmentIds)) {
            foreach (Department::whereIn('id', $departmentIds)->get() as $department) {
                $r = $isEnvioMidia
                    ? $service->enviarMidiaParaDepartamento($department, $arquivo, null, $mensagem)
                    : $service->enviarParaDepartamento($department, $mensagem);
                $enviadas += $r['enviadas'];
                $erros += $r['erros'];
                $detalhesErros = array_merge($detalhesErros, $r['detalhes_erros'] ?? []);
            }
        }
        if (! empty($telefonesManuais)) {
            $r = $isEnvioMidia
                ? $service->enviarMidiaParaTelefonesManuais($telefonesManuais, $arquivo, null, $mensagem)
                : $service->enviarParaTelefonesManuais($telefonesManuais, $mensagem);
            $enviadas += $r['enviadas'];
            $erros += $r['erros'];
            $detalhesErros = array_merge($detalhesErros, $r['detalhes_erros'] ?? []);
        }

        $detalhesErros = array_values(array_unique(array_slice($detalhesErros, 0, 10)));

        if ($erros > 0 && $enviadas === 0) {
            return back()
                ->with('error', "Falha no envio: {$erros} erro(s).")
                ->with('envio_erros', $detalhesErros);
        }

        if ($erros > 0) {
            return back()
                ->with('warning', "Envio parcial: {$enviadas} enviada(s), {$erros} erro(s).")
                ->with('envio_erros', $detalhesErros);
        }

        return back()->with('success', "Envio concluído: {$enviadas} enviada(s).");
    }

    /**
     * Extrai números únicos a partir de texto (linhas, vírgulas ou ponto e vírgula).
     * Só retorna entradas que, após normalização, tenham tamanho mínimo para BR (55 + DDD + número).
     *
     * @return list<string>
     */
    private function parseTelefonesManuais(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $raw);
        $parts = preg_split('/[\n,;]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $seen = [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $n = WhatsAppService::normalizarNumero($part);
            if (strlen($n) < 12) {
                continue;
            }
            if (! isset($seen[$n])) {
                $seen[$n] = true;
                $out[] = $part;
            }
        }

        return $out;
    }
}
