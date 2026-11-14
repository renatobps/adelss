<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use App\Models\EventRegistrationPayment;
use App\Models\PaymentTransaction;
use App\Services\EventRegistrationReceiptService;
use App\Services\FinancialNotificationService;
use App\Services\Payments\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MercadoPagoWebhookController extends Controller
{
    private ?\App\Models\MercadoPagoAccount $webhookAccount = null;
    public function __construct(
        private MercadoPagoService $mercadoPagoService,
        private FinancialNotificationService $financialNotificationService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $this->webhookAccount = null;
        if ($request->query('account') !== null) {
            $uuid = $request->query('account');
            if (!is_string($uuid) || !\Illuminate\Support\Str::isUuid($uuid) || !Schema::hasTable('mercado_pago_accounts')) {
                return response()->json(['success' => false, 'error' => 'Conta inválida.'], 401);
            }
            // Archived versions remain usable only for callbacks of old charges.
            $this->webhookAccount = \App\Models\MercadoPagoAccount::where('uuid', $uuid)->first();
            if (!$this->webhookAccount) return response()->json(['success' => false, 'error' => 'Conta inválida.'], 401);
            $this->mercadoPagoService->useAccount($this->webhookAccount);
        }
        if (!$this->isSignatureValid($request)) {
            return response()->json(['success' => false, 'error' => 'Assinatura inválida.'], 401);
        }

        $topic = strtolower((string) ($request->input('type') ?: $request->query('type') ?: $request->input('topic') ?: $request->query('topic') ?: ''));
        $paymentId = $request->input('data.id') ?: $request->query('data.id') ?: $request->input('id') ?: $request->query('id');
        $isPayment = $topic === '' || $topic === 'payment' || str_starts_with($topic, 'payment');
        if (!$paymentId || ! $isPayment) {
            return response()->json(['success' => true, 'message' => 'Evento ignorado.']);
        }

        try {
            $payment = $this->mercadoPagoService->getPayment((string) $paymentId);
            if ($this->webhookAccount && isset($payment['collector_id']) && (string) $payment['collector_id'] !== (string) $this->webhookAccount->merchant_id) {
                throw new \RuntimeException('O pagamento não pertence à conta informada.');
            }
            $pending = $this->syncPayment($payment, $request->all());
        } catch (\Throwable $e) {
            Log::warning('Mercado Pago webhook processamento falhou', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        $this->dispatchPendingNotifications($pending, $payment ?? []);

        return response()->json(['success' => true]);
    }

    /**
     * @return array{
     *     confirmedRegistration: ?EventRegistration,
     *     receiptTransaction: ?\App\Models\FinancialTransaction,
     *     mpMovement: ?array{direction: string, payment: array<string, mixed>, transaction: ?\App\Models\FinancialTransaction}
     * }
     */
    private function syncPayment(array $payment, array $webhookPayload): array
    {
        $externalId = (string) ($payment['id'] ?? '');
        if ($externalId === '') {
            throw new \RuntimeException('Pagamento sem ID externo.');
        }

        $status = (string) ($payment['status'] ?? '');
        $mpMovementDirection = match ($status) {
            'approved' => 'in',
            'refunded', 'charged_back' => 'out',
            default => null,
        };

        return DB::transaction(function () use ($externalId, $payment, $webhookPayload, $mpMovementDirection) {
            $confirmedRegistration = null;
            $receiptTransaction = null;
            $mpTransaction = null;
            $handled = false;
            /** @var PaymentTransaction|null $paymentTransaction */
            $paymentTransaction = $this->webhookAccount ? null : PaymentTransaction::query()
                ->where('external_payment_id', $externalId)
                ->lockForUpdate()
                ->first();

            if ($paymentTransaction) {
                $handled = true;
                $status = (string) ($payment['status'] ?? $paymentTransaction->status);
                $statusDetail = (string) ($payment['status_detail'] ?? $paymentTransaction->status_detail);
                $wasPaid = $paymentTransaction->status === 'approved';

                $paymentTransaction->update([
                    'status' => $status,
                    'status_detail' => $statusDetail,
                    'payment_method' => (string) ($payment['payment_method_id'] ?? $paymentTransaction->payment_method),
                    'payer_email' => data_get($payment, 'payer.email') ?: $paymentTransaction->payer_email,
                    'payer_document' => data_get($payment, 'payer.identification.number') ?: $paymentTransaction->payer_document,
                    'qr_code_base64' => data_get($payment, 'point_of_interaction.transaction_data.qr_code_base64') ?: $paymentTransaction->qr_code_base64,
                    'qr_code_text' => data_get($payment, 'point_of_interaction.transaction_data.qr_code') ?: $paymentTransaction->qr_code_text,
                    'paid_at' => $status === 'approved' ? now() : $paymentTransaction->paid_at,
                    'raw_payload' => $payment,
                    'webhook_payload' => $webhookPayload,
                    'error_message' => $status === 'rejected' ? $statusDetail : null,
                ]);

                $financialTransaction = $paymentTransaction->financialTransaction()->lockForUpdate()->first();
                if ($financialTransaction) {
                    $mpTransaction = $financialTransaction;
                    if ($status === 'approved') {
                        if (!$financialTransaction->is_paid) {
                            $financialTransaction->update([
                                'is_paid' => true,
                                'status' => 'recebido',
                            ]);
                            $financialTransaction->refresh()->load(['member', 'category']);
                            $receiptTransaction = $financialTransaction;
                        }
                    } elseif (in_array($status, ['rejected', 'cancelled', 'refunded', 'charged_back'], true) && $financialTransaction->type === 'receita') {
                        $financialTransaction->update([
                            'is_paid' => false,
                            'status' => 'a_receber',
                        ]);
                    }
                }

                if ($wasPaid && $status === 'approved') {
                    $mpMovementDirection = null;
                }
            }

            /** @var EventRegistrationPayment|null $eventRegistrationPayment */
            $eventRegistrationPayment = null;
            if (Schema::hasTable('event_registration_payments')) {
                $eventRegistrationPayment = EventRegistrationPayment::query()
                    ->when($this->webhookAccount, fn ($query) => $query->where('mercado_pago_account_id', $this->webhookAccount->id))
                    ->when(!$this->webhookAccount && Schema::hasColumn('event_registration_payments', 'mercado_pago_account_id'), fn ($query) => $query->whereNull('mercado_pago_account_id'))
                    ->where('external_payment_id', $externalId)
                    ->lockForUpdate()
                    ->first();
            }

            if ($eventRegistrationPayment) {
                $handled = true;
                $status = (string) ($payment['status'] ?? $eventRegistrationPayment->status);
                $statusDetail = (string) ($payment['status_detail'] ?? $eventRegistrationPayment->status_detail);

                $eventRegistrationPayment->update([
                    'status' => $status,
                    'status_detail' => $statusDetail,
                    'payment_method' => (string) ($payment['payment_method_id'] ?? $eventRegistrationPayment->payment_method),
                    'payer_email' => data_get($payment, 'payer.email') ?: $eventRegistrationPayment->payer_email,
                    'payer_document' => data_get($payment, 'payer.identification.number') ?: $eventRegistrationPayment->payer_document,
                    'qr_code_base64' => data_get($payment, 'point_of_interaction.transaction_data.qr_code_base64') ?: $eventRegistrationPayment->qr_code_base64,
                    'qr_code_text' => data_get($payment, 'point_of_interaction.transaction_data.qr_code') ?: $eventRegistrationPayment->qr_code_text,
                    'paid_at' => $status === 'approved' ? now() : $eventRegistrationPayment->paid_at,
                    'raw_payload' => $payment,
                    'webhook_payload' => $webhookPayload,
                    'error_message' => $status === 'rejected' ? $statusDetail : null,
                ]);

                $registration = $eventRegistrationPayment->registration()->lockForUpdate()->first();
                if ($registration) {
                    if ($status === 'approved') {
                        if ($registration->status !== EventRegistration::STATUS_CONFIRMADO) {
                            $registration->update(['status' => EventRegistration::STATUS_CONFIRMADO]);
                            $confirmedRegistration = $registration;
                        }
                    } elseif (in_array($status, ['rejected', 'cancelled', 'refunded', 'charged_back'], true)) {
                        $registration->update(['status' => EventRegistration::STATUS_CANCELADO]);
                    }
                }
            }

            if (!$handled) {
                Log::info('Webhook Mercado Pago sem lançamento local; notificando tesouraria se houver movimentação', [
                    'external_payment_id' => $externalId,
                    'status' => $payment['status'] ?? null,
                ]);
            }

            return [
                'confirmedRegistration' => $confirmedRegistration,
                'receiptTransaction' => $receiptTransaction,
                'mpMovement' => !$this->webhookAccount && $mpMovementDirection
                    ? [
                        'direction' => $mpMovementDirection,
                        'payment' => $payment,
                        'transaction' => $mpTransaction,
                    ]
                    : null,
            ];
        });
    }

    /**
     * @param  array{
     *     confirmedRegistration?: ?EventRegistration,
     *     receiptTransaction?: ?\App\Models\FinancialTransaction,
     *     mpMovement?: ?array{direction: string, payment: array<string, mixed>, transaction: ?\App\Models\FinancialTransaction}
     * }  $pending
     * @param  array<string, mixed>  $payment
     */
    private function dispatchPendingNotifications(array $pending, array $payment): void
    {
        $receiptTransaction = $pending['receiptTransaction'] ?? null;
        if ($receiptTransaction) {
            try {
                $this->financialNotificationService->enviarComprovanteReceita($receiptTransaction);
            } catch (\Throwable $e) {
                Log::warning('Falha ao enviar comprovante financeiro após pagamento aprovado', [
                    'transaction_id' => $receiptTransaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $confirmedRegistration = $pending['confirmedRegistration'] ?? null;
        if ($confirmedRegistration) {
            try {
                app(EventRegistrationReceiptService::class)->enviarComprovante($confirmedRegistration);
            } catch (\Throwable $e) {
                Log::warning('Falha ao enviar comprovante de inscrição após pagamento aprovado', [
                    'registration_id' => $confirmedRegistration->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $mpMovement = $pending['mpMovement'] ?? null;
        if ($mpMovement) {
            try {
                $this->financialNotificationService->notificarMovimentacaoMercadoPago(
                    $mpMovement['direction'],
                    $mpMovement['payment'] ?? $payment,
                    $mpMovement['transaction'] ?? null
                );
            } catch (\Throwable $e) {
                Log::warning('Falha ao notificar tesouraria sobre movimentação Mercado Pago', [
                    'payment_id' => $payment['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function isSignatureValid(Request $request): bool
    {
        $secret = trim((string) ($this->webhookAccount ? $this->webhookAccount->webhook_secret : config('mercadopago.webhook_secret', '')));
        if ($secret === '') {
            return $this->allowsUnsignedWebhook();
        }

        $xSignature = (string) $request->header('x-signature', '');
        $xRequestId = (string) $request->header('x-request-id', '');
        $rawDataId = $request->input('data.id') ?: $request->query('data.id') ?: $request->input('id') ?: $request->query('id') ?: '';
        if (!is_scalar($rawDataId)) return false;
        $dataId = strtolower((string) $rawDataId);
        if ($xSignature === '' || $xRequestId === '' || $dataId === '') {
            if ($this->allowsUnsignedWebhook()) {
                Log::info('Webhook Mercado Pago sem cabeçalhos de assinatura; processando mesmo assim (túnel/sandbox).');

                return true;
            }

            return false;
        }

        $parts = [];
        foreach (explode(',', $xSignature) as $pair) {
            $segments = explode('=', trim($pair), 2);
            if (count($segments) === 2) {
                $parts[trim($segments[0])] = trim($segments[1]);
            }
        }

        $ts = $parts['ts'] ?? null;
        $v1 = $parts['v1'] ?? null;
        if (!$ts || !$v1) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $v1);
    }

    private function allowsUnsignedWebhook(): bool
    {
        if ($this->webhookAccount || !app()->environment(['local', 'testing'])) return false;
        if (filter_var(config('mercadopago.sandbox'), FILTER_VALIDATE_BOOL)) {
            return true;
        }

        $notificationUrl = strtolower((string) config('mercadopago.notification_url', ''));

        return str_contains($notificationUrl, 'ultrahook.com')
            || str_contains($notificationUrl, 'ngrok')
            || str_contains($notificationUrl, 'localhost');
    }
}
