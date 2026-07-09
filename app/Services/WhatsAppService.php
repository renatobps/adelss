<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private const ACTIVE_INSTANCE_CACHE_KEY = 'whatsapp.active_instance_name';

    private string $apiUrl;
    private string $apiKey;
    private string $instanceName;
    private string $configuredInstanceName;

    public function __construct()
    {
        $this->apiUrl = rtrim((string) (config('whatsapp.api_url') ?? ''), '/');
        $this->apiKey = (string) (config('whatsapp.api_key') ?? '');
        $this->configuredInstanceName = (string) (config('whatsapp.instance_name') ?? '');
        $this->instanceName = $this->resolveActiveInstanceName();
    }

    private function resolveActiveInstanceName(): string
    {
        $selected = Cache::get(self::ACTIVE_INSTANCE_CACHE_KEY);
        if (is_string($selected) && trim($selected) !== '') {
            return trim($selected);
        }

        return trim($this->configuredInstanceName);
    }

    private function getApiHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'apikey' => $this->apiKey,
        ];
    }

    private function buildEvolutionUrl(string $endpoint): string
    {
        return "{$this->apiUrl}/message/{$endpoint}/{$this->instanceName}";
    }

    private function buildInstanceUrl(string $endpoint): string
    {
        return "{$this->apiUrl}/" . ltrim($endpoint, '/');
    }

    /**
     * Normaliza número para formato internacional (55...) com 9º dígito em celulares BR.
     */
    public static function normalizarNumero(string $numero): string
    {
        return self::aplicarNonoDigitoBr(self::normalizarNumeroBasico($numero));
    }

    /**
     * Retorna variantes do número (com e sem 9º dígito) para comparação/busca.
     *
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
     * Envia mensagem de texto para um número.
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarMensagem(string $numero, string $mensagem, int $delayMs = 700): array
    {
        if (empty($this->apiUrl) || empty($this->apiKey) || empty($this->instanceName)) {
            Log::warning('WhatsApp Evolution: credenciais não configuradas.');
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

        $url = $this->buildEvolutionUrl('sendText');
        $res = $this->postJson($url, $payload);

        // Compatibilidade com variações de payload em algumas versões.
        if ($res->status() === 400) {
            $fallbackPayload = [
                'number' => $numero,
                'textMessage' => ['text' => $mensagem],
            ];
            if ($delayMs > 0) {
                $fallbackPayload['delay'] = max(1, $delayMs);
            }
            $res = $this->postJson($url, $fallbackPayload);
        }

        $body = $res->json() ?? [];
        if ($res->successful() && empty($body['error'])) {
            return ['success' => true, 'data' => $body];
        }
        return [
            'success' => false,
            'error' => $this->resolverMensagemErro($body),
            'status' => $res->status(),
        ];
    }

    /**
     * Verifica se a API está configurada (não testa conexão).
     */
    public function isConfigurado(): bool
    {
        return !empty($this->apiUrl) && !empty($this->apiKey) && !empty($this->instanceName);
    }

    /**
     * Registra webhook na Evolution API para a instância ativa.
     */
    public function configurarWebhook(?string $url = null, array $events = ['MESSAGES_UPSERT']): bool
    {
        $url ??= (string) config('whatsapp.webhook_url', '');

        if ($url === '' || !$this->isConfigurado()) {
            return false;
        }

        $url = rtrim($url, '/');
        $instance = $this->instanceName;
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

        foreach ($payloads as $payload) {
            $response = Http::withHeaders($this->getApiHeaders())
                ->timeout(20)
                ->post($this->buildInstanceUrl("webhook/set/{$instance}"), $payload);

            if ($response->successful()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Envia enquete com botões clicáveis (Evolution API sendButtons).
     * WhatsApp permite no máximo 3 botões; opções excedentes são truncadas.
     *
     * @param string $numero Número do destinatário
     * @param string $titulo Título da enquete
     * @param string $descricao Texto descritivo da enquete
     * @param array $opcoes Array de strings (opções); cada uma vira um botão
     * @param int|null $enqueteId ID da enquete (opcional)
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarEnquetePoll(string $numero, string $titulo, string $descricao, array $opcoes, ?int $enqueteId = null): array
    {
        if (empty($this->apiUrl) || empty($this->apiKey) || empty($this->instanceName)) {
            Log::warning('WhatsApp Evolution: credenciais não configuradas para enquete.');
            return ['success' => false, 'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_API_KEY e selecione uma instância ativa em Notificações > Configuração WPP.'];
        }

        $numero = self::normalizarNumero($numero);

        $botoes = [];
        foreach ($opcoes as $index => $opcao) {
            $label = is_string($opcao) ? $opcao : (string) ($opcao['name'] ?? $opcao['label'] ?? $opcao['text'] ?? reset($opcao));
            $label = mb_substr(trim($label), 0, 20);
            $buttonId = (string) ($index + 1);
            $botoes[] = [
                'type' => 'reply',
                'displayText' => $label,
                'id' => $buttonId,
            ];
        }

        if (count($botoes) < 2) {
            return ['success' => false, 'error' => 'Enquetes requerem pelo menos 2 opções.'];
        }
        if (count($botoes) > 3) {
            $botoes = array_slice($botoes, 0, 3);
        }

        $url = $this->buildEvolutionUrl('sendButtons');
        $payload = [
            'number' => $numero,
            'title' => mb_substr(trim($titulo) !== '' ? $titulo : 'Enquete', 0, 30),
            'description' => mb_substr(trim($descricao) !== '' ? $descricao : 'Selecione uma opção:', 0, 120),
            'footer' => 'ADELSS',
            'buttons' => $botoes,
        ];

        $res = $this->postJson($url, $payload);

        $body = $res->json() ?? [];
        if ($res->successful() && empty($body['error'])) {
            return ['success' => true, 'data' => $body];
        }
        Log::warning('WhatsApp Evolution: falha ao enviar enquete', [
            'status' => $res->status(),
            'url' => $url,
            'payload' => $payload,
            'response' => $body,
        ]);
        return [
            'success' => false,
            'error' => $this->resolverMensagemErro($body),
            'status' => $res->status(),
        ];
    }

    /**
     * Envia arquivo de mídia usando Evolution API (sendMedia).
     *
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarMidiaArquivo(
        string $numero,
        UploadedFile $arquivo,
        string $tipoMidia,
        bool $isPdfDocumento = false,
        ?string $fileName = null,
        string $legenda = ''
    ): array
    {
        if (empty($this->apiUrl) || empty($this->apiKey) || empty($this->instanceName)) {
            Log::warning('WhatsApp Evolution: credenciais não configuradas para envio de mídia.');
            return [
                'success' => false,
                'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_API_KEY e selecione uma instância ativa em Notificações > Configuração WPP.',
            ];
        }

        $tipoMidia = strtolower(trim($tipoMidia));
        $tiposAceitos = ['image', 'document', 'video', 'audio'];
        if (!in_array($tipoMidia, $tiposAceitos, true)) {
            return [
                'success' => false,
                'error' => 'Tipo de mídia inválido. Use: image, document, video ou audio.',
            ];
        }
        $numero = self::normalizarNumero($numero);
        $url = $this->buildEvolutionUrl('sendMedia');

        $payload = [
            'number' => $numero,
            'mediatype' => $tipoMidia,
        ];
        if ($isPdfDocumento) {
            $payload['mimetype'] = 'application/pdf';
            $payload['fileName'] = $fileName ?: 'proposta.pdf';
        }
        if (trim($legenda) !== '') {
            $payload['caption'] = $legenda;
        }

        $res = Http::withHeaders(['apikey' => $this->apiKey])
            ->timeout(config('whatsapp.timeout', 120))
            ->attach(
                'file',
                fopen($arquivo->getRealPath(), 'r'),
                $arquivo->getClientOriginalName()
            )
            ->post($url, $payload);

        $body = $res->json() ?? [];
        if ($res->successful() && empty($body['error'])) {
            return ['success' => true, 'data' => $body];
        }

        Log::warning('WhatsApp Evolution: falha ao enviar mídia', [
            'status' => $res->status(),
            'url' => $url,
            'numero' => $numero,
            'mediatype' => $tipoMidia,
            'is_pdf_document' => $isPdfDocumento,
            'response' => $body,
        ]);

        return [
            'success' => false,
            'error' => $this->resolverMensagemErro($body),
            'status' => $res->status(),
        ];
    }

    /**
     * Envia imagem em base64 (ex.: QR Code PIX) usando sendMedia.
     *
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarImagemBase64(string $numero, string $imagemBase64, string $legenda = ''): array
    {
        if (empty($this->apiUrl) || empty($this->apiKey) || empty($this->instanceName)) {
            Log::warning('WhatsApp Evolution: credenciais não configuradas para envio de imagem base64.');
            return [
                'success' => false,
                'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_API_KEY e selecione uma instância ativa em Notificações > Configuração WPP.',
            ];
        }

        $conteudo = trim($imagemBase64);
        if ($conteudo === '') {
            return [
                'success' => false,
                'error' => 'Imagem base64 vazia para envio.',
            ];
        }

        if (str_starts_with($conteudo, 'data:image') && str_contains($conteudo, ',')) {
            [, $conteudo] = explode(',', $conteudo, 2);
        }

        $binario = base64_decode($conteudo, true);
        if ($binario === false) {
            return [
                'success' => false,
                'error' => 'Imagem base64 inválida para envio.',
            ];
        }

        $numero = self::normalizarNumero($numero);
        $url = $this->buildEvolutionUrl('sendMedia');
        $tempPath = tempnam(sys_get_temp_dir(), 'wa-pix-');

        if ($tempPath === false) {
            return [
                'success' => false,
                'error' => 'Não foi possível preparar arquivo temporário para envio.',
            ];
        }

        file_put_contents($tempPath, $binario);

        try {
            $payload = [
                'number' => $numero,
                'mediatype' => 'image',
                'mimetype' => 'image/png',
                'fileName' => 'pix-qrcode.png',
            ];

            if (trim($legenda) !== '') {
                $payload['caption'] = $legenda;
            }

            $res = Http::withHeaders(['apikey' => $this->apiKey])
                ->timeout(config('whatsapp.timeout', 120))
                ->attach('file', fopen($tempPath, 'r'), 'pix-qrcode.png')
                ->post($url, $payload);

            $body = $res->json() ?? [];
            if ($res->successful() && empty($body['error'])) {
                return ['success' => true, 'data' => $body];
            }

            Log::warning('WhatsApp Evolution: falha ao enviar imagem base64', [
                'status' => $res->status(),
                'url' => $url,
                'numero' => $numero,
                'response' => $body,
            ]);

            return [
                'success' => false,
                'error' => $this->resolverMensagemErro($body),
                'status' => $res->status(),
            ];
        } finally {
            @unlink($tempPath);
        }
    }

    /**
     * Envia documento PDF a partir de um caminho local.
     *
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarDocumentoArquivo(
        string $numero,
        string $filePath,
        string $fileName = 'documento.pdf',
        string $legenda = ''
    ): array {
        if (empty($this->apiUrl) || empty($this->apiKey) || empty($this->instanceName)) {
            return [
                'success' => false,
                'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_API_KEY e selecione uma instância ativa em Notificações > Configuração WPP.',
            ];
        }

        if (!is_file($filePath)) {
            return ['success' => false, 'error' => 'Arquivo PDF não encontrado para envio.'];
        }

        $numero = self::normalizarNumero($numero);
        $url = $this->buildEvolutionUrl('sendMedia');
        $payload = [
            'number' => $numero,
            'mediatype' => 'document',
            'mimetype' => 'application/pdf',
            'fileName' => $fileName,
        ];

        if (trim($legenda) !== '') {
            $payload['caption'] = $legenda;
        }

        $res = Http::withHeaders(['apikey' => $this->apiKey])
            ->timeout(config('whatsapp.timeout', 120))
            ->attach('file', fopen($filePath, 'r'), $fileName)
            ->post($url, $payload);

        $body = $res->json() ?? [];
        if ($res->successful() && empty($body['error'])) {
            return ['success' => true, 'data' => $body];
        }

        return [
            'success' => false,
            'error' => $this->resolverMensagemErro($body),
            'status' => $res->status(),
        ];
    }

    private function postJson(string $url, array $payload): \Illuminate\Http\Client\Response
    {
        return Http::withHeaders($this->getApiHeaders())
            ->asJson()
            ->timeout(config('whatsapp.timeout', 120))
            ->post($url, $payload);
    }

    private function resolverMensagemErro(array $body): string
    {
        $nestedMessages = $body['response']['message'] ?? null;
        if (is_array($nestedMessages) && isset($nestedMessages[0]) && is_array($nestedMessages[0])) {
            $first = $nestedMessages[0];
            if (($first['exists'] ?? true) === false && !empty($first['number'])) {
                return 'Número não existe no WhatsApp: ' . $first['number'];
            }
            if (!empty($first['message']) && is_string($first['message'])) {
                return $first['message'];
            }
        }

        $error = $body['error'] ?? null;
        if (is_array($error)) {
            return (string) ($error['message'] ?? $error['code'] ?? json_encode($error));
        }

        if (!empty($body['response']['message']) && is_string($body['response']['message'])) {
            return (string) $body['response']['message'];
        }

        return (string) ($body['message'] ?? $error ?? 'Erro ao enviar mensagem');
    }

}
