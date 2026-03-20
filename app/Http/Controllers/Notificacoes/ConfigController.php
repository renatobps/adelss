<?php

namespace App\Http\Controllers\Notificacoes;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\NotificacaoGrupo;
use App\Services\NotificacaoService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{
    private function apiUrl(): string
    {
        return rtrim((string) (config('whatsapp.api_url') ?? ''), '/');
    }

    private function instanceId(): string
    {
        return (string) (config('whatsapp.instance_id') ?? '');
    }

    private function instanceToken(): string
    {
        return (string) (config('whatsapp.instance_token') ?? '');
    }

    private function apiHeaders(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        $token = config('whatsapp.client_token');
        if ($token) {
            $headers['Client-Token'] = $token;
        }
        return $headers;
    }

    private function buildZApiUrl(string $endpoint): string
    {
        return $this->apiUrl() . '/instances/' . $this->instanceId() . '/token/' . $this->instanceToken() . '/' . $endpoint;
    }

    public function index()
    {
        $whatsapp = app(WhatsAppService::class);
        $configurado = $whatsapp->isConfigurado();
        return view('notificacoes.config.index', compact('configurado'));
    }

    /** GET status (JSON) para AJAX */
    public function status()
    {
        try {
            $apiUrl = $this->apiUrl();
            $instanceId = $this->instanceId();
            $instanceToken = $this->instanceToken();
            if (empty($apiUrl) || empty($instanceId) || empty($instanceToken)) {
                return response()->json([
                    'success' => true,
                    'data' => ['state' => 'close', 'note' => 'Configure WHATSAPP_* no .env'],
                ]);
            }
            $url = $this->buildZApiUrl('status');
            $res = Http::withHeaders($this->apiHeaders())->timeout(15)->get($url);
            $body = $res->json() ?? [];
            $state = $body['connectionStatus']['state'] ?? $body['state'] ?? $body['status'] ?? 'unknown';
            $open = in_array(strtolower((string) $state), ['open', 'connected', 'conectado'], true);
            return response()->json([
                'success' => true,
                'data' => [
                    'state' => $open ? 'open' : ($state ?: 'close'),
                    'raw' => $body,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp status check failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => true,
                'data' => ['state' => 'close', 'note' => $e->getMessage()],
            ]);
        }
    }

    /** GET conectar (QR Code) - JSON */
    public function conectar()
    {
        try {
            $apiUrl = $this->apiUrl();
            $instanceId = $this->instanceId();
            $instanceToken = $this->instanceToken();
            if (empty($apiUrl) || empty($instanceId) || empty($instanceToken)) {
                return response()->json(['success' => false, 'error' => 'API não configurada no .env'], 400);
            }
            $url = $this->buildZApiUrl('instance/connect');
            $res = Http::withHeaders($this->apiHeaders())->timeout(30)->get($url);
            $body = $res->json() ?? [];
            $base64 = $body['base64'] ?? $body['qr'] ?? $body['value'] ?? $body['data']['base64'] ?? null;
            if ($base64) {
                if (!str_contains($base64, 'data:')) {
                    $base64 = 'data:image/png;base64,' . $base64;
                }
                return response()->json(['success' => true, 'data' => ['base64' => $base64]]);
            }
            return response()->json([
                'success' => false,
                'error' => $body['message'] ?? $body['error'] ?? 'QR Code não retornado pela API',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('WhatsApp conectar failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** GET listar instâncias (Z-API pode não ter; retornamos a instância atual) */
    public function listarInstancias()
    {
        try {
            $instanceId = $this->instanceId();
            if (empty($instanceId)) {
                return response()->json(['success' => true, 'data' => []]);
            }
            return response()->json([
                'success' => true,
                'data' => [[
                    'instance' => [
                        'instanceName' => $instanceId,
                        'status' => 'active',
                        'owner' => '—',
                    ],
                ]],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function criarInstancia(Request $request)
    {
        $request->validate(['instanceName' => 'required|string|max:64']);
        return response()->json([
            'success' => true,
            'data' => ['instanceName' => $request->instanceName, 'message' => 'Configure a instância no .env (Z-API).'],
        ]);
    }

    public function deletarInstancia(string $instanceName)
    {
        return response()->json([
            'success' => false,
            'error' => 'Exclusão de instância é feita pelo painel Z-API.',
        ], 400);
    }

    public function statusInstancia(string $instanceName)
    {
        $state = ($this->instanceId() === $instanceName) ? 'active' : 'unknown';
        return response()->json(['success' => true, 'data' => ['state' => $state]]);
    }

    public function configurarWebhookReceived(Request $request)
    {
        $request->validate(['value' => 'required|url']);
        try {
            $url = $this->buildZApiUrl('update-webhook-received');
            $res = Http::withHeaders($this->apiHeaders())->timeout(30)->put($url, ['value' => $request->value]);
            if ($res->successful()) {
                return response()->json(['success' => true, 'message' => 'Webhook para receber mensagens configurado.']);
            }
            $body = $res->json() ?? [];
            return response()->json([
                'success' => false,
                'error' => $body['message'] ?? $body['error'] ?? 'Erro ao configurar webhook',
            ], $res->status());
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function configurarWebhookDelivery(Request $request)
    {
        $request->validate(['value' => 'required|url']);
        try {
            $url = $this->buildZApiUrl('update-webhook-delivery');
            $res = Http::withHeaders($this->apiHeaders())->timeout(30)->put($url, ['value' => $request->value]);
            if ($res->successful()) {
                return response()->json(['success' => true, 'message' => 'Webhook para confirmações de envio configurado.']);
            }
            $body = $res->json() ?? [];
            return response()->json([
                'success' => false,
                'error' => $body['message'] ?? $body['error'] ?? 'Erro ao configurar webhook',
            ], $res->status());
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** POST teste: aceita formulário ou AJAX (numero + mensagem ou department_id + mensagem). Grupo = Departamento. */
    public function enviarTeste(Request $request)
    {
        $request->validate([
            'telefone' => 'nullable|string|min:8',
            'numero' => 'nullable|string|min:8',
            'mensagem' => 'nullable|string|max:4096',
            'grupo_id' => 'nullable|integer',
            'department_id' => 'nullable|integer|exists:departments,id',
        ]);
        $departmentId = $request->input('department_id') ?? $request->input('grupo_id');
        if (!$request->filled('numero') && !$request->filled('telefone') && !$departmentId) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Informe número ou selecione um departamento.'], 422);
            }
            return back()->with('error', 'Informe o telefone ou selecione um departamento.');
        }

        $numero = $request->input('numero') ?? $request->input('telefone');
        $mensagem = $request->input('mensagem', 'Teste de conexão - ADELSS Notificações.');

        $wantsJson = $request->wantsJson() || $request->ajax();

        if ($departmentId) {
            $department = Department::find($departmentId);
            if (!$department) {
                if ($wantsJson) {
                    return response()->json(['success' => false, 'error' => 'Departamento não encontrado.'], 404);
                }
                return back()->with('error', 'Departamento não encontrado.');
            }
            $service = app(NotificacaoService::class);
            $totais = $service->enviarParaDepartamento($department, $mensagem);
            if ($wantsJson) {
                return response()->json(['success' => true, 'data' => $totais]);
            }
            return back()->with('success', "Enviado ao departamento: {$totais['enviadas']} enviadas, {$totais['erros']} erros.");
        }

        if (empty($numero)) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'error' => 'Número ou grupo é obrigatório.'], 422);
            }
            return back()->with('error', 'Informe o telefone.');
        }

        $whatsapp = app(WhatsAppService::class);
        $resultado = $whatsapp->enviarMensagem($numero, $mensagem);

        if ($wantsJson) {
            if ($resultado['success'] ?? false) {
                return response()->json(['success' => true, 'data' => $resultado['data'] ?? []]);
            }
            return response()->json([
                'success' => false,
                'error' => $resultado['error'] ?? 'Falha ao enviar',
                'status' => $resultado['status'] ?? 500,
            ], $resultado['status'] ?? 500);
        }

        if ($resultado['success'] ?? false) {
            return back()->with('success', 'Mensagem de teste enviada com sucesso.');
        }
        return back()->with('error', $resultado['error'] ?? 'Falha ao enviar mensagem de teste.');
    }
}
