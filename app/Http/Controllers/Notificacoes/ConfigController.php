<?php

namespace App\Http\Controllers\Notificacoes;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\NotificacaoService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{
    private const ACTIVE_INSTANCE_CACHE_KEY = 'whatsapp.active_instance_name';

    private function apiUrl(): string
    {
        return rtrim((string) (config('whatsapp.api_url') ?? ''), '/');
    }

    private function apiHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'apikey' => (string) (config('whatsapp.api_key') ?? ''),
        ];
    }

    private function buildEvolutionUrl(string $endpoint): string
    {
        return $this->apiUrl() . '/' . ltrim($endpoint, '/');
    }

    private function configuredInstanceName(): string
    {
        return trim((string) (config('whatsapp.instance_name') ?? ''));
    }

    private function activeInstanceName(): string
    {
        $selected = Cache::get(self::ACTIVE_INSTANCE_CACHE_KEY);
        if (is_string($selected) && trim($selected) !== '') {
            return trim($selected);
        }

        return $this->configuredInstanceName();
    }

    private function normalizeInstancesPayload(array $body): array
    {
        $possibleLists = [
            $body['instances'] ?? null,
            $body['data'] ?? null,
            $body['response'] ?? null,
            $body,
        ];

        $instances = [];
        foreach ($possibleLists as $list) {
            if (!is_array($list)) {
                continue;
            }
            foreach ($list as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $name = $item['instanceName']
                    ?? $item['name']
                    ?? $item['instance']['instanceName']
                    ?? $item['instance']['name']
                    ?? null;
                if (!is_string($name) || trim($name) === '') {
                    continue;
                }

                $status = $item['connectionStatus']
                    ?? $item['state']
                    ?? $item['status']
                    ?? $item['instance']['status']
                    ?? $item['instance']['state']
                    ?? 'unknown';
                $owner = $item['owner']
                    ?? $item['instance']['owner']
                    ?? $item['profileName']
                    ?? '—';

                $instances[] = [
                    'instance' => [
                        'instanceName' => trim($name),
                        'status' => strtolower((string) $status),
                        'owner' => is_string($owner) && trim($owner) !== '' ? trim($owner) : '—',
                    ],
                ];
            }
            if (!empty($instances)) {
                break;
            }
        }

        return $instances;
    }

    private function configurarWebhookEvolution(string $url, array $events): array
    {
        $instanceName = $this->activeInstanceName();
        if ($instanceName === '') {
            return ['success' => false, 'error' => 'Selecione uma instância ativa.', 'status' => 422];
        }

        $apiKey = (string) (config('whatsapp.api_key') ?? '');
        if ($this->apiUrl() === '' || $apiKey === '') {
            return ['success' => false, 'error' => 'Configure WHATSAPP_API_URL e WHATSAPP_API_KEY no .env', 'status' => 400];
        }

        $url = rtrim($url, '/');
        $endpoint = $this->buildEvolutionUrl("webhook/set/{$instanceName}");
        $payloads = [
            [
                'webhook' => [
                    'enabled' => true,
                    'url' => $url,
                    'webhookByEvents' => false,
                    'webhookBase64' => false,
                    'events' => $events,
                ],
            ],
            [
                'enabled' => true,
                'url' => $url,
                'webhookByEvents' => false,
                'events' => $events,
            ],
        ];

        $lastBody = [];
        $lastStatus = 500;

        foreach ($payloads as $payload) {
            $res = Http::withHeaders($this->apiHeaders())->timeout(30)->post($endpoint, $payload);
            $lastBody = $res->json() ?? [];
            $lastStatus = $res->status();

            if ($res->successful()) {
                return [
                    'success' => true,
                    'message' => 'Webhook configurado na instância ' . $instanceName . '.',
                    'data' => $lastBody,
                    'status' => 200,
                ];
            }
        }

        return [
            'success' => false,
            'error' => $lastBody['message'] ?? $lastBody['error'] ?? 'Erro ao configurar webhook na Evolution API',
            'status' => $lastStatus,
        ];
    }

    public function index()
    {
        $this->authorize('notificacoes.view');
        $whatsapp = app(WhatsAppService::class);
        $configurado = $whatsapp->isConfigurado();
        $instanciaSelecionada = $this->activeInstanceName();
        $instanciaPadrao = $this->configuredInstanceName();

        return view('notificacoes.config.index', compact('configurado', 'instanciaSelecionada', 'instanciaPadrao'));
    }

    /** GET status (JSON) para AJAX */
    public function status()
    {
        $this->authorize('notificacoes.view');
        try {
            $apiUrl = $this->apiUrl();
            $instanceName = $this->activeInstanceName();
            $apiKey = (string) (config('whatsapp.api_key') ?? '');

            if (empty($apiUrl)) {
                return response()->json([
                    'success' => true,
                    'data' => ['state' => 'close', 'note' => 'Configure WHATSAPP_API_URL no .env'],
                ]);
            }

            if (empty($apiKey) || empty($instanceName)) {
                return response()->json([
                    'success' => true,
                    'data' => ['state' => 'close', 'note' => 'Configure WHATSAPP_API_KEY e WHATSAPP_INSTANCE_NAME no .env'],
                ]);
            }

            $url = $this->buildEvolutionUrl('instance/connectionState/' . $instanceName);
            $res = Http::withHeaders($this->apiHeaders())->timeout(15)->get($url);
            $body = $res->json();
            if (!is_array($body)) {
                $body = [];
            }

            $state = $body['instance']['state']
                ?? $body['instance']['connectionStatus']
                ?? $body['connectionStatus']['state']
                ?? $body['connectionState']
                ?? $body['state']
                ?? $body['status']
                ?? $body['data']['instance']['state']
                ?? $body['data']['state']
                ?? 'unknown';

            $normalizedState = strtolower((string) $state);
            $open = in_array($normalizedState, ['open', 'connected', 'conectado'], true);

            return response()->json([
                'success' => true,
                'data' => [
                    'state' => $open ? 'open' : ($normalizedState ?: 'close'),
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
        $this->authorize('notificacoes.manage');
        try {
            $apiUrl = $this->apiUrl();
            $instanceName = $this->activeInstanceName();
            $apiKey = (string) (config('whatsapp.api_key') ?? '');

            if (empty($apiUrl)) {
                return response()->json(['success' => false, 'error' => 'API não configurada no .env'], 400);
            }

            if (empty($apiKey) || empty($instanceName)) {
                return response()->json(['success' => false, 'error' => 'Configure WHATSAPP_API_KEY e WHATSAPP_INSTANCE_NAME no .env'], 400);
            }

            $url = $this->buildEvolutionUrl('instance/connect/' . $instanceName);
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

    /** GET listar instâncias Evolution API */
    public function listarInstancias()
    {
        $this->authorize('notificacoes.manage');
        try {
            $apiUrl = $this->apiUrl();
            $instanceName = $this->configuredInstanceName();
            $instanceSelecionada = $this->activeInstanceName();
            $apiKey = (string) (config('whatsapp.api_key') ?? '');

            if (empty($instanceName) && empty($instanceSelecionada)) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $instances = [];
            if (!empty($apiUrl) && !empty($apiKey)) {
                $url = $this->buildEvolutionUrl('instance/fetchInstances');
                $res = Http::withHeaders($this->apiHeaders())->timeout(20)->get($url);
                $body = $res->json();
                if (!is_array($body)) {
                    $body = [];
                }
                $instances = $this->normalizeInstancesPayload($body);
            }

            if (empty($instances)) {
                $fallbackName = $instanceSelecionada ?: $instanceName;
                $fallbackStatus = 'unknown';

                if (!empty($apiUrl) && !empty($apiKey) && !empty($fallbackName)) {
                    $statusUrl = $this->buildEvolutionUrl('instance/connectionState/' . $fallbackName);
                    $statusRes = Http::withHeaders($this->apiHeaders())->timeout(15)->get($statusUrl);
                    $statusBody = $statusRes->json();
                    if (!is_array($statusBody)) {
                        $statusBody = [];
                    }
                    $fallbackStatus = strtolower((string) (
                        $statusBody['instance']['state']
                        ?? $statusBody['instance']['connectionStatus']
                        ?? $statusBody['connectionStatus']['state']
                        ?? $statusBody['connectionState']
                        ?? $statusBody['state']
                        ?? $statusBody['status']
                        ?? 'unknown'
                    ));
                }

                if (!empty($fallbackName)) {
                    $instances = [[
                        'instance' => [
                            'instanceName' => $fallbackName,
                            'status' => $fallbackStatus,
                            'owner' => '—',
                        ],
                    ]];
                }
            }

            foreach ($instances as &$instanceData) {
                $name = (string) ($instanceData['instance']['instanceName'] ?? '');
                $instanceData['instance']['selected'] = $name !== '' && $name === $instanceSelecionada;
            }
            unset($instanceData);

            return response()->json([
                'success' => true,
                'selected' => $instanceSelecionada,
                'data' => $instances,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function selecionarInstancia(Request $request, string $instanceName)
    {
        $this->authorize('notificacoes.manage');
        $instanceName = trim($instanceName);
        if ($instanceName === '') {
            return response()->json(['success' => false, 'error' => 'Instância inválida.'], 422);
        }

        Cache::forever(self::ACTIVE_INSTANCE_CACHE_KEY, $instanceName);

        return response()->json([
            'success' => true,
            'message' => "Instância ativa alterada para {$instanceName}.",
            'data' => ['instanceName' => $instanceName],
        ]);
    }

    public function criarInstancia(Request $request)
    {
        $this->authorize('notificacoes.manage');
        $request->validate(['instanceName' => 'required|string|max:64']);

        try {
            $apiKey = (string) (config('whatsapp.api_key') ?? '');
            if ($this->apiUrl() === '' || $apiKey === '') {
                return response()->json([
                    'success' => false,
                    'error' => 'Configure WHATSAPP_API_URL e WHATSAPP_API_KEY no .env',
                ], 400);
            }

            $instanceName = trim((string) $request->instanceName);
            $url = $this->buildEvolutionUrl('instance/create');
            $res = Http::withHeaders($this->apiHeaders())->timeout(30)->post($url, [
                'instanceName' => $instanceName,
                'qrcode' => true,
            ]);
            $body = $res->json() ?? [];

            if ($res->successful()) {
                Cache::forever(self::ACTIVE_INSTANCE_CACHE_KEY, $instanceName);

                return response()->json([
                    'success' => true,
                    'message' => "Instância {$instanceName} criada.",
                    'data' => $body,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $body['message'] ?? $body['error'] ?? 'Erro ao criar instância',
            ], $res->status());
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deletarInstancia(string $instanceName)
    {
        $this->authorize('notificacoes.manage');
        try {
            $apiKey = (string) (config('whatsapp.api_key') ?? '');
            if ($this->apiUrl() === '' || $apiKey === '') {
                return response()->json([
                    'success' => false,
                    'error' => 'Configure WHATSAPP_API_URL e WHATSAPP_API_KEY no .env',
                ], 400);
            }

            $instanceName = trim($instanceName);
            $url = $this->buildEvolutionUrl('instance/delete/' . $instanceName);
            $res = Http::withHeaders($this->apiHeaders())->timeout(30)->delete($url);
            $body = $res->json() ?? [];

            if ($res->successful()) {
                if ($this->activeInstanceName() === $instanceName) {
                    Cache::forget(self::ACTIVE_INSTANCE_CACHE_KEY);
                }

                return response()->json([
                    'success' => true,
                    'message' => "Instância {$instanceName} removida.",
                    'data' => $body,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $body['message'] ?? $body['error'] ?? 'Erro ao excluir instância',
            ], $res->status());
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function statusInstancia(string $instanceName)
    {
        $this->authorize('notificacoes.view');
        try {
            $url = $this->buildEvolutionUrl('instance/connectionState/' . trim($instanceName));
            $res = Http::withHeaders($this->apiHeaders())->timeout(15)->get($url);
            $body = $res->json() ?? [];
            $state = strtolower((string) (
                $body['instance']['state']
                ?? $body['instance']['connectionStatus']
                ?? $body['state']
                ?? 'unknown'
            ));

            return response()->json(['success' => true, 'data' => ['state' => $state]]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function reiniciarInstancia(string $instanceName)
    {
        $this->authorize('notificacoes.manage');
        try {
            $apiUrl = $this->apiUrl();
            $apiKey = (string) (config('whatsapp.api_key') ?? '');
            if (empty($apiUrl) || empty($apiKey)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configure WHATSAPP_API_URL e WHATSAPP_API_KEY no .env',
                ], 400);
            }

            $url = $this->buildEvolutionUrl('instance/restart/' . $instanceName);
            $res = Http::withHeaders($this->apiHeaders())->timeout(30)->post($url);
            $body = $res->json() ?? [];

            if ($res->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Instância reiniciada com sucesso.',
                    'data' => $body,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $body['message'] ?? $body['error'] ?? 'Falha ao reiniciar instância',
            ], $res->status());
        } catch (\Throwable $e) {
            Log::error('WhatsApp restart instance failed', ['error' => $e->getMessage(), 'instance' => $instanceName]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function configurarWebhookReceived(Request $request)
    {
        $this->authorize('notificacoes.manage');
        $request->validate(['value' => 'required|url']);

        try {
            $result = $this->configurarWebhookEvolution((string) $request->value, ['MESSAGES_UPSERT']);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data'] ?? [],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], $result['status'] ?? 500);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function configurarWebhookDelivery(Request $request)
    {
        $this->authorize('notificacoes.manage');
        $request->validate(['value' => 'required|url']);

        try {
            $result = $this->configurarWebhookEvolution((string) $request->value, ['MESSAGES_UPDATE', 'SEND_MESSAGE']);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook de confirmações de envio configurado.',
                    'data' => $result['data'] ?? [],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], $result['status'] ?? 500);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** POST teste: aceita formulário ou AJAX (numero + mensagem ou department_id + mensagem). Grupo = Departamento. */
    public function enviarTeste(Request $request)
    {
        $this->authorize('notificacoes.manage');
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
