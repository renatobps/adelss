<?php

namespace App\Http\Controllers\Notificacoes;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\WhatsAppConnectionLog;
use App\Services\NotificacaoService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{
    public function __construct(
        private readonly WhatsAppService $whatsapp
    ) {}

    public function index()
    {
        $this->authorize('notificacoes.view');
        $configurado = $this->whatsapp->isConfigurado();
        $instanciaSelecionada = $this->whatsapp->getActiveInstanceName();
        $instanciaPadrao = trim((string) (config('whatsapp.instance_name') ?? ''));
        $instanciaId = $this->whatsapp->getActiveInstanceId();

        $connectionLogs = WhatsAppConnectionLog::query()
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('notificacoes.config.index', compact(
            'configurado',
            'instanciaSelecionada',
            'instanciaPadrao',
            'instanciaId',
            'connectionLogs'
        ));
    }

    /** GET status (JSON) para AJAX */
    public function status()
    {
        $this->authorize('notificacoes.view');
        try {
            if (!config('whatsapp.api_url')) {
                return response()->json([
                    'success' => true,
                    'data' => ['state' => 'close', 'note' => 'Configure WHATSAPP_API_URL no .env'],
                ]);
            }

            if (!config('whatsapp.api_key') || $this->whatsapp->getActiveInstanceName() === '') {
                return response()->json([
                    'success' => true,
                    'data' => ['state' => 'close', 'note' => 'Configure WHATSAPP_API_KEY e WHATSAPP_INSTANCE_NAME no .env'],
                ]);
            }

            $status = $this->whatsapp->getConnectionStatus();

            return response()->json([
                'success' => true,
                'data' => [
                    'state' => $status['state'],
                    'name' => $status['name'] ?? null,
                    'raw' => $status['raw'] ?? null,
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
            if (!config('whatsapp.api_url') || !config('whatsapp.api_key')) {
                return response()->json(['success' => false, 'error' => 'API não configurada no .env'], 400);
            }

            // Garante webhook + conexão; QR pode vir do endpoint dedicado.
            $webhookUrl = (string) (config('whatsapp.webhook_url') ?: url('/webhook'));
            $this->whatsapp->configurarWebhook($webhookUrl, [
                'MESSAGE',
                'SEND_MESSAGE',
                'CONNECTION',
                'READ_RECEIPT',
                'QRCODE',
            ]);

            $qr = $this->whatsapp->getQrCode();
            if ($qr['success'] ?? false) {
                return response()->json(['success' => true, 'data' => $qr['data'] ?? []]);
            }

            // Já conectado: trata como sucesso informativo.
            $status = $this->whatsapp->getConnectionStatus();
            if ($status['connected'] ?? false) {
                return response()->json([
                    'success' => true,
                    'data' => ['already_connected' => true, 'state' => 'open'],
                    'message' => 'Instância já está conectada.',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $qr['error'] ?? 'QR Code não retornado pela API',
            ], $qr['status'] ?? 404);
        } catch (\Throwable $e) {
            Log::error('WhatsApp conectar failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** GET listar instâncias Evolution GO */
    public function listarInstancias()
    {
        $this->authorize('notificacoes.manage');
        try {
            $instanceSelecionada = $this->whatsapp->getActiveInstanceName();
            $instanceIdSelecionada = $this->whatsapp->getActiveInstanceId();
            $instancesRaw = $this->whatsapp->listInstances(true);

            $instances = [];
            foreach ($instancesRaw as $item) {
                $name = (string) ($item['name'] ?? '');
                $id = (string) ($item['id'] ?? '');
                $selected = ($id !== '' && $id === $instanceIdSelecionada)
                    || ($name !== '' && strcasecmp($name, $instanceSelecionada) === 0);
                $instances[] = [
                    'instance' => [
                        'instanceName' => $name,
                        'instanceId' => $id,
                        'status' => (string) ($item['status'] ?? 'unknown'),
                        'owner' => (string) ($item['owner'] ?? '—'),
                        'selected' => $selected,
                    ],
                ];
            }

            if (empty($instances) && $instanceSelecionada !== '') {
                $status = $this->whatsapp->getConnectionStatus();
                $instances[] = [
                    'instance' => [
                        'instanceName' => $instanceSelecionada,
                        'instanceId' => $this->whatsapp->getActiveInstanceId(),
                        'status' => $status['state'] ?? 'unknown',
                        'owner' => '—',
                        'selected' => true,
                    ],
                ];
            }

            return response()->json([
                'success' => true,
                'selected' => $instanceSelecionada,
                'selectedId' => $instanceIdSelecionada,
                'data' => $instances,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function selecionarInstancia(Request $request, string $instanceName)
    {
        $this->authorize('notificacoes.manage');
        $instanceName = trim(urldecode($instanceName));
        if ($instanceName === '') {
            return response()->json(['success' => false, 'error' => 'Instância inválida.'], 422);
        }

        if (!$this->whatsapp->selecionarInstancia($instanceName)) {
            return response()->json(['success' => false, 'error' => 'Instância não encontrada na Evolution GO.'], 404);
        }

        $activeName = $this->whatsapp->getActiveInstanceName();
        $activeId = $this->whatsapp->getActiveInstanceId();

        return response()->json([
            'success' => true,
            'message' => "Instância ativa alterada para {$activeName}. Próximos envios usarão esta conta.",
            'data' => [
                'instanceName' => $activeName,
                'instanceId' => $activeId,
            ],
        ]);
    }

    public function criarInstancia(Request $request)
    {
        $this->authorize('notificacoes.manage');
        $request->validate(['instanceName' => 'required|string|max:64']);

        try {
            $result = $this->whatsapp->createInstance(trim((string) $request->instanceName));
            if ($result['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Instância criada.',
                    'data' => $result['data'] ?? [],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Erro ao criar instância',
            ], $result['status'] ?? 500);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deletarInstancia(string $instanceName)
    {
        $this->authorize('notificacoes.manage');
        try {
            $instance = $this->whatsapp->findInstanceByName(trim($instanceName))
                ?? $this->whatsapp->findInstanceById(trim($instanceName));

            if (!$instance || empty($instance['id'])) {
                return response()->json(['success' => false, 'error' => 'Instância não encontrada.'], 404);
            }

            $result = $this->whatsapp->deleteInstance((string) $instance['id']);
            if ($result['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => "Instância {$instance['name']} removida.",
                    'data' => $result['data'] ?? [],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Erro ao excluir instância',
            ], $result['status'] ?? 500);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function statusInstancia(string $instanceName)
    {
        $this->authorize('notificacoes.view');
        try {
            $instance = $this->whatsapp->findInstanceByName(trim($instanceName))
                ?? $this->whatsapp->findInstanceById(trim($instanceName));

            if (!$instance || empty($instance['id'])) {
                return response()->json(['success' => false, 'error' => 'Instância não encontrada.'], 404);
            }

            $status = $this->whatsapp->getConnectionStatus((string) $instance['id']);

            return response()->json([
                'success' => true,
                'data' => ['state' => $status['state'] ?? 'unknown'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function reiniciarInstancia(string $instanceName)
    {
        $this->authorize('notificacoes.manage');
        try {
            $instance = $this->whatsapp->findInstanceByName(trim($instanceName))
                ?? $this->whatsapp->findInstanceById(trim($instanceName));

            if (!$instance || empty($instance['id'])) {
                return response()->json(['success' => false, 'error' => 'Instância não encontrada.'], 404);
            }

            $result = $this->whatsapp->reconnectInstance((string) $instance['id']);
            if ($result['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Instância reiniciada com sucesso.',
                    'data' => $result['data'] ?? [],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Falha ao reiniciar instância',
            ], $result['status'] ?? 500);
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
            $ok = $this->whatsapp->configurarWebhook((string) $request->value, [
                'MESSAGE',
                'SEND_MESSAGE',
                'CONNECTION',
                'READ_RECEIPT',
                'QRCODE',
            ]);

            if ($ok) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook configurado na Evolution GO (inclui READ_RECEIPT para status Recebido/Lida).',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Erro ao configurar webhook na Evolution GO',
            ], 500);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function configurarWebhookDelivery(Request $request)
    {
        $this->authorize('notificacoes.manage');
        $request->validate(['value' => 'required|url']);

        try {
            $ok = $this->whatsapp->configurarWebhook((string) $request->value, [
                'MESSAGE',
                'SEND_MESSAGE',
                'CONNECTION',
                'READ_RECEIPT',
                'QRCODE',
            ]);

            if ($ok) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook de confirmações (entrega/leitura) configurado.',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Erro ao configurar webhook na Evolution GO',
            ], 500);
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

        $resultado = $this->whatsapp->enviarMensagem($numero, $mensagem);

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
