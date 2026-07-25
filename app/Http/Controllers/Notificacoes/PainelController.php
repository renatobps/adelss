<?php

namespace App\Http\Controllers\Notificacoes;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Member;
use App\Models\NotificacaoEnviada;
use App\Services\AuditLogger;
use App\Services\NotificacaoService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 50, 100], true)) {
            $perPage = 10;
        }

        $notificacoes = $query->orderByDesc('data_envio')
            ->paginate($perPage)
            ->withQueryString();

        $ultimoMes = now()->subDays(30);
        $stats = [
            'enviadas' => NotificacaoEnviada::where('status', 'enviada')->where('data_envio', '>=', $ultimoMes)->count(),
            'erros' => NotificacaoEnviada::where('status', 'erro')->where('data_envio', '>=', $ultimoMes)->count(),
            'total' => NotificacaoEnviada::where('data_envio', '>=', $ultimoMes)->count(),
        ];

        $members = Member::active()->whereNotNull('phone')->where('phone', '!=', '')->orderBy('name')->get(['id', 'name', 'phone']);
        $departments = Department::active()->orderBy('name')->get(['id', 'name']);
        $canManageHistorico = $request->user()?->can('notificacoes.historico.manage') ?? false;

        return view('notificacoes.painel.index', compact(
            'notificacoes',
            'stats',
            'members',
            'departments',
            'perPage',
            'canManageHistorico'
        ));
    }

    public function limparHistorico(Request $request)
    {
        $this->authorize('notificacoes.historico.manage');

        $data = $request->validate([
            'modo' => ['required', Rule::in(['tudo', 'antes_hoje', 'antes_data'])],
            'data_limite' => ['nullable', 'date', 'required_if:modo,antes_data'],
            'confirmacao' => ['required', 'string', 'in:EXCLUIR'],
        ], [
            'confirmacao.in' => 'Digite EXCLUIR para confirmar a exclusão.',
            'data_limite.required_if' => 'Informe a data limite para exclusão.',
        ]);

        $query = NotificacaoEnviada::query();
        $descricao = '';

        if ($data['modo'] === 'tudo') {
            $descricao = 'Excluir todo o histórico';
        } elseif ($data['modo'] === 'antes_hoje') {
            $query->whereDate('data_envio', '<', now()->toDateString());
            $descricao = 'Excluir histórico anterior a hoje';
        } else {
            $query->whereDate('data_envio', '<', $data['data_limite']);
            $descricao = 'Excluir histórico anterior a '.$data['data_limite'];
        }

        $apagados = (clone $query)->count();
        $query->delete();

        AuditLogger::log('notificacoes', 'historico.limpar', $descricao, [
            'modo' => $data['modo'],
            'data_limite' => $data['data_limite'] ?? null,
            'registros_apagados' => $apagados,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            $ultimoMes = now()->subDays(30);

            return response()->json([
                'success' => true,
                'message' => $apagados === 1
                    ? '1 registro removido do histórico.'
                    : "{$apagados} registros removidos do histórico.",
                'apagados' => $apagados,
                'stats' => [
                    'enviadas' => NotificacaoEnviada::where('status', 'enviada')->where('data_envio', '>=', $ultimoMes)->count(),
                    'erros' => NotificacaoEnviada::where('status', 'erro')->where('data_envio', '>=', $ultimoMes)->count(),
                    'total' => NotificacaoEnviada::where('data_envio', '>=', $ultimoMes)->count(),
                ],
            ]);
        }

        return redirect()
            ->route('notificacoes.painel.index')
            ->with('success', $apagados === 1
                ? '1 registro removido do histórico.'
                : "{$apagados} registros removidos do histórico.");
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

        if (! empty($memberIds)) {
            $members = Member::whereIn('id', $memberIds)->get();
            $r = $isEnvioMidia
                ? $service->enviarMidiaParaMembros($members, $arquivo, null, $mensagem)
                : $service->enviarParaMembros($members, $mensagem);
            $enviadas += $r['enviadas'];
            $erros += $r['erros'];
            $detalhesErros = array_merge($detalhesErros, $r['detalhes_erros'] ?? []);
        }
        if (! empty($departmentIds)) {
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
