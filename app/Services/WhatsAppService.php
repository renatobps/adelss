<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private string $apiKey;
    private string $instanceName;
    private string $instanceId;
    private string $instanceToken;

    public function __construct()
    {
        $this->apiUrl = rtrim((string) (config('whatsapp.api_url') ?? ''), '/');
        $this->apiKey = (string) (config('whatsapp.api_key') ?? '');
        $this->instanceName = (string) (config('whatsapp.instance_name') ?? '');
        $this->instanceId = (string) (config('whatsapp.instance_id') ?? '');
        $this->instanceToken = (string) (config('whatsapp.instance_token') ?? '');
    }

    private function getApiHeaders(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        if (!empty($this->apiKey)) {
            $headers['apikey'] = $this->apiKey;
            return $headers;
        }

        $legacyToken = config('whatsapp.client_token');
        if ($legacyToken) {
            $headers['Client-Token'] = $legacyToken;
        }
        return $headers;
    }

    private function buildEvolutionUrl(string $endpoint): string
    {
        return "{$this->apiUrl}/message/{$endpoint}/{$this->instanceName}";
    }

    private function buildUrl(string $endpoint): string
    {
        return "{$this->apiUrl}/instances/{$this->instanceId}/token/{$this->instanceToken}/{$endpoint}";
    }

    /**
     * Normaliza número para formato internacional (55...).
     */
    public static function normalizarNumero(string $numero): string
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
                'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_API_KEY e WHATSAPP_INSTANCE_NAME no .env',
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
     * Envia imagem por URL.
     *
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarImagem(string $numero, string $imageUrl, string $caption = ''): array
    {
        $numero = self::normalizarNumero($numero);
        return $this->enviarComPayload('send-image', [
            'phone' => $numero,
            'image' => $imageUrl,
            'caption' => $caption,
        ], 'Erro ao enviar imagem');
    }

    /**
     * Envia vídeo por URL.
     *
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarVideo(string $numero, string $videoUrl, string $caption = ''): array
    {
        $numero = self::normalizarNumero($numero);
        return $this->enviarComPayload('send-video', [
            'phone' => $numero,
            'video' => $videoUrl,
            'caption' => $caption,
        ], 'Erro ao enviar vídeo');
    }

    /**
     * Verifica se a API está configurada (não testa conexão).
     */
    public function isConfigurado(): bool
    {
        return !empty($this->apiUrl)
            && (
                (!empty($this->apiKey) && !empty($this->instanceName))
                || (!empty($this->instanceId) && !empty($this->instanceToken))
            );
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
            return ['success' => false, 'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_API_KEY e WHATSAPP_INSTANCE_NAME no .env'];
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
                'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_API_KEY e WHATSAPP_INSTANCE_NAME no .env',
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
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    private function enviarComPayload(string $endpoint, array $payload, string $erroPadrao): array
    {
        if (empty($this->apiUrl) || empty($this->instanceId) || empty($this->instanceToken)) {
            Log::warning('WhatsApp: credenciais não configuradas.');
            return [
                'success' => false,
                'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_INSTANCE_ID e WHATSAPP_INSTANCE_TOKEN no .env',
            ];
        }

        $url = $this->buildUrl($endpoint);
        $res = $this->postJson($url, $payload);

        $body = $res->json() ?? [];
        if ($res->successful() && empty($body['error'])) {
            return ['success' => true, 'data' => $body];
        }

        return [
            'success' => false,
            'error' => $body['message'] ?? $body['error'] ?? $erroPadrao,
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
