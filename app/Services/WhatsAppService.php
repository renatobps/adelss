<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private string $instanceId;
    private string $instanceToken;

    public function __construct()
    {
        $this->apiUrl = rtrim((string) (config('whatsapp.api_url') ?? ''), '/');
        $this->instanceId = (string) (config('whatsapp.instance_id') ?? '');
        $this->instanceToken = (string) (config('whatsapp.instance_token') ?? '');
    }

    private function getApiHeaders(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        $token = config('whatsapp.client_token');
        if ($token) {
            $headers['Client-Token'] = $token;
        }
        return $headers;
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
        if (empty($this->apiUrl) || empty($this->instanceId) || empty($this->instanceToken)) {
            Log::warning('WhatsApp: credenciais não configuradas.');
            return [
                'success' => false,
                'error' => 'Configure WHATSAPP_API_URL, WHATSAPP_INSTANCE_ID e WHATSAPP_INSTANCE_TOKEN no .env',
            ];
        }

        $numero = self::normalizarNumero($numero);
        $payload = [
            'phone' => $numero,
            'message' => $mensagem,
        ];
        if ($delayMs > 0) {
            $payload['delayMessage'] = max(1, (int) ($delayMs / 1000));
        }

        $url = $this->buildUrl('send-text');
        $res = Http::withHeaders($this->getApiHeaders())
            ->asJson()
            ->timeout(config('whatsapp.timeout', 120))
            ->post($url, $payload);

        $body = $res->json() ?? [];
        if ($res->successful() && empty($body['error'])) {
            return ['success' => true, 'data' => $body];
        }
        return [
            'success' => false,
            'error' => $body['message'] ?? $body['error'] ?? 'Erro ao enviar mensagem',
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
        return !empty($this->apiUrl) && !empty($this->instanceId) && !empty($this->instanceToken);
    }

    /**
     * Envia enquete com botões clicáveis (Z-API send-button-list).
     * WhatsApp permite no máximo 3 botões; opções excedentes são truncadas.
     *
     * @param string $numero Número do destinatário
     * @param string $mensagem Texto da enquete
     * @param array $opcoes Array de strings (opções); cada uma vira um botão
     * @param int|null $enqueteId ID da enquete (opcional)
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarEnquetePoll(string $numero, string $mensagem, array $opcoes, ?int $enqueteId = null): array
    {
        if (empty($this->apiUrl) || empty($this->instanceId) || empty($this->instanceToken)) {
            Log::warning('WhatsApp: credenciais não configuradas para enquete.');
            return ['success' => false, 'error' => 'Configure WHATSAPP_* no .env'];
        }

        $numero = self::normalizarNumero($numero);

        $botoes = [];
        foreach ($opcoes as $index => $opcao) {
            $label = is_string($opcao) ? $opcao : (string) ($opcao['name'] ?? $opcao['label'] ?? $opcao['text'] ?? reset($opcao));
            $label = mb_substr($label, 0, 20);
            $botoes[] = ['id' => (string) ($index + 1), 'label' => $label];
        }

        if (count($botoes) < 2) {
            return ['success' => false, 'error' => 'Enquetes requerem pelo menos 2 opções.'];
        }
        if (count($botoes) > 3) {
            $botoes = array_slice($botoes, 0, 3);
        }

        $url = $this->buildUrl('send-button-list');
        $payload = [
            'phone' => $numero,
            'message' => $mensagem,
            'buttonList' => ['buttons' => $botoes],
        ];

        $res = Http::withHeaders($this->getApiHeaders())
            ->asJson()
            ->timeout(config('whatsapp.timeout', 120))
            ->post($url, $payload);

        $body = $res->json() ?? [];
        if ($res->successful() && empty($body['error'])) {
            return ['success' => true, 'data' => $body];
        }
        return [
            'success' => false,
            'error' => $body['message'] ?? $body['error'] ?? 'Erro ao enviar enquete',
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
        $res = Http::withHeaders($this->getApiHeaders())
            ->asJson()
            ->timeout(config('whatsapp.timeout', 120))
            ->post($url, $payload);

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
}
