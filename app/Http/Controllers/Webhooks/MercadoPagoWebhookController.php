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

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private MercadoPagoService $mercadoPagoService,
        private FinancialNotificationService $financialNotificationService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        if (!$this->isSignatureValid($request)) {
            return response()->json(['success' => false, 'error' => 'Assinatura inválida.'], 401);
        }

        $topic = (string) ($request->input('type') ?: $request->query('type') ?: $request->input('topic') ?: '');
        $paymentId = $request->input('data.id') ?: $request->query('data.id') ?: $request->input('id');
        if (!$paymentId || ($topic !== '' && $topic !== 'payment')) {
            return response()->json(['success' => true, 'message' => 'Evento ignorado.']);
        }

        try {
            $payment = $this->mercadoPagoService->getPayment((string) $paymentId);
            $this->syncPayment($payment, $request->all());
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

        return response()->json(['success' => true]);
    }

    /**
     * @param array<string, mixed> $payment
     * @param array<string, mixed> $webhookPayload
     */
    private function syncPayment(array $payment, array $webhookPayload): void
    {
        $externalId = (string) ($payment['id'] ?? '');
        if ($externalId === '') {
            throw new \RuntimeException('Pagamento sem ID externo.');
        }

        $confirmedRegistration = DB::transaction(function () use ($externalId, $payment, $webhookPayload) {
            $confirmedRegistration = null;
            $handled = false;
            /** @var PaymentTransaction|null $paymentTransaction */
            $paymentTransaction = PaymentTransaction::query()
                ->where('external_payment_id', $externalId)
                ->lockForUpdate()
                ->first();

            if ($paymentTransaction) {
                $handled = true;
                $status = (string) ($payment['status'] ?? $paymentTransaction->status);
                $statusDetail = (string) ($payment['status_detail'] ?? $paymentTransaction->status_detail);

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
                    if ($status === 'approved') {
                        if (!$financialTransaction->is_paid) {
                            $financialTransaction->update([
                                'is_paid' => true,
                                'status' => 'recebido',
                            ]);
                            $financialTransaction->refresh()->load(['member', 'category']);
                            $this->financialNotificationService->enviarComprovanteReceita($financialTransaction);
                        }
                    } elseif (in_array($status, ['rejected', 'cancelled', 'refunded', 'charged_back'], true) && $financialTransaction->type === 'receita') {
                        $financialTransaction->update([
                            'is_paid' => false,
                            'status' => 'a_receber',
                        ]);
                    }
                }
            }

            /** @var EventRegistrationPayment|null $eventRegistrationPayment */
            $eventRegistrationPayment = EventRegistrationPayment::query()
                ->where('external_payment_id', $externalId)
                ->lockForUpdate()
                ->first();

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
                Log::info('Webhook Mercado Pago ignorado: pagamento não rastreado localmente', [
                    'external_payment_id' => $externalId,
                ]);
            }

            return $confirmedRegistration;
        });

        // Envio do comprovante fora da transação: falha de envio nunca invalida a inscrição.
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
    }

    private function isSignatureValid(Request $request): bool
    {
        $secret = trim((string) config('mercadopago.webhook_secret', ''));
        if ($secret === '') {
            return true;
        }

        $xSignature = (string) $request->header('x-signature', '');
        $xRequestId = (string) $request->header('x-request-id', '');
        $dataId = (string) ($request->input('data.id') ?: $request->query('data.id') ?: '');
        if ($xSignature === '' || $xRequestId === '' || $dataId === '') {
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
}
