<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private const ACTIVE_INSTANCE_CACHE_KEY = 'whatsapp.active_instance_name';
    private const ACTIVE_INSTANCE_ID_CACHE_KEY = 'whatsapp.active_instance_id';
    private const INSTANCES_CACHE_KEY = 'whatsapp.instances_snapshot';

    private string $apiUrl;
    private string $apiKey;
    private string $configuredInstanceName;
    private string $configuredInstanceId;

    public function __construct()
    {
        $this->apiUrl = rtrim((string) (config('whatsapp.api_url') ?? ''), '/');
        $this->apiKey = (string) (config('whatsapp.api_key') ?? '');
        $this->configuredInstanceName = trim((string) (config('whatsapp.instance_name') ?? ''));
        $this->configuredInstanceId = trim((string) (config('whatsapp.instance_id') ?? ''));
    }

    /**
     * Headers padrão Evolution GO: apikey + instanceId.
     *
     * @return array<string, string>
     */
    private function getApiHeaders(?string $instanceId = null): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'apikey' => $this->apiKey,
        ];

        $instanceId = trim((string) ($instanceId ?? $this->resolveInstanceId()));
        if ($instanceId !== '') {
            $headers['instanceId'] = $instanceId;
        }

        return $headers;
    }

    private function buildUrl(string $endpoint): string
    {
        return $this->apiUrl . '/' . ltrim($endpoint, '/');
    }

    public function getActiveInstanceName(): string
    {
        $selected = Cache::get(self::ACTIVE_INSTANCE_CACHE_KEY);
        if (is_string($selected) && trim($selected) !== '') {
            return trim($selected);
        }

        return $this->configuredInstanceName;
    }

    public function getActiveInstanceId(): string
    {
        return $this->resolveInstanceId();
    }

    private function resolveInstanceId(): string
    {
        $cachedId = Cache::get(self::ACTIVE_INSTANCE_ID_CACHE_KEY);
        if (is_string($cachedId) && trim($cachedId) !== '') {
            return trim($cachedId);
        }

        if ($this->configuredInstanceId !== '') {
            return $this->configuredInstanceId;
        }

        $instance = $this->findInstanceByName($this->getActiveInstanceName());
        $id = trim((string) ($instance['id'] ?? ''));
        if ($id !== '') {
            Cache::forever(self::ACTIVE_INSTANCE_ID_CACHE_KEY, $id);
        }

        return $id;
    }

    /**
     * @return array{id?: string, name?: string, token?: string, connected?: bool, jid?: string, webhook?: string}|null
     */
    public function findInstanceByName(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        foreach ($this->listInstances() as $instance) {
            if (strcasecmp((string) ($instance['name'] ?? ''), $name) === 0) {
                return $instance;
            }
        }

        return null;
    }

    /**
     * @return array{id?: string, name?: string, token?: string, connected?: bool, jid?: string, webhook?: string}|null
     */
    public function findInstanceById(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }

        foreach ($this->listInstances() as $instance) {
            if ((string) ($instance['id'] ?? '') === $id) {
                return $instance;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{id: string, name: string, token?: string, connected?: bool, jid?: string, webhook?: string, owner?: string, status?: string}>
     */
    public function listInstances(bool $fresh = false): array
    {
        if (!$fresh) {
            $cached = Cache::get(self::INSTANCES_CACHE_KEY);
            if (is_array($cached)) {
                return $cached;
            }
        }

        if ($this->apiUrl === '' || $this->apiKey === '') {
            return [];
        }

        try {
            $res = Http::withHeaders([
                'Content-Type' => 'application/json',
                'apikey' => $this->apiKey,
            ])->timeout(20)->get($this->buildUrl('instance/all'));

            $body = $res->json() ?? [];
            $list = $body['data'] ?? $body['instances'] ?? [];
            if (!is_array($list)) {
                return [];
            }

            $instances = [];
            foreach ($list as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $id = trim((string) ($item['id'] ?? $item['instanceId'] ?? ''));
                $name = trim((string) ($item['name'] ?? $item['instanceName'] ?? ''));
                if ($id === '' && $name === '') {
                    continue;
                }

                $connected = (bool) ($item['connected'] ?? false);
                $jid = (string) ($item['jid'] ?? '');
                $owner = $jid !== '' ? explode(':', explode('@', $jid)[0])[0] : '—';

                $instances[] = [
                    'id' => $id,
                    'name' => $name !== '' ? $name : $id,
                    'token' => (string) ($item['token'] ?? ''),
                    'connected' => $connected,
                    'jid' => $jid,
                    'webhook' => (string) ($item['webhook'] ?? ''),
                    'owner' => $owner,
                    'status' => $connected ? 'open' : 'close',
                ];
            }

            Cache::put(self::INSTANCES_CACHE_KEY, $instances, now()->addMinutes(2));

            return $instances;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp Evolution GO: falha ao listar instâncias', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function selecionarInstancia(string $instanceNameOrId): bool
    {
        $key = trim($instanceNameOrId);
        if ($key === '') {
            return false;
        }

        $instance = $this->findInstanceById($key) ?? $this->findInstanceByName($key);
        if (!$instance) {
            return false;
        }

        Cache::forever(self::ACTIVE_INSTANCE_CACHE_KEY, (string) $instance['name']);
        Cache::forever(self::ACTIVE_INSTANCE_ID_CACHE_KEY, (string) $instance['id']);
        Cache::forget(self::INSTANCES_CACHE_KEY);

        return true;
    }

    /**
     * Normaliza número para formato internacional (55...) com 9º dígito em celulares BR.
     */
    public static function normalizarNumero(string $numero): string
    {
        return self::aplicarNonoDigitoBr(self::normalizarNumeroBasico($numero));
    }

    /**
     * @return array<int, string>
     */
    public static function variantesNumero(string $numero): array
    {
        $base = self::normalizarNumeroBasico($numero);

        return array_values(array_unique(array_filter([
            $base,
            self::aplicarNonoDigitoBr($base),
            self::removerNonoDigitoBr($base),
        ])));
    }

    public static function numerosEquivalentes(string $a, string $b): bool
    {
        return count(array_intersect(self::variantesNumero($a), self::variantesNumero($b))) > 0;
    }

    private static function normalizarNumeroBasico(string $numero): string
    {
        $numero = preg_replace('/[^0-9]/', '', $numero);
        if (str_starts_with($numero, '5555')) {
            $numero = substr($numero, 2);
        }
        if (!str_starts_with($numero, '55') && strlen($numero) >= 10) {
            $numero = '55' . $numero;
        }

        return $numero;
    }

    private static function aplicarNonoDigitoBr(string $numero): string
    {
        if (!str_starts_with($numero, '55') || strlen($numero) !== 12) {
            return $numero;
        }

        $local = substr($numero, 4);
        if (strlen($local) !== 8 || !in_array($local[0], ['6', '7', '8', '9'], true)) {
            return $numero;
        }

        return substr($numero, 0, 4) . '9' . $local;
    }

    private static function removerNonoDigitoBr(string $numero): string
    {
        if (!str_starts_with($numero, '55') || strlen($numero) !== 13) {
            return $numero;
        }

        $local = substr($numero, 4);
        if (strlen($local) !== 9 || $local[0] !== '9') {
            return $numero;
        }

        return substr($numero, 0, 4) . substr($local, 1);
    }

    /**
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarMensagem(string $numero, string $mensagem, int $delayMs = 700): array
    {
        if (!$this->isConfigurado()) {
            Log::warning('WhatsApp Evolution GO: credenciais não configuradas.');

            return [
                'success' => false,
                'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_API_KEY e selecione uma instância ativa em Notificações > Configuração WPP.',
            ];
        }

        $numero = self::normalizarNumero($numero);
        $payload = [
            'number' => $numero,
            'text' => $mensagem,
        ];
        if ($delayMs > 0) {
            $payload['delay'] = max(1, $delayMs);
        }

        $res = $this->postJson('send/text', $payload);
        $body = $res->json() ?? [];

        if ($this->isSuccessfulResponse($res, $body)) {
            return ['success' => true, 'data' => $body];
        }

        return [
            'success' => false,
            'error' => $this->resolverMensagemErro($body),
            'status' => $res->status(),
        ];
    }

    public function isConfigurado(): bool
    {
        return $this->apiUrl !== ''
            && $this->apiKey !== ''
            && ($this->resolveInstanceId() !== '' || $this->getActiveInstanceName() !== '');
    }

    /**
     * Configura webhook na Evolution GO via POST /instance/connect.
     *
     * @param  array<int, string>  $events
     */
    public function configurarWebhook(?string $url = null, array $events = ['MESSAGE', 'SEND_MESSAGE', 'CONNECTION']): bool
    {
        $url ??= (string) config('whatsapp.webhook_url', '');
        if ($url === '' || !$this->isConfigurado()) {
            return false;
        }

        $events = array_values(array_unique(array_merge(['MESSAGE'], $events)));
        $payload = [
            'webhookUrl' => rtrim($url, '/'),
            'subscribe' => $events,
            'immediate' => true,
        ];

        $res = $this->postJson('instance/connect', $payload);
        if ($res->successful()) {
            Cache::forget(self::INSTANCES_CACHE_KEY);

            return true;
        }

        Log::warning('WhatsApp Evolution GO: falha ao configurar webhook', [
            'status' => $res->status(),
            'response' => $res->json(),
        ]);

        return false;
    }

    /**
     * Envia pergunta com botões de resposta (Evolution GO /send/button).
     * Não é poll nativo do WhatsApp: o destinatário clica no botão,
     * o webhook registra o texto escolhido e o sistema envia agradecimento.
     *
     * WhatsApp permite no máximo 3 botões reply; opções excedentes são truncadas.
     *
     * @param  array<int, mixed>  $opcoes
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarEnquetePoll(string $numero, string $titulo, string $descricao, array $opcoes, ?int $enqueteId = null): array
    {
        if (!$this->isConfigurado()) {
            Log::warning('WhatsApp Evolution GO: credenciais não configuradas para enquete.');

            return [
                'success' => false,
                'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_API_KEY e selecione uma instância ativa em Notificações > Configuração WPP.',
            ];
        }

        $numero = self::normalizarNumero($numero);
        $buttons = [];
        foreach ($opcoes as $index => $opcao) {
            $label = is_string($opcao)
                ? $opcao
                : (string) ($opcao['name'] ?? $opcao['label'] ?? $opcao['text'] ?? reset($opcao));
            $label = mb_substr(trim($label), 0, 20);
            if ($label === '') {
                continue;
            }

            $buttons[] = [
                'type' => 'reply',
                'displayText' => $label,
                'id' => (string) ($index + 1),
            ];
        }

        if (count($buttons) < 2) {
            return ['success' => false, 'error' => 'Enquetes requerem pelo menos 2 opções.'];
        }
        if (count($buttons) > 3) {
            $buttons = array_slice($buttons, 0, 3);
        }

        $payload = [
            'number' => $numero,
            'title' => mb_substr(trim($titulo) !== '' ? $titulo : 'Enquete', 0, 30),
            'description' => mb_substr(trim($descricao) !== '' ? $descricao : 'Selecione uma opção:', 0, 120),
            'footer' => 'ADELSS',
            'buttons' => $buttons,
        ];

        $res = $this->postJson('send/button', $payload);
        $body = $res->json() ?? [];

        if ($this->isSuccessfulResponse($res, $body)) {
            return ['success' => true, 'data' => $body];
        }

        Log::warning('WhatsApp Evolution GO: falha ao enviar enquete com botões', [
            'status' => $res->status(),
            'payload' => $payload,
            'response' => $body,
            'enquete_id' => $enqueteId,
        ]);

        return [
            'success' => false,
            'error' => $this->resolverMensagemErro($body),
            'status' => $res->status(),
        ];
    }

    /**
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarMidiaArquivo(
        string $numero,
        UploadedFile $arquivo,
        string $tipoMidia,
        bool $isPdfDocumento = false,
        ?string $fileName = null,
        string $legenda = ''
    ): array {
        $tipoMidia = strtolower(trim($tipoMidia));
        $tiposAceitos = ['image', 'document', 'video', 'audio'];
        if (!in_array($tipoMidia, $tiposAceitos, true)) {
            return [
                'success' => false,
                'error' => 'Tipo de mídia inválido. Use: image, document, video ou audio.',
            ];
        }

        $conteudo = @file_get_contents($arquivo->getRealPath());
        if ($conteudo === false) {
            return ['success' => false, 'error' => 'Não foi possível ler o arquivo de mídia.'];
        }

        $mime = $arquivo->getMimeType() ?: ($isPdfDocumento ? 'application/pdf' : 'application/octet-stream');
        $nome = $fileName ?: $arquivo->getClientOriginalName();

        return $this->enviarMidiaBase64(
            $numero,
            base64_encode($conteudo),
            $tipoMidia,
            $mime,
            $nome,
            $legenda
        );
    }

    /**
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarImagemBase64(string $numero, string $imagemBase64, string $legenda = ''): array
    {
        $conteudo = trim($imagemBase64);
        if ($conteudo === '') {
            return ['success' => false, 'error' => 'Imagem base64 vazia para envio.'];
        }

        $mime = 'image/png';
        if (str_starts_with($conteudo, 'data:image') && str_contains($conteudo, ',')) {
            if (preg_match('#^data:(image/[^;]+);base64,#i', $conteudo, $m)) {
                $mime = strtolower($m[1]);
            }
            [, $conteudo] = explode(',', $conteudo, 2);
        }

        return $this->enviarMidiaBase64(
            $numero,
            $conteudo,
            'image',
            $mime,
            'pix-qrcode.png',
            $legenda
        );
    }

    /**
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarDocumentoArquivo(
        string $numero,
        string $filePath,
        string $fileName = 'documento.pdf',
        string $legenda = ''
    ): array {
        if (!is_file($filePath)) {
            return ['success' => false, 'error' => 'Arquivo PDF não encontrado para envio.'];
        }

        $conteudo = @file_get_contents($filePath);
        if ($conteudo === false) {
            return ['success' => false, 'error' => 'Não foi possível ler o arquivo PDF.'];
        }

        return $this->enviarMidiaBase64(
            $numero,
            base64_encode($conteudo),
            'document',
            'application/pdf',
            $fileName,
            $legenda
        );
    }

    /**
     * Evolution GO /send/media espera JSON com url (http(s) ou data URI).
     *
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    private function enviarMidiaBase64(
        string $numero,
        string $base64,
        string $tipo,
        string $mime,
        string $fileName,
        string $legenda = ''
    ): array {
        if (!$this->isConfigurado()) {
            return [
                'success' => false,
                'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_API_KEY e selecione uma instância ativa em Notificações > Configuração WPP.',
            ];
        }

        $base64 = trim($base64);
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64, 2)[1];
        }

        if ($base64 === '' || base64_decode($base64, true) === false) {
            return ['success' => false, 'error' => 'Conteúdo base64 inválido para envio de mídia.'];
        }

        $payload = [
            'number' => self::normalizarNumero($numero),
            'type' => $tipo,
            'url' => 'data:' . $mime . ';base64,' . $base64,
            'filename' => $fileName,
        ];
        if (trim($legenda) !== '') {
            $payload['caption'] = $legenda;
        }

        $res = $this->postJson('send/media', $payload);
        $body = $res->json() ?? [];

        if ($this->isSuccessfulResponse($res, $body)) {
            return ['success' => true, 'data' => $body];
        }

        Log::warning('WhatsApp Evolution GO: falha ao enviar mídia', [
            'status' => $res->status(),
            'tipo' => $tipo,
            'numero' => self::normalizarNumero($numero),
            'response' => $body,
        ]);

        return [
            'success' => false,
            'error' => $this->resolverMensagemErro($body),
            'status' => $res->status(),
        ];
    }

    public function getConnectionStatus(?string $instanceId = null): array
    {
        if ($this->apiUrl === '' || $this->apiKey === '') {
            return ['state' => 'close', 'connected' => false, 'note' => 'API não configurada'];
        }

        $instanceId = trim((string) ($instanceId ?? $this->resolveInstanceId()));
        if ($instanceId === '') {
            return ['state' => 'close', 'connected' => false, 'note' => 'Instância não selecionada'];
        }

        $res = Http::withHeaders($this->getApiHeaders($instanceId))
            ->timeout(15)
            ->get($this->buildUrl('instance/status'));

        $body = $res->json() ?? [];
        $data = is_array($body['data'] ?? null) ? $body['data'] : $body;

        $connected = (bool) (
            $data['Connected']
            ?? $data['connected']
            ?? $data['LoggedIn']
            ?? $data['loggedIn']
            ?? false
        );

        return [
            'state' => $connected ? 'open' : 'close',
            'connected' => $connected,
            'name' => $data['Name'] ?? $data['name'] ?? null,
            'raw' => $body,
        ];
    }

    public function getQrCode(): array
    {
        if (!$this->isConfigurado()) {
            return ['success' => false, 'error' => 'API não configurada'];
        }

        $res = Http::withHeaders($this->getApiHeaders())
            ->timeout(30)
            ->get($this->buildUrl('instance/qr'));

        $body = $res->json() ?? [];
        $data = is_array($body['data'] ?? null) ? $body['data'] : $body;
        $base64 = $data['qrcode']
            ?? $data['base64']
            ?? $data['qr']
            ?? $body['qrcode']
            ?? null;

        if (is_string($base64) && $base64 !== '') {
            if (!str_contains($base64, 'data:')) {
                $base64 = 'data:image/png;base64,' . $base64;
            }

            return ['success' => true, 'data' => ['base64' => $base64]];
        }

        return [
            'success' => false,
            'error' => $this->resolverMensagemErro($body) ?: 'QR Code não retornado pela API',
            'status' => $res->status(),
        ];
    }

    public function reconnectInstance(?string $instanceId = null): array
    {
        if (!$this->isConfigurado()) {
            return ['success' => false, 'error' => 'API não configurada'];
        }

        $res = $this->postJson('instance/reconnect', [], $instanceId);
        $body = $res->json() ?? [];

        if ($this->isSuccessfulResponse($res, $body)) {
            Cache::forget(self::INSTANCES_CACHE_KEY);

            return ['success' => true, 'data' => $body];
        }

        return [
            'success' => false,
            'error' => $this->resolverMensagemErro($body),
            'status' => $res->status(),
        ];
    }

    public function createInstance(string $name, ?string $token = null): array
    {
        if ($this->apiUrl === '' || $this->apiKey === '') {
            return ['success' => false, 'error' => 'Configure WHATSAPP_API_URL e WHATSAPP_API_KEY no .env'];
        }

        $payload = ['name' => trim($name)];
        if ($token !== null && trim($token) !== '') {
            $payload['token'] = trim($token);
        }

        $res = Http::withHeaders([
            'Content-Type' => 'application/json',
            'apikey' => $this->apiKey,
        ])->timeout(30)->post($this->buildUrl('instance/create'), $payload);

        $body = $res->json() ?? [];
        if ($this->isSuccessfulResponse($res, $body)) {
            Cache::forget(self::INSTANCES_CACHE_KEY);
            $data = is_array($body['data'] ?? null) ? $body['data'] : $body;
            $createdName = (string) ($data['name'] ?? $name);
            $createdId = (string) ($data['id'] ?? '');
            if ($createdId !== '') {
                Cache::forever(self::ACTIVE_INSTANCE_CACHE_KEY, $createdName);
                Cache::forever(self::ACTIVE_INSTANCE_ID_CACHE_KEY, $createdId);
            }

            return ['success' => true, 'data' => $body];
        }

        return [
            'success' => false,
            'error' => $this->resolverMensagemErro($body),
            'status' => $res->status(),
        ];
    }

    public function deleteInstance(string $instanceId): array
    {
        if ($this->apiUrl === '' || $this->apiKey === '') {
            return ['success' => false, 'error' => 'Configure WHATSAPP_API_URL e WHATSAPP_API_KEY no .env'];
        }

        $instanceId = trim($instanceId);
        $res = Http::withHeaders([
            'Content-Type' => 'application/json',
            'apikey' => $this->apiKey,
        ])->timeout(30)->delete($this->buildUrl('instance/delete/' . $instanceId));

        $body = $res->json() ?? [];
        if ($this->isSuccessfulResponse($res, $body)) {
            if ($this->resolveInstanceId() === $instanceId) {
                Cache::forget(self::ACTIVE_INSTANCE_CACHE_KEY);
                Cache::forget(self::ACTIVE_INSTANCE_ID_CACHE_KEY);
            }
            Cache::forget(self::INSTANCES_CACHE_KEY);

            return ['success' => true, 'data' => $body];
        }

        return [
            'success' => false,
            'error' => $this->resolverMensagemErro($body),
            'status' => $res->status(),
        ];
    }

    private function postJson(string $endpoint, array $payload = [], ?string $instanceId = null): \Illuminate\Http\Client\Response
    {
        return Http::withHeaders($this->getApiHeaders($instanceId))
            ->asJson()
            ->timeout(config('whatsapp.timeout', 120))
            ->post($this->buildUrl($endpoint), $payload);
    }

    private function isSuccessfulResponse(\Illuminate\Http\Client\Response $res, array $body): bool
    {
        if (!$res->successful()) {
            return false;
        }

        if (array_key_exists('success', $body) && $body['success'] === false) {
            return false;
        }

        if (!empty($body['error'])) {
            return false;
        }

        return true;
    }

    private function resolverMensagemErro(array $body): string
    {
        $error = $body['error'] ?? null;
        if (is_array($error)) {
            return (string) ($error['message'] ?? $error['code'] ?? json_encode($error));
        }
        if (is_string($error) && $error !== '') {
            return $error;
        }

        if (!empty($body['message']) && is_string($body['message']) && strtolower($body['message']) !== 'success') {
            return (string) $body['message'];
        }

        return 'Erro ao comunicar com a Evolution GO';
    }
}
