<?php

namespace App\Services\Payments;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoService
{
    public function __construct()
    {
        $accessToken = (string) config('mercadopago.access_token', '');
        if ($accessToken !== '') {
            MercadoPagoConfig::setAccessToken($accessToken);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function createPixPayment(array $payload, string $idempotencyKey): array
    {
        $request = [
            'transaction_amount' => (float) $payload['amount'],
            'description' => (string) $payload['description'],
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => (string) $payload['payer_email'],
                'identification' => [
                    'type' => (string) ($payload['payer_document_type'] ?? 'CPF'),
                    'number' => (string) $payload['payer_document'],
                ],
            ],
            'notification_url' => $this->notificationUrl(),
            'external_reference' => (string) $payload['external_reference'],
        ];

        if ($this->statementDescriptor() !== '') {
            $request['statement_descriptor'] = $this->statementDescriptor();
        }

        return $this->createPayment($request, $idempotencyKey);
    }

    /**
     * @return array<string, mixed>
     */
    public function createCardPayment(array $payload, string $idempotencyKey): array
    {
        $request = [
            'transaction_amount' => (float) $payload['amount'],
            'token' => (string) $payload['token'],
            'description' => (string) $payload['description'],
            'installments' => (int) $payload['installments'],
            'payment_method_id' => (string) $payload['payment_method_id'],
            'payer' => [
                'email' => (string) $payload['payer_email'],
                'identification' => [
                    'type' => (string) ($payload['payer_document_type'] ?? 'CPF'),
                    'number' => (string) $payload['payer_document'],
                ],
            ],
            'notification_url' => $this->notificationUrl(),
            'external_reference' => (string) $payload['external_reference'],
        ];

        if (!empty($payload['issuer_id'])) {
            $request['issuer_id'] = (int) $payload['issuer_id'];
        }

        if ($this->statementDescriptor() !== '') {
            $request['statement_descriptor'] = $this->statementDescriptor();
        }

        return $this->createPayment($request, $idempotencyKey);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayment(string|int $paymentId): array
    {
        try {
            $client = new PaymentClient();
            $payment = $client->get((int) $paymentId);

            return $this->normalizeResource($payment);
        } catch (MPApiException $e) {
            $apiResponse = $e->getApiResponse();
            $content = $apiResponse ? $apiResponse->getContent() : null;
            Log::warning('Mercado Pago consulta de pagamento falhou', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
                'response' => $content,
            ]);

            throw new \RuntimeException(
                Arr::get($content, 'message', $e->getMessage())
            );
        } catch (\Throwable $e) {
            Log::warning('Mercado Pago consulta de pagamento falhou', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);

            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    private function createPayment(array $request, string $idempotencyKey): array
    {
        try {
            $requestOptions = new RequestOptions();
            $requestOptions->setCustomHeaders([
                'x-idempotency-key: ' . $idempotencyKey,
            ]);

            $client = new PaymentClient();
            $payment = $client->create($request, $requestOptions);

            return $this->normalizeResource($payment);
        } catch (MPApiException $e) {
            $apiResponse = $e->getApiResponse();
            $content = $apiResponse ? $apiResponse->getContent() : null;
            Log::warning('Mercado Pago criação de pagamento falhou', [
                'message' => $e->getMessage(),
                'response' => $content,
                'request' => $request,
            ]);

            throw new \RuntimeException(
                Arr::get($content, 'message', $e->getMessage())
            );
        } catch (\Throwable $e) {
            Log::warning('Mercado Pago criação de pagamento falhou', [
                'message' => $e->getMessage(),
                'request' => $request,
            ]);

            throw new \RuntimeException($e->getMessage());
        }
    }

    private function statementDescriptor(): string
    {
        return mb_substr(trim((string) config('mercadopago.statement_descriptor', '')), 0, 13);
    }

    private function notificationUrl(): string
    {
        $configured = trim((string) config('mercadopago.notification_url', ''));
        if ($configured !== '') {
            return $configured;
        }

        return route('webhooks.mercadopago');
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeResource(mixed $resource): array
    {
        return json_decode(json_encode($resource, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
