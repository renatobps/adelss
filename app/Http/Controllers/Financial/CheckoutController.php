<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\PaymentTransaction;
use App\Services\Payments\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        private MercadoPagoService $mercadoPagoService
    ) {}

    public function createPix(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        $this->authorize('checkout', FinancialTransaction::class);
        $validated = $request->validate([
            'payer_email' => ['required', 'email'],
            'payer_document' => ['required', 'string', 'min:11', 'max:20'],
        ]);

        if (!$this->canCharge($transaction)) {
            return response()->json([
                'success' => false,
                'error' => 'Somente receitas em aberto podem receber cobrança.',
            ], 422);
        }

        $idempotencyKey = (string) Str::uuid();
        $externalReference = sprintf('ftx-%d-%s', $transaction->id, Str::random(8));

        try {
            $response = $this->mercadoPagoService->createPixPayment([
                'amount' => $transaction->amount,
                'description' => $this->buildDescription($transaction),
                'payer_email' => $validated['payer_email'],
                'payer_document' => preg_replace('/\D+/', '', $validated['payer_document']) ?: $validated['payer_document'],
                'payer_document_type' => 'CPF',
                'external_reference' => $externalReference,
            ], $idempotencyKey);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        $record = $this->persistPaymentTransaction(
            $transaction,
            $response,
            $idempotencyKey,
            $externalReference,
            'pix',
            $validated['payer_email'],
            $validated['payer_document']
        );

        return response()->json([
            'success' => true,
            'data' => [
                'payment_transaction_id' => $record->id,
                'external_payment_id' => $record->external_payment_id,
                'status' => $record->status,
                'status_detail' => $record->status_detail,
                'qr_code_base64' => $record->qr_code_base64,
                'qr_code_text' => $record->qr_code_text,
            ],
        ]);
    }

    public function createCard(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        $this->authorize('checkout', FinancialTransaction::class);
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'payer_email' => ['required', 'email'],
            'payer_document' => ['required', 'string', 'min:11', 'max:20'],
            'payment_method_id' => ['required', 'string', 'max:50'],
            'issuer_id' => ['nullable', 'string', 'max:40'],
            'installments' => ['required', 'integer', 'min:1', 'max:24'],
        ]);

        if (!$this->canCharge($transaction)) {
            return response()->json([
                'success' => false,
                'error' => 'Somente receitas em aberto podem receber cobrança.',
            ], 422);
        }

        $idempotencyKey = (string) Str::uuid();
        $externalReference = sprintf('ftx-%d-%s', $transaction->id, Str::random(8));

        try {
            $response = $this->mercadoPagoService->createCardPayment([
                'amount' => $transaction->amount,
                'description' => $this->buildDescription($transaction),
                'token' => $validated['token'],
                'payer_email' => $validated['payer_email'],
                'payer_document' => preg_replace('/\D+/', '', $validated['payer_document']) ?: $validated['payer_document'],
                'payer_document_type' => 'CPF',
                'payment_method_id' => $validated['payment_method_id'],
                'issuer_id' => $validated['issuer_id'] ?? null,
                'installments' => (int) $validated['installments'],
                'external_reference' => $externalReference,
            ], $idempotencyKey);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        $record = $this->persistPaymentTransaction(
            $transaction,
            $response,
            $idempotencyKey,
            $externalReference,
            (string) ($response['payment_method_id'] ?? $validated['payment_method_id']),
            $validated['payer_email'],
            $validated['payer_document']
        );

        return response()->json([
            'success' => true,
            'data' => [
                'payment_transaction_id' => $record->id,
                'external_payment_id' => $record->external_payment_id,
                'status' => $record->status,
                'status_detail' => $record->status_detail,
            ],
        ]);
    }

    public function status(PaymentTransaction $paymentTransaction): JsonResponse
    {
        $this->authorize('checkout', FinancialTransaction::class);
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $paymentTransaction->id,
                'status' => $paymentTransaction->status,
                'status_detail' => $paymentTransaction->status_detail,
                'paid_at' => optional($paymentTransaction->paid_at)?->toIso8601String(),
            ],
        ]);
    }

    private function canCharge(FinancialTransaction $transaction): bool
    {
        return $transaction->type === 'receita' && !$transaction->is_paid;
    }

    private function buildDescription(FinancialTransaction $transaction): string
    {
        $description = trim((string) $transaction->description);
        if ($description === '') {
            return 'Receita ADELSS #' . $transaction->id;
        }

        return mb_substr($description, 0, 120);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function persistPaymentTransaction(
        FinancialTransaction $transaction,
        array $response,
        string $idempotencyKey,
        string $externalReference,
        string $paymentMethod,
        string $payerEmail,
        string $payerDocument
    ): PaymentTransaction {
        return DB::transaction(function () use (
            $transaction,
            $response,
            $idempotencyKey,
            $externalReference,
            $paymentMethod,
            $payerEmail,
            $payerDocument
        ) {
            return PaymentTransaction::create([
                'financial_transaction_id' => $transaction->id,
                'gateway' => 'mercado_pago',
                'idempotency_key' => $idempotencyKey,
                'external_payment_id' => isset($response['id']) ? (string) $response['id'] : null,
                'external_reference' => $externalReference,
                'status' => (string) ($response['status'] ?? 'pending'),
                'status_detail' => (string) ($response['status_detail'] ?? ''),
                'payment_method' => $paymentMethod,
                'amount' => (float) $transaction->amount,
                'currency' => (string) ($response['currency_id'] ?? config('mercadopago.currency', 'BRL')),
                'payer_email' => $payerEmail,
                'payer_document' => $payerDocument,
                'qr_code_base64' => data_get($response, 'point_of_interaction.transaction_data.qr_code_base64'),
                'qr_code_text' => data_get($response, 'point_of_interaction.transaction_data.qr_code'),
                'paid_at' => ($response['status'] ?? null) === 'approved' ? now() : null,
                'raw_payload' => $response,
            ]);
        });
    }
}
