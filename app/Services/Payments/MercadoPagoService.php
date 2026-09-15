<?php

namespace App\Services\Payments;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

    public function isConfigured(): bool
    {
        return trim((string) config('mercadopago.access_token', '')) !== '';
    }

    /**
     * Saldo da carteira Mercado Pago vinculada ao token.
     *
     * @return array{available: float, unavailable: float, total: float, currency: string}
     */
    public function getAccountBalance(bool $fresh = false): array
    {
        $cacheKey = 'mercadopago.account_balance';
        if (! $fresh) {
            $cached = $this->cacheGet($cacheKey);
            if (is_array($cached) && array_key_exists('available', $cached)) {
                return $cached;
            }
        }

        $token = trim((string) config('mercadopago.access_token', ''));
        if ($token === '') {
            throw new \RuntimeException('Mercado Pago não configurado (MP_ACCESS_TOKEN).');
        }

        $payload = $this->requestBalancePayload($token);
        $balance = $this->normalizeBalance($payload);

        $this->cachePut($cacheKey, $balance, now()->addSeconds(45));

        return $balance;
    }

    /**
     * @return array{
     *     days: int,
     *     in_total: float,
     *     out_total: float,
     *     items: array<int, array<string, mixed>>,
     *     truncated: bool,
     *     error: ?string,
     *     outflows_pending: bool
     * }|null
     */
    public function getCachedPaymentMovements(int $days = 30, int $limit = 50): ?array
    {
        $cached = $this->cacheGet($this->paymentMovementsCacheKey($days, $limit));
        if (! is_array($cached) || ! array_key_exists('items', $cached)) {
            return null;
        }

        return $cached;
    }

    private function paymentMovementsCacheKey(int $days, int $limit): string
    {
        return 'mercadopago.payment_movements.'.$days.'.'.$limit;
    }

    /**
     * Pagamentos recentes da conta: aprovados entram, estornos saem.
     *
     * @return array{
     *     days: int,
     *     in_total: float,
     *     out_total: float,
     *     items: array<int, array<string, mixed>>,
     *     truncated: bool,
     *     error: ?string,
     *     outflows_pending: bool
     * }
     */
    public function getPaymentMovements(bool $fresh = false, int $days = 30, int $limit = 50, bool $refreshOutflowReports = true): array
    {
        $empty = [
            'days' => $days,
            'in_total' => 0.0,
            'out_total' => 0.0,
            'items' => [],
            'truncated' => false,
            'error' => null,
            'outflows_pending' => false,
        ];

        if (! $fresh) {
            $cached = $this->getCachedPaymentMovements($days, $limit);
            if ($cached !== null) {
                return $cached;
            }
        }

        $token = trim((string) config('mercadopago.access_token', ''));
        if ($token === '') {
            $empty['error'] = 'MP_ACCESS_TOKEN não configurado.';

            return $empty;
        }

        try {
            $payload = $this->requestPaymentMovements($token, $days, $limit, $refreshOutflowReports);
            $this->cachePut($this->paymentMovementsCacheKey($days, $limit), $payload, now()->addSeconds(20));

            return $payload;
        } catch (\Throwable $e) {
            Log::warning('Mercado Pago movimentos indisponíveis', [
                'message' => $e->getMessage(),
            ]);
            $empty['error'] = $e->getMessage();

            return $empty;
        }
    }

    /**
     * @return array{
     *     days: int,
     *     in_total: float,
     *     out_total: float,
     *     items: array<int, array<string, mixed>>,
     *     truncated: bool,
     *     error: ?string,
     *     outflows_pending: bool
     * }
     */
    private function requestPaymentMovements(string $token, int $days, int $limit, bool $refreshOutflowReports = true): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->timeout(20)->get('https://api.mercadopago.com/v1/payments/search', [
            'sort' => 'date_created',
            'criteria' => 'desc',
            'range' => 'date_created',
            'begin_date' => 'NOW-'.$days.'DAYS',
            'end_date' => 'NOW',
            'limit' => $limit,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException($this->friendlyBalanceError($response, $token));
        }

        $results = $response->json('results') ?? [];
        if (! is_array($results)) {
            $results = [];
        }

        $items = [];
        $inTotal = 0.0;
        $outTotal = 0.0;

        foreach ($results as $payment) {
            if (! is_array($payment)) {
                continue;
            }

            $mapped = $this->mapPaymentMovement($payment);
            if ($mapped === null) {
                continue;
            }

            $items[] = $mapped;
            if ($mapped['direction'] === 'in') {
                $inTotal += (float) $mapped['amount'];
            } elseif ($mapped['direction'] === 'out') {
                $outTotal += (float) $mapped['amount'];
            }
        }

        $reportOutflows = $this->fetchAccountOutflows($token, $days, $refreshOutflowReports);
        foreach ($reportOutflows['items'] as $item) {
            $dup = collect($items)->contains(
                fn (array $existing) => ($existing['id'] ?? '') === ($item['id'] ?? '')
                    && ($existing['direction'] ?? '') === 'out'
            );
            if ($dup) {
                continue;
            }
            $items[] = $item;
            $outTotal += (float) $item['amount'];
        }

        usort($items, function (array $a, array $b) {
            return strcmp((string) ($b['occurred_at'] ?? ''), (string) ($a['occurred_at'] ?? ''));
        });

        $pagingTotal = (int) ($response->json('paging.total') ?? count($results));

        return [
            'days' => $days,
            'in_total' => round($inTotal, 2),
            'out_total' => round($outTotal, 2),
            'items' => $items,
            'truncated' => $pagingTotal > count($results),
            'error' => null,
            'outflows_pending' => (bool) ($reportOutflows['pending'] ?? false),
        ];
    }

    /**
     * @param  array<string, mixed>  $payment
     * @return array<string, mixed>|null
     */
    private function mapPaymentMovement(array $payment): ?array
    {
        $status = strtolower((string) ($payment['status'] ?? ''));
        $direction = match ($status) {
            'approved' => 'in',
            'refunded', 'charged_back' => 'out',
            'pending', 'in_process', 'in_mediation' => 'pending',
            default => null,
        };

        if ($direction === null) {
            return null;
        }

        $gross = (float) ($payment['transaction_amount'] ?? 0);
        $refunded = (float) ($payment['transaction_amount_refunded'] ?? 0);
        $amount = $direction === 'in' ? max(0, $gross - $refunded) : $gross;
        $occurredAt = $payment['date_approved']
            ?? $payment['date_last_updated']
            ?? $payment['date_created']
            ?? null;

        $when = null;
        if (is_string($occurredAt) && $occurredAt !== '') {
            try {
                $when = Carbon::parse($occurredAt)->timezone('America/Sao_Paulo');
            } catch (\Throwable) {
                $when = null;
            }
        }

        $payerName = trim(implode(' ', array_filter([
            (string) data_get($payment, 'payer.first_name', ''),
            (string) data_get($payment, 'payer.last_name', ''),
        ])));
        $payer = $payerName !== ''
            ? $payerName
            : (string) (data_get($payment, 'payer.email') ?: '');

        $description = trim((string) ($payment['description'] ?? ''));
        if ($description === '') {
            $description = 'Pagamento '.($payment['id'] ?? '');
        }

        return [
            'id' => (string) ($payment['id'] ?? ''),
            'direction' => $direction,
            'status' => $status,
            'amount' => round($amount, 2),
            'method' => $this->paymentMethodLabel((string) ($payment['payment_method_id'] ?? '')),
            'description' => $description,
            'payer' => $payer,
            'occurred_at' => $when?->toIso8601String(),
            'occurred_at_label' => $when?->format('d/m/Y H:i') ?? '—',
        ];
    }

    private function paymentMethodLabel(string $method): string
    {
        $method = strtolower(trim($method));

        return match ($method) {
            'pix' => 'PIX',
            'account_money', 'available_money' => 'Saldo MP',
            'bolbradesco', 'pec', 'bolix' => 'Boleto',
            '' => 'Pagamento',
            default => strtoupper($method),
        };
    }

    /**
     * Débitos da carteira: relatório de dinheiro em conta (saques/PIX) e relatório de liberações.
     *
     * @return array{items: array<int, array<string, mixed>>, pending: bool}
     */
    private function fetchAccountOutflows(string $token, int $days, bool $refreshReports = true): array
    {
        $fromSettlement = $this->fetchSettlementOutflows($token, $days, $refreshReports);
        $fromRelease = $this->fetchReleaseOutflows($token, $days, $refreshReports);

        return [
            'items' => $this->dedupeOutflows(array_merge($fromSettlement['items'], $fromRelease)),
            'pending' => $fromSettlement['pending'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function dedupeOutflows(array $items): array
    {
        $byId = [];
        foreach ($items as $item) {
            $id = (string) ($item['id'] ?? '');
            if ($id === '') {
                $byId['anon-'.count($byId)] = $item;
                continue;
            }
            $existing = $byId[$id] ?? null;
            if ($existing === null) {
                $byId[$id] = $item;
                continue;
            }
            $preferNew = str_contains(strtolower((string) ($item['description'] ?? '')), 'saque')
                && ! str_contains(strtolower((string) ($existing['description'] ?? '')), 'saque');
            if ($preferNew || (float) ($item['amount'] ?? 0) >= (float) ($existing['amount'] ?? 0)) {
                $byId[$id] = $item;
            }
        }

        return array_values($byId);
    }

    /**
     * Relatório “dinheiro em conta”: PAYOUTS / WITHDRAWAL são saídas reais (PIX/TED).
     *
     * @return array{items: array<int, array<string, mixed>>, pending: bool}
     */
    private function fetchSettlementOutflows(string $token, int $days, bool $refreshReports = true): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];

        try {
            if ($refreshReports) {
                $this->refreshSettlementReportIfStale($token, $days, $headers);
            }

            $search = Http::withHeaders($headers)->timeout(20)->get(
                'https://api.mercadopago.com/v1/account/settlement_report/search',
                ['limit' => 8]
            );
            $rows = $search->successful() ? ($search->json('results') ?? []) : [];
            if (! is_array($rows)) {
                $rows = [];
            }

            $pending = isset($rows[0]) && is_array($rows[0]) && $this->isPendingReportRow($rows[0]);
            $items = [];

            foreach ($rows as $row) {
                if (! is_array($row) || ! $this->isProcessedReportRow($row)) {
                    continue;
                }

                $download = Http::withHeaders($headers)->timeout(30)
                    ->get('https://api.mercadopago.com/v1/account/settlement_report/'.$row['file_name']);
                if (! $download->successful() || trim($download->body()) === '') {
                    continue;
                }

                $parsed = $this->parseSettlementReportCsv($download->body(), $days);
                if ($parsed['usable']) {
                    $items = $parsed['items'];
                    break;
                }
            }

            return ['items' => $items, 'pending' => $pending];
        } catch (\Throwable $e) {
            Log::warning('Mercado Pago settlement de saídas indisponível', [
                'message' => $e->getMessage(),
            ]);

            return ['items' => [], 'pending' => false];
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function reportRowStatus(array $row): string
    {
        return strtolower((string) ($row['file_status'] ?? $row['status'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isProcessedReportRow(array $row): bool
    {
        $file = (string) ($row['file_name'] ?? '');
        $status = $this->reportRowStatus($row);

        return $file !== '' && ($status === '' || $status === 'processed' || $status === 'enabled');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isPendingReportRow(array $row): bool
    {
        return $this->reportRowStatus($row) === 'pending'
            || ((string) ($row['file_name'] ?? '') === '' && $this->reportRowStatus($row) !== 'processed');
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function refreshSettlementReportIfStale(string $token, int $days, array $headers): void
    {
        $search = Http::withHeaders($headers)->timeout(20)->get(
            'https://api.mercadopago.com/v1/account/settlement_report/search',
            ['limit' => 3]
        );
        $rows = $search->successful() ? ($search->json('results') ?? []) : [];
        $latestRow = is_array($rows) && isset($rows[0]) && is_array($rows[0]) ? $rows[0] : null;
        $latestCreated = null;
        if ($latestRow) {
            $raw = (string) ($latestRow['date_created'] ?? '');
            if ($raw !== '') {
                try {
                    $latestCreated = Carbon::parse($raw);
                } catch (\Throwable) {
                    $latestCreated = null;
                }
            }
        }

        if ($latestRow && $this->isPendingReportRow($latestRow)) {
            $this->waitForNewerSettlementReport($headers, $latestCreated?->copy()->subSecond());

            return;
        }

        $stale = $latestCreated === null || $latestCreated->lt(now()->subMinutes(2));
        if (! $stale) {
            return;
        }

        if ($this->requestSettlementReport($token, $days)) {
            $this->waitForNewerSettlementReport($headers, $latestCreated);
        }
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function waitForNewerSettlementReport(array $headers, ?Carbon $after): void
    {
        $attempts = app()->environment('testing') ? 1 : 4;
        $sleepSeconds = app()->environment('testing') ? 0 : 3;

        for ($i = 0; $i < $attempts; $i++) {
            if ($i > 0 && $sleepSeconds > 0) {
                sleep($sleepSeconds);
            }

            $search = Http::withHeaders($headers)->timeout(20)->get(
                'https://api.mercadopago.com/v1/account/settlement_report/search',
                ['limit' => 3]
            );
            $rows = $search->successful() ? ($search->json('results') ?? []) : [];
            if (! is_array($rows) || ! isset($rows[0]) || ! is_array($rows[0])) {
                continue;
            }
            $status = $this->reportRowStatus($rows[0]);
            $file = (string) ($rows[0]['file_name'] ?? '');
            $createdRaw = (string) ($rows[0]['date_created'] ?? '');
            if ($file === '' || ($status !== '' && ! in_array($status, ['processed', 'enabled'], true))) {
                continue;
            }
            if ($after === null) {
                return;
            }
            try {
                $created = Carbon::parse($createdRaw);
            } catch (\Throwable) {
                continue;
            }
            if ($created->gt($after)) {
                return;
            }
        }
    }

    private function requestSettlementReport(string $token, int $days): bool
    {
        if ($this->cacheHas('mercadopago.settlement_report_requested')) {
            return false;
        }

        $this->cachePut('mercadopago.settlement_report_requested', true, now()->addSeconds(90));

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        Http::withHeaders($headers)->timeout(20)->post('https://api.mercadopago.com/v1/account/settlement_report/config', [
            'file_name_prefix' => 'adelss-settlement',
            'display_timezone' => 'GMT-03',
            'include_withdraw' => true,
            'columns' => [
                ['key' => 'SOURCE_ID'],
                ['key' => 'TRANSACTION_TYPE'],
                ['key' => 'TRANSACTION_AMOUNT'],
                ['key' => 'TRANSACTION_DATE'],
                ['key' => 'SETTLEMENT_NET_AMOUNT'],
                ['key' => 'OPERATION_TAGS'],
                ['key' => 'PAYMENT_METHOD'],
            ],
        ]);

        Http::withHeaders($headers)->timeout(20)->post('https://api.mercadopago.com/v1/account/settlement_report', [
            'begin_date' => now('UTC')->subDays($days)->startOfDay()->format('Y-m-d\TH:i:s\Z'),
            'end_date' => now('UTC')->addDay()->startOfDay()->format('Y-m-d\TH:i:s\Z'),
        ]);

        return true;
    }

    /**
     * @return array{usable: bool, items: array<int, array<string, mixed>>}
     */
    private function parseSettlementReportCsv(string $csv, int $days): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($csv)) ?: [];
        $headerLine = array_shift($lines);
        if (! is_string($headerLine) || $headerLine === '') {
            return ['usable' => false, 'items' => []];
        }

        $delimiter = substr_count($headerLine, ';') > substr_count($headerLine, ',') ? ';' : ',';
        $headers = array_map(
            fn ($h) => strtoupper(trim((string) $h, "\" \t")),
            str_getcsv($headerLine, $delimiter)
        );
        $index = array_flip($headers);
        $usable = isset($index['TRANSACTION_TYPE']) || isset($index['TRANSACTION_AMOUNT']);
        if (! $usable) {
            return ['usable' => false, 'items' => []];
        }

        $since = now('America/Sao_Paulo')->subDays($days)->startOfDay();
        $outTypes = ['payouts', 'payout', 'withdrawal', 'withdraw', 'refund', 'chargeback', 'money_transfer'];
        $items = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line, $delimiter);
            $type = strtolower($this->csvColumn($cols, $index, 'TRANSACTION_TYPE'));
            if (! in_array($type, $outTypes, true)) {
                continue;
            }

            $gross = (float) str_replace(',', '.', $this->csvColumn($cols, $index, 'TRANSACTION_AMOUNT'));
            $net = (float) str_replace(',', '.', $this->csvColumn($cols, $index, 'SETTLEMENT_NET_AMOUNT'));
            $amount = abs($gross !== 0.0 ? $gross : $net);
            if ($amount <= 0) {
                continue;
            }

            $whenRaw = $this->csvColumn($cols, $index, 'SETTLEMENT_DATE')
                ?: $this->csvColumn($cols, $index, 'TRANSACTION_DATE');
            $when = null;
            if ($whenRaw !== '') {
                try {
                    $when = Carbon::parse($whenRaw)->timezone('America/Sao_Paulo');
                } catch (\Throwable) {
                    $when = null;
                }
            }
            if ($when && $when->lt($since)) {
                continue;
            }

            $tags = strtolower($this->csvColumn($cols, $index, 'OPERATION_TAGS'));
            $label = match (true) {
                str_contains($tags, 'pix') || $type === 'money_transfer' => 'PIX enviado',
                in_array($type, ['payouts', 'payout'], true) => 'Saque / PIX enviado',
                in_array($type, ['withdrawal', 'withdraw'], true) => 'Transferência bancária',
                $type === 'refund' => 'Estorno',
                $type === 'chargeback' => 'Chargeback',
                default => 'Saída da carteira',
            };

            $sourceId = $this->csvColumn($cols, $index, 'SOURCE_ID');

            $items[] = [
                'id' => $sourceId !== '' ? $sourceId : ('set-'.md5($line)),
                'direction' => 'out',
                'status' => 'outflow',
                'amount' => round($amount, 2),
                'method' => $this->paymentMethodLabel($this->csvColumn($cols, $index, 'PAYMENT_METHOD')),
                'description' => $label,
                'payer' => '',
                'occurred_at' => $when?->toIso8601String(),
                'occurred_at_label' => $when?->format('d/m/Y H:i') ?? '—',
            ];
        }

        return ['usable' => true, 'items' => $items];
    }

    /**
     * Saques e débitos do relatório de liberações (fallback).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchReleaseOutflows(string $token, int $days, bool $refreshReports = true): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];

        try {
            if ($refreshReports) {
                $this->refreshReleaseReportIfStale($token, $days, $headers);
            }

            $search = Http::withHeaders($headers)->timeout(20)->get(
                'https://api.mercadopago.com/v1/account/release_report/search',
                ['limit' => 5]
            );
            $rows = $search->successful() ? ($search->json('results') ?? []) : [];
            if (! is_array($rows)) {
                $rows = [];
            }

            $usable = false;
            $items = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $file = (string) ($row['file_name'] ?? '');
                if ($file === '') {
                    continue;
                }

                $download = Http::withHeaders($headers)->timeout(30)
                    ->get('https://api.mercadopago.com/v1/account/release_report/'.$file);
                if (! $download->successful() || trim($download->body()) === '') {
                    continue;
                }

                $parsed = $this->parseReleaseReportCsv($download->body(), $days);
                if ($parsed['usable']) {
                    $usable = true;
                    $items = $parsed['items'];
                    break;
                }
            }

            if (! $usable && $refreshReports) {
                $this->requestReleaseReport($token, $days);
            }

            return $items;
        } catch (\Throwable $e) {
            Log::warning('Mercado Pago relatório de saídas indisponível', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return array{usable: bool, items: array<int, array<string, mixed>>}
     */
    private function parseReleaseReportCsv(string $csv, int $days): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($csv)) ?: [];
        $headerLine = array_shift($lines);
        if (! is_string($headerLine) || $headerLine === '') {
            return ['usable' => false, 'items' => []];
        }

        $delimiter = substr_count($headerLine, ';') > substr_count($headerLine, ',') ? ';' : ',';
        $headers = array_map(
            fn ($h) => strtoupper(trim((string) $h, "\" \t")),
            str_getcsv($headerLine, $delimiter)
        );
        $index = array_flip($headers);
        $usable = isset($index['NET_DEBIT_AMOUNT']) || isset($index['DESCRIPTION']);
        if (! $usable) {
            return ['usable' => false, 'items' => []];
        }

        $since = now('America/Sao_Paulo')->subDays($days)->startOfDay();
        $items = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line, $delimiter);
            $recordType = strtolower((string) $this->csvColumn($cols, $index, 'RECORD_TYPE'));
            if (in_array($recordType, ['total', 'initial_available_balance'], true)) {
                continue;
            }

            $description = strtolower(trim($this->csvColumn($cols, $index, 'DESCRIPTION')));
            if ($description === 'payment'
                || $description === 'reserve_for_payout'
                || str_starts_with($description, 'pre_payout')
                || str_starts_with($description, 'pos_payout')) {
                continue;
            }

            $debit = (float) str_replace(',', '.', $this->csvColumn($cols, $index, 'NET_DEBIT_AMOUNT'));
            $tags = strtolower($this->csvColumn($cols, $index, 'OPERATION_TAGS'));
            $isOut = $debit > 0
                || in_array($description, ['payout', 'refund', 'chargeback'], true)
                || str_contains($tags, 'cashout');

            if (! $isOut) {
                continue;
            }

            $whenRaw = $this->csvColumn($cols, $index, 'DATE');
            $when = null;
            if ($whenRaw !== '') {
                try {
                    $when = Carbon::parse($whenRaw)->timezone('America/Sao_Paulo');
                } catch (\Throwable) {
                    $when = null;
                }
            }
            if ($when && $when->lt($since)) {
                continue;
            }

            $amount = $debit > 0 ? $debit : (float) str_replace(',', '.', $this->csvColumn($cols, $index, 'GROSS_AMOUNT'));
            if ($amount <= 0) {
                continue;
            }

            $label = match (true) {
                str_contains($description, 'payout') || str_contains($tags, 'cashout') => 'Saque / transferência',
                $description === 'refund' => 'Estorno',
                $description === 'chargeback' => 'Chargeback',
                $description !== '' => $description,
                default => 'Saída da carteira',
            };

            $sourceId = $this->csvColumn($cols, $index, 'SOURCE_ID');

            $items[] = [
                'id' => $sourceId !== '' ? $sourceId : ('rel-'.md5($line)),
                'direction' => 'out',
                'status' => 'outflow',
                'amount' => round($amount, 2),
                'method' => $this->paymentMethodLabel($this->csvColumn($cols, $index, 'PAYMENT_METHOD')),
                'description' => $label,
                'payer' => '',
                'occurred_at' => $when?->toIso8601String(),
                'occurred_at_label' => $when?->format('d/m/Y H:i') ?? '—',
            ];
        }

        return ['usable' => true, 'items' => $items];
    }

    /**
     * @param  array<int, string|null>  $cols
     * @param  array<string, int>  $index
     */
    private function csvColumn(array $cols, array $index, string $name): string
    {
        if (! isset($index[$name])) {
            return '';
        }

        return trim((string) ($cols[$index[$name]] ?? ''));
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function refreshReleaseReportIfStale(string $token, int $days, array $headers): void
    {
        $search = Http::withHeaders($headers)->timeout(20)->get(
            'https://api.mercadopago.com/v1/account/release_report/search',
            ['limit' => 3]
        );
        $rows = $search->successful() ? ($search->json('results') ?? []) : [];
        $latestCreated = null;
        if (is_array($rows) && isset($rows[0]) && is_array($rows[0])) {
            $raw = (string) ($rows[0]['date_created'] ?? '');
            if ($raw !== '') {
                try {
                    $latestCreated = Carbon::parse($raw);
                } catch (\Throwable) {
                    $latestCreated = null;
                }
            }
        }

        if ($latestCreated !== null && $latestCreated->gte(now()->subMinutes(2))) {
            return;
        }

        $this->requestReleaseReport($token, $days);
    }

    private function requestReleaseReport(string $token, int $days): void
    {
        if ($this->cacheHas('mercadopago.release_report_requested')) {
            return;
        }

        $this->cachePut('mercadopago.release_report_requested', true, now()->addSeconds(90));

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        Http::withHeaders($headers)->timeout(20)->put('https://api.mercadopago.com/v1/account/release_report/config', [
            'file_name_prefix' => 'adelss-release',
            'include_withdrawal_at_end' => true,
            'execute_after_withdrawal' => true,
            'display_timezone' => 'GMT-03',
            'header_language' => 'pt',
            'frequency' => ['hour' => 6, 'type' => 'monthly', 'value' => 1],
            'columns' => [
                ['key' => 'DATE'],
                ['key' => 'SOURCE_ID'],
                ['key' => 'RECORD_TYPE'],
                ['key' => 'DESCRIPTION'],
                ['key' => 'NET_DEBIT_AMOUNT'],
                ['key' => 'NET_CREDIT_AMOUNT'],
                ['key' => 'GROSS_AMOUNT'],
                ['key' => 'MP_FEE_AMOUNT'],
                ['key' => 'PAYMENT_METHOD'],
                ['key' => 'OPERATION_TAGS'],
            ],
        ]);

        Http::withHeaders($headers)->timeout(20)->post('https://api.mercadopago.com/v1/account/release_report', [
            'begin_date' => now('UTC')->subDays($days)->startOfDay()->format('Y-m-d\TH:i:s\Z'),
            'end_date' => now('UTC')->format('Y-m-d\TH:i:s\Z'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestBalancePayload(string $token): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];

        $me = Http::withHeaders($headers)->timeout(15)->get('https://api.mercadopago.com/users/me');
        if (! $me->successful()) {
            throw new \RuntimeException($this->friendlyBalanceError($me, $token));
        }

        $meJson = $me->json() ?? [];
        $userId = $meJson['id'] ?? null;
        if (! $userId) {
            throw new \RuntimeException('Não foi possível identificar a conta Mercado Pago vinculada ao Access Token.');
        }

        if ($this->isTestUser($meJson, $token)) {
            throw new \RuntimeException(
                'O Access Token atual é de teste. Ele autentica um usuário fictício (CPF), não a conta CNPJ do painel. Para ver o saldo da carteira da empresa, use as credenciais de Produção em Suas integrações → Produção.'
            );
        }

        $byUser = Http::withHeaders($headers)
            ->timeout(15)
            ->get('https://api.mercadopago.com/users/'.$userId.'/mercadopago_account/balance');

        if ($byUser->successful()) {
            return $byUser->json() ?? [];
        }

        Log::warning('Mercado Pago saldo da carteira indisponível', [
            'status' => $byUser->status(),
            'error' => $byUser->json('error'),
            'credential' => $this->credentialKind($token),
        ]);

        throw new \RuntimeException($this->friendlyBalanceError($byUser, $token));
    }

    /**
     * @param  array<string, mixed>  $me
     */
    private function isTestUser(array $me, string $token): bool
    {
        if ($this->credentialKind($token) === 'test') {
            return true;
        }

        $tags = $me['tags'] ?? [];
        if (is_array($tags) && in_array('test_user', $tags, true)) {
            return true;
        }

        return str_starts_with(strtoupper((string) ($me['nickname'] ?? '')), 'TESTUSER');
    }

    private function credentialKind(string $token): string
    {
        if (str_starts_with($token, 'TEST-')) {
            return 'test';
        }
        if (str_starts_with($token, 'APP_USR-')) {
            return 'production';
        }

        return 'unknown';
    }

    private function friendlyBalanceError(\Illuminate\Http\Client\Response $response, string $token): string
    {
        $status = $response->status();
        $error = (string) ($response->json('error') ?? '');
        $message = trim((string) ($response->json('message') ?? ''));
        $isTest = $this->credentialKind($token) === 'test';

        if ($status === 401 || $error === 'invalid_credentials' || str_contains(mb_strtolower($message), 'invalid')) {
            return 'Access Token do Mercado Pago inválido ou expirado.';
        }

        if ($status === 403 || strcasecmp($error, 'forbidden') === 0 || strcasecmp($message, 'forbidden') === 0) {
            return 'As credenciais estão válidas, mas o Mercado Pago não libera consulta ao vivo do saldo da carteira para esta aplicação. O card usa o saldo interno; entradas e saídas continuam pelo webhook.';
        }

        if ($status === 404 || $error === 'resource not found') {
            if ($isTest) {
                return 'O Access Token atual é de teste (TEST-). A carteira de teste não tem API de saldo. Use o Access Token de produção (APP_USR-) em Suas integrações → Produção.';
            }

            return 'A API de saldo da carteira não está disponível para esta credencial. Confira se o Access Token de produção (APP_USR-) está ativo.';
        }

        if ($message !== '' && ! str_contains(mb_strtolower($message), 'si quieres')) {
            return $message;
        }

        return 'Não foi possível consultar o saldo do Mercado Pago (HTTP '.$status.').';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{available: float, unavailable: float, total: float, currency: string}
     */
    private function normalizeBalance(array $payload): array
    {
        $available = $payload['available_balance']
            ?? $payload['available_amount']
            ?? data_get($payload, 'available.total')
            ?? data_get($payload, 'available.amount')
            ?? 0;
        $unavailable = $payload['unavailable_balance']
            ?? $payload['unavailable_amount']
            ?? data_get($payload, 'unavailable.total')
            ?? data_get($payload, 'unavailable.amount')
            ?? 0;
        $total = $payload['total_amount']
            ?? $payload['total']
            ?? ((float) $available + (float) $unavailable);
        $currency = (string) ($payload['currency_id']
            ?? data_get($payload, 'available.currency_id')
            ?? config('mercadopago.currency', 'BRL'));

        return [
            'available' => round((float) $available, 2),
            'unavailable' => round((float) $unavailable, 2),
            'total' => round((float) $total, 2),
            'currency' => $currency !== '' ? $currency : 'BRL',
        ];
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

    private function cacheGet(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (\Throwable $e) {
            $this->logCacheFailure('leitura', $key, $e);

            return null;
        }
    }

    private function cacheHas(string $key): bool
    {
        try {
            return Cache::has($key);
        } catch (\Throwable $e) {
            $this->logCacheFailure('leitura', $key, $e);

            return false;
        }
    }

    private function cachePut(string $key, mixed $value, \DateTimeInterface|\DateInterval|int $ttl): void
    {
        try {
            Cache::put($key, $value, $ttl);
        } catch (\Throwable $e) {
            $this->logCacheFailure('gravação', $key, $e);
        }
    }

    private function logCacheFailure(string $operacao, string $key, \Throwable $e): void
    {
        Log::warning('Cache do Mercado Pago indisponível para '.$operacao, [
            'key' => $key,
            'message' => $e->getMessage(),
        ]);
    }
}
