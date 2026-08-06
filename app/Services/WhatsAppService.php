<?php

namespace App\Services;

use App\Models\ConfiguracaoWhatsapp;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsAppService
{
    private const ACTIVE_INSTANCE_CACHE_KEY = 'whatsapp.active_instance_name';
    private const ACTIVE_INSTANCE_ID_CACHE_KEY = 'whatsapp.active_instance_id';
    private const ACTIVE_INSTANCE_TOKEN_CACHE_KEY = 'whatsapp.active_instance_token';
    private const INSTANCES_CACHE_KEY = 'whatsapp.instances_snapshot';
    private const DB_KEY_INSTANCE_NAME = 'active_instance_name';
    private const DB_KEY_INSTANCE_ID = 'active_instance_id';
    private const DB_KEY_INSTANCE_TOKEN = 'active_instance_token';

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
     * Headers Evolution GO.
     * Importante: em muitos deploys o WHATSAPP_API_KEY do .env é o token de UMA instância
     * (ex.: aDelss). Se usarmos só esse token + outro instanceId, a API ignora o instanceId
     * e continua enviando pela conta dona do token. Por isso, em operações da instância ativa
     * usamos o token da própria instância selecionada.
     *
     * @return array<string, string>
     */
    private function getApiHeaders(?string $instanceId = null, bool $useActiveInstanceToken = true): array
    {
        $resolved = trim((string) ($instanceId ?? ''));
        if ($resolved === '') {
            $resolved = $this->resolveInstanceId();
        }

        $apikey = $this->apiKey;
        if ($useActiveInstanceToken) {
            $instanceToken = $this->resolveInstanceToken($resolved);
            if ($instanceToken !== '') {
                $apikey = $instanceToken;
            }
        }

        $headers = [
            'Content-Type' => 'application/json',
            'apikey' => $apikey,
        ];
        if ($resolved !== '') {
            $headers['instanceId'] = $resolved;
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

        $fromDb = $this->getPersistedSetting(self::DB_KEY_INSTANCE_NAME);
        if ($fromDb !== '') {
            Cache::forever(self::ACTIVE_INSTANCE_CACHE_KEY, $fromDb);

            return $fromDb;
        }

        return $this->configuredInstanceName;
    }

    public function getActiveInstanceId(): string
    {
        return $this->resolveInstanceId();
    }

    /**
     * Prioridade da instância ativa:
     * 1) Cache (troca recente na UI)
     * 2) Banco configuracoes_whatsapp (sobrevive a cache:clear)
     * 3) Resolve pelo nome ativo na API
     * 4) Fallback .env (só se o usuário nunca escolheu na UI)
     */
    private function resolveInstanceId(): string
    {
        $cachedId = Cache::get(self::ACTIVE_INSTANCE_ID_CACHE_KEY);
        if (is_string($cachedId) && trim($cachedId) !== '') {
            return trim($cachedId);
        }

        $fromDb = $this->getPersistedSetting(self::DB_KEY_INSTANCE_ID);
        if ($fromDb !== '') {
            Cache::forever(self::ACTIVE_INSTANCE_ID_CACHE_KEY, $fromDb);

            return $fromDb;
        }

        $activeName = $this->getActiveInstanceName();
        if ($activeName !== '') {
            $instance = $this->findInstanceByName($activeName);
            $id = trim((string) ($instance['id'] ?? ''));
            if ($id !== '') {
                $this->rememberActiveInstance(
                    $activeName,
                    $id,
                    (string) ($instance['token'] ?? '')
                );

                return $id;
            }
        }

        // Fallback .env — só quando não há seleção salva.
        if ($this->configuredInstanceId !== '') {
            return $this->configuredInstanceId;
        }

        return '';
    }

    /**
     * Token da instância ativa (ou da informada). Sem isso o envio pode cair na conta do token do .env.
     */
    private function resolveInstanceToken(?string $instanceId = null): string
    {
        $instanceId = trim((string) ($instanceId ?: $this->resolveInstanceId()));

        $cached = Cache::get(self::ACTIVE_INSTANCE_TOKEN_CACHE_KEY);
        $cachedId = Cache::get(self::ACTIVE_INSTANCE_ID_CACHE_KEY);
        if (is_string($cached) && trim($cached) !== ''
            && ($instanceId === '' || (string) $cachedId === $instanceId)) {
            return trim($cached);
        }

        $fromDb = $this->getPersistedSetting(self::DB_KEY_INSTANCE_TOKEN);
        $dbId = $this->getPersistedSetting(self::DB_KEY_INSTANCE_ID);
        if ($fromDb !== '' && ($instanceId === '' || $dbId === $instanceId)) {
            Cache::forever(self::ACTIVE_INSTANCE_TOKEN_CACHE_KEY, $fromDb);

            return $fromDb;
        }

        if ($instanceId !== '') {
            $instance = $this->findInstanceById($instanceId);
            $token = trim((string) ($instance['token'] ?? ''));
            if ($token !== '') {
                if ($instanceId === $this->resolveInstanceId()) {
                    Cache::forever(self::ACTIVE_INSTANCE_TOKEN_CACHE_KEY, $token);
                    $this->persistSetting(self::DB_KEY_INSTANCE_TOKEN, $token, 'Token da instância WhatsApp ativa');
                }

                return $token;
            }
        }

        return '';
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
            // Listagem usa a chave do .env (global ou admin), não o token da instância ativa.
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

        // Lista fresca da API para não pegar ID desatualizado do cache.
        $this->listInstances(true);
        $instance = $this->findInstanceById($key) ?? $this->findInstanceByName($key);
        if (!$instance || trim((string) ($instance['id'] ?? '')) === '') {
            return false;
        }

        $this->rememberActiveInstance(
            (string) $instance['name'],
            (string) $instance['id'],
            (string) ($instance['token'] ?? '')
        );
        Cache::forget(self::INSTANCES_CACHE_KEY);

        Log::info('WhatsApp: instância ativa alterada', [
            'name' => $instance['name'],
            'id' => $instance['id'],
            'owner' => $instance['owner'] ?? null,
            'has_token' => trim((string) ($instance['token'] ?? '')) !== '',
        ]);

        return true;
    }

    private function rememberActiveInstance(string $name, string $id, string $token = ''): void
    {
        $name = trim($name);
        $id = trim($id);
        $token = trim($token);
        if ($id === '') {
            return;
        }

        Cache::forever(self::ACTIVE_INSTANCE_ID_CACHE_KEY, $id);
        if ($name !== '') {
            Cache::forever(self::ACTIVE_INSTANCE_CACHE_KEY, $name);
        }
        if ($token !== '') {
            Cache::forever(self::ACTIVE_INSTANCE_TOKEN_CACHE_KEY, $token);
        } else {
            Cache::forget(self::ACTIVE_INSTANCE_TOKEN_CACHE_KEY);
        }

        $this->persistSetting(self::DB_KEY_INSTANCE_ID, $id, 'UUID da instância WhatsApp ativa (selecionada na UI)');
        if ($name !== '') {
            $this->persistSetting(self::DB_KEY_INSTANCE_NAME, $name, 'Nome da instância WhatsApp ativa (selecionada na UI)');
        }
        if ($token !== '') {
            $this->persistSetting(self::DB_KEY_INSTANCE_TOKEN, $token, 'Token da instância WhatsApp ativa (selecionada na UI)');
        }
    }

    private function getPersistedSetting(string $chave): string
    {
        try {
            if (!Schema::hasTable('configuracoes_whatsapp')) {
                return '';
            }

            return trim((string) ConfiguracaoWhatsapp::getValor($chave, ''));
        } catch (\Throwable) {
            return '';
        }
    }

    private function persistSetting(string $chave, string $valor, ?string $descricao = null): void
    {
        try {
            if (!Schema::hasTable('configuracoes_whatsapp')) {
                return;
            }
            ConfiguracaoWhatsapp::setValor($chave, $valor, $descricao);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp: falha ao persistir instância ativa no banco', [
                'chave' => $chave,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Destinatário para a Evolution GO: telefone normalizado OU JID de grupo (@g.us) intacto.
     */
    public static function resolverDestinatario(string $destino): string
    {
        $destino = trim($destino);
        if ($destino !== '' && str_contains($destino, '@g.us')) {
            return $destino;
        }

        return self::normalizarNumero($destino);
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

        $instanceId = $this->resolveInstanceId();
        if ($instanceId === '') {
            return [
                'success' => false,
                'error' => 'Nenhuma instância WhatsApp ativa. Selecione uma em Notificações > Configuração WPP.',
            ];
        }

        $instanceToken = $this->resolveInstanceToken($instanceId);
        Log::info('WhatsApp: enviando texto', [
            'numero' => $numero,
            'instance_id' => $instanceId,
            'instance_name' => $this->getActiveInstanceName(),
            'using_instance_token' => $instanceToken !== '' && $instanceToken !== $this->apiKey,
        ]);

        $res = $this->postJson('send/text', $payload, $instanceId);
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
     * Lista grupos WhatsApp da instância ativa (Evolution GO GET /group/list).
     *
     * @return array{success: bool, groups?: array<int, array{jid: string, name: string}>, error?: string}
     */
    public function listGroups(): array
    {
        if (!$this->isConfigurado()) {
            return [
                'success' => false,
                'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_API_KEY e selecione uma instância ativa em Notificações > Configuração WPP.',
            ];
        }

        try {
            $res = Http::withHeaders($this->getApiHeaders())
                ->timeout(config('whatsapp.timeout', 120))
                ->get($this->buildUrl('group/list'));
            $body = $res->json() ?? [];

            if (!$this->isSuccessfulResponse($res, $body)) {
                return [
                    'success' => false,
                    'error' => $this->resolverMensagemErro($body) ?: 'Falha ao listar grupos do WhatsApp.',
                    'status' => $res->status(),
                ];
            }

            $raw = $body['groups']
                ?? $body['data']
                ?? $body['result']
                ?? (is_array($body) && array_is_list($body) ? $body : []);

            if (!is_array($raw)) {
                $raw = [];
            }

            $groups = [];
            foreach ($raw as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $jid = (string) ($item['JID'] ?? $item['jid'] ?? $item['id'] ?? $item['groupJid'] ?? '');
                $name = trim((string) ($item['Name'] ?? $item['name'] ?? $item['subject'] ?? ''));
                if ($jid === '' || !str_contains($jid, '@g.us')) {
                    continue;
                }
                $groups[] = [
                    'jid' => $jid,
                    'name' => $name !== '' ? $name : $jid,
                ];
            }

            usort($groups, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

            return ['success' => true, 'groups' => $groups];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp Evolution GO: falha ao listar grupos', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Falha ao listar grupos: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Envia mídia para um grupo WhatsApp (JID @g.us) via POST /send/media.
     * 1) tenta URL pública; 2) se falhar, envia base64 (Evolution não precisa baixar a URL).
     * Não normaliza o JID como telefone — preserva o sufixo @g.us.
     *
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function sendMediaToGroup(string $groupJid, string $mediaUrl, string $caption, string $type): array
    {
        if (!$this->isConfigurado()) {
            return [
                'success' => false,
                'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_API_KEY e selecione uma instância ativa em Notificações > Configuração WPP.',
            ];
        }

        $groupJid = trim($groupJid);
        if ($groupJid === '' || !str_contains($groupJid, '@g.us')) {
            return ['success' => false, 'error' => 'JID do grupo WhatsApp inválido.'];
        }

        $type = strtolower(trim($type));
        if (!in_array($type, ['image', 'video', 'document'], true)) {
            return ['success' => false, 'error' => 'Tipo de mídia inválido. Use: image, video ou document.'];
        }

        $mediaUrl = trim($mediaUrl);
        if ($mediaUrl === '' || !preg_match('#^https?://#i', $mediaUrl)) {
            return ['success' => false, 'error' => 'URL pública da mídia inválida para envio ao WhatsApp.'];
        }

        $filename = basename(parse_url($mediaUrl, PHP_URL_PATH) ?: '') ?: ($type === 'video' ? 'video.mp4' : 'imagem.jpg');
        $payload = [
            'number' => $groupJid,
            'url' => $mediaUrl,
            'type' => $type,
            'filename' => $filename,
        ];
        if (trim($caption) !== '') {
            $payload['caption'] = $caption;
        }

        Log::info('WhatsApp: enviando mídia para grupo (URL)', [
            'group_jid' => $groupJid,
            'type' => $type,
            'media_url' => $mediaUrl,
            'instance_id' => $this->resolveInstanceId(),
        ]);

        $res = $this->postJson('send/media', $payload);
        $body = $res->json() ?? [];

        if ($this->isSuccessfulResponse($res, $body)) {
            return ['success' => true, 'data' => $body];
        }

        $urlError = $this->resolverMensagemErro($body);
        Log::warning('WhatsApp Evolution GO: falha URL no grupo — tentando base64', [
            'group_jid' => $groupJid,
            'status' => $res->status(),
            'response' => $body,
        ]);

        $binary = $this->carregarBytesDaMidiaPublica($mediaUrl);
        if ($binary === null) {
            return [
                'success' => false,
                'error' => $urlError,
                'status' => $res->status(),
            ];
        }

        $mime = $type === 'video' ? 'video/mp4' : ($type === 'document' ? 'application/octet-stream' : 'image/jpeg');
        $fallback = $this->enviarMidiaBase64(
            $groupJid,
            base64_encode($binary),
            $type,
            $mime,
            $filename,
            $caption
        );

        if ($fallback['success'] ?? false) {
            return $fallback;
        }

        return [
            'success' => false,
            'error' => $fallback['error'] ?? $urlError,
            'status' => $fallback['status'] ?? $res->status(),
        ];
    }

    /**
     * Lê bytes da mídia a partir do disco local (storage/app/public) ou via HTTP.
     */
    private function carregarBytesDaMidiaPublica(string $mediaUrl): ?string
    {
        $path = parse_url($mediaUrl, PHP_URL_PATH) ?: '';
        if (preg_match('#/storage/(.+)$#', $path, $m)) {
            $relative = urldecode($m[1]);
            $full = storage_path('app/public/' . ltrim(str_replace('\\', '/', $relative), '/'));
            if (is_file($full)) {
                $contents = @file_get_contents($full);
                if ($contents !== false && $contents !== '') {
                    return $contents;
                }
            }
        }

        try {
            $res = Http::timeout(config('whatsapp.timeout', 120))->get($mediaUrl);
            if ($res->successful() && $res->body() !== '') {
                return $res->body();
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp: não foi possível baixar mídia pública para fallback', [
                'url' => $mediaUrl,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Configura webhook na Evolution GO via POST /instance/connect.
     *
     * @param  array<int, string>  $events
     */
    public function configurarWebhook(
        ?string $url = null,
        array $events = ['MESSAGE', 'SEND_MESSAGE', 'CONNECTION', 'READ_RECEIPT', 'QRCODE']
    ): bool {
        $url ??= (string) config('whatsapp.webhook_url', '');
        if ($url === '' || !$this->isConfigurado()) {
            return false;
        }

        $events = array_values(array_unique(array_merge(['MESSAGE', 'READ_RECEIPT'], $events)));
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
     * Evolution GO /send/media:
     * - url http(s): baixada pelo servidor
     * - url sem http(s): tratada como base64 PURO (sem prefixo data:)
     *   Enviar "data:image/...;base64,XXX" causa "invalid base64 encoding"
     *   porque a API tenta decodificar a string inteira.
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
        if (str_starts_with($base64, 'data:') && str_contains($base64, ',')) {
            $base64 = explode(',', $base64, 2)[1];
        }
        // Remove quebras/espaços que quebram o decode no Evolution GO.
        $base64 = preg_replace('/\s+/', '', $base64) ?? '';

        if ($base64 === '' || base64_decode($base64, true) === false) {
            return ['success' => false, 'error' => 'Conteúdo base64 inválido para envio de mídia.'];
        }

        $destinatario = self::resolverDestinatario($numero);
        $payload = [
            'number' => $destinatario,
            'type' => $tipo,
            // Base64 puro — NÃO usar data URI (v0.7+ do Evolution GO).
            'url' => $base64,
            'filename' => $fileName !== '' ? $fileName : 'arquivo',
        ];
        if (trim($legenda) !== '') {
            $payload['caption'] = $legenda;
        }
        // Alguns builds aceitam mimetype auxiliar; não atrapalha se ignorado.
        if ($mime !== '') {
            $payload['mimetype'] = $mime;
        }

        $res = $this->postJson('send/media', $payload);
        $body = $res->json() ?? [];

        if ($this->isSuccessfulResponse($res, $body)) {
            return ['success' => true, 'data' => $body];
        }

        // Fallback: multipart com arquivo binário (quando JSON base64 falhar).
        $decoded = base64_decode($base64, true);
        if ($decoded !== false && $decoded !== '') {
            $fallback = $this->enviarMidiaMultipart(
                $destinatario,
                $decoded,
                $tipo,
                $mime,
                $fileName,
                $legenda
            );
            if ($fallback['success'] ?? false) {
                return $fallback;
            }

            Log::warning('WhatsApp Evolution GO: falha ao enviar mídia (JSON e multipart)', [
                'status' => $res->status(),
                'tipo' => $tipo,
                'numero' => $destinatario,
                'json_response' => $body,
                'multipart_error' => $fallback['error'] ?? null,
            ]);

            return [
                'success' => false,
                'error' => $this->resolverMensagemErro($body) ?: ($fallback['error'] ?? 'Falha ao enviar mídia'),
                'status' => $res->status(),
            ];
        }

        Log::warning('WhatsApp Evolution GO: falha ao enviar mídia', [
            'status' => $res->status(),
            'tipo' => $tipo,
            'numero' => $destinatario,
            'response' => $body,
        ]);

        return [
            'success' => false,
            'error' => $this->resolverMensagemErro($body),
            'status' => $res->status(),
        ];
    }

    /**
     * Fallback multipart/form-data para /send/media (upload binário).
     *
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    private function enviarMidiaMultipart(
        string $numero,
        string $binary,
        string $tipo,
        string $mime,
        string $fileName,
        string $legenda = ''
    ): array {
        $fileName = $fileName !== '' ? $fileName : 'arquivo';

        try {
            $fields = [
                'number' => self::resolverDestinatario($numero),
                'type' => $tipo,
                'filename' => $fileName,
            ];
            if (trim($legenda) !== '') {
                $fields['caption'] = $legenda;
            }
            if ($mime !== '') {
                $fields['mimetype'] = $mime;
            }

            $headers = $this->getApiHeaders();
            unset($headers['Content-Type']); // multipart define o boundary

            $res = Http::withHeaders($headers)
                ->timeout(config('whatsapp.timeout', 120))
                ->attach(
                    'file',
                    $binary,
                    $fileName,
                    $mime !== '' ? ['Content-Type' => $mime] : []
                )
                ->post($this->buildUrl('send/media'), $fields);

            $body = $res->json() ?? [];

            if ($this->isSuccessfulResponse($res, $body)) {
                return ['success' => true, 'data' => $body];
            }

            return [
                'success' => false,
                'error' => $this->resolverMensagemErro($body),
                'status' => $res->status(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Falha no upload multipart: ' . $e->getMessage()];
        }
    }

    /**
     * Verificação de conexão para monitoramento (comando agendado e tela de config).
     * Normaliza o resultado de getConnectionStatus() e nunca lança exceção:
     * falha na consulta à API vira status "erro_consulta".
     *
     * @return array{status: string, connected: bool, instance_name: string, instance_id: string, raw_state: ?string}
     */
    public function checkConnectionStatus(): array
    {
        $instanceName = $this->getActiveInstanceName();
        $instanceId = $this->getActiveInstanceId();

        try {
            $status = $this->getConnectionStatus();
            $connected = (bool) ($status['connected'] ?? false);

            return [
                'status' => $connected
                    ? \App\Models\WhatsAppConnectionLog::STATUS_CONECTADO
                    : \App\Models\WhatsAppConnectionLog::STATUS_DESCONECTADO,
                'connected' => $connected,
                'instance_name' => $instanceName,
                'instance_id' => $instanceId,
                'raw_state' => (string) ($status['state'] ?? ''),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => \App\Models\WhatsAppConnectionLog::STATUS_ERRO_CONSULTA,
                'connected' => false,
                'instance_name' => $instanceName,
                'instance_id' => $instanceId,
                'raw_state' => mb_substr($e->getMessage(), 0, 255),
            ];
        }
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

    private function postJson(
        string $endpoint,
        array $payload = [],
        ?string $instanceId = null,
        bool $useActiveInstanceToken = true
    ): \Illuminate\Http\Client\Response {
        return Http::withHeaders($this->getApiHeaders($instanceId, $useActiveInstanceToken))
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
            $error = (string) ($error['message'] ?? $error['code'] ?? json_encode($error));
        }
        if (is_string($error) && $error !== '') {
            if (stripos($error, 'not registered on WhatsApp') !== false) {
                return 'Número não está registrado no WhatsApp (verifique DDD + número).';
            }

            if (stripos($error, 'error 420') !== false || preg_match('/\b420\b/', $error)) {
                return 'WhatsApp recusou o envio (erro 420). '
                    . 'Isso é comum em canais de anúncio de Comunidade — use um grupo normal. '
                    . 'Também pode ocorrer se a mídia não estiver acessível publicamente; '
                    . 'confira se a URL abre no navegador e tente novamente.';
            }

            return $error;
        }

        if (!empty($body['message']) && is_string($body['message']) && strtolower($body['message']) !== 'success') {
            $message = (string) $body['message'];
            if (stripos($message, 'error 420') !== false || preg_match('/\b420\b/', $message)) {
                return 'WhatsApp recusou o envio (erro 420). '
                    . 'Isso é comum em canais de anúncio de Comunidade — use um grupo normal. '
                    . 'Também pode ocorrer se a mídia não estiver acessível publicamente; '
                    . 'confira se a URL abre no navegador e tente novamente.';
            }

            return $message;
        }

        return 'Erro ao comunicar com a Evolution GO';
    }
}
