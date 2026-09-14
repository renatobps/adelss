<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\FinancialTransfer;
use App\Services\FinancialNotificationService;
use App\Services\Payments\MercadoPagoService;
use App\Support\PdfText;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountController extends Controller
{
    public function __construct(private MercadoPagoService $mercadoPago) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', FinancialAccount::class);

        $status = $request->input('status', 'ativas');
        if (!in_array($status, ['ativas', 'inativas', 'todas'], true)) {
            $status = 'ativas';
        }

        $query = $this->accountsWithBalanceSums()->orderBy('name');

        if ($status === 'ativas') {
            $query->where('is_active', true);
        } elseif ($status === 'inativas') {
            $query->where('is_active', false);
        }

        $listedAccounts = $query->get();
        $hasMercadoPagoAccount = FinancialAccount::query()
            ->where(function ($q) {
                $q->where('type', FinancialAccount::TYPE_MERCADO_PAGO)
                    ->orWhere('bank_name', 'like', '%Mercado Pago%')
                    ->orWhere('bank_name', 'like', '%MercadoPago%')
                    ->orWhere('name', 'like', '%Mercado Pago%')
                    ->orWhere('name', 'like', '%MercadoPago%');
            })
            ->exists();
        $mpMovements = $hasMercadoPagoAccount
            ? ($this->mercadoPago->getCachedPaymentMovements() ?? $this->emptyMpMovements())
            : null;

        $accounts = $listedAccounts->map(function (FinancialAccount $account) use ($mpMovements) {
            $this->applyDisplayBalance($account, $mpMovements);

            return $account;
        });

        $counts = [
            'ativas' => FinancialAccount::where('is_active', true)->count(),
            'inativas' => FinancialAccount::where('is_active', false)->count(),
            'todas' => FinancialAccount::count(),
        ];

        if ($status === 'ativas') {
            $saldoAtivas = $accounts->sum(fn (FinancialAccount $account) => (float) $account->current_balance);
        } else {
            $saldoAtivas = $this->accountsWithBalanceSums()
                ->where('is_active', true)
                ->get()
                ->sum(function (FinancialAccount $account) use ($mpMovements) {
                    $this->applyDisplayBalance($account, $mpMovements);

                    return (float) $account->current_balance;
                });
        }

        $types = FinancialAccount::TYPES;
        $colors = FinancialAccount::COLORS;

        $transferAccounts = FinancialAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $transfers = FinancialTransfer::with(['fromAccount', 'toAccount'])
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('financial.accounts.index', compact(
            'accounts',
            'status',
            'counts',
            'saldoAtivas',
            'types',
            'colors',
            'mpMovements',
            'transferAccounts',
            'transfers'
        ));
    }

    public function mercadoPagoMovements()
    {
        $this->authorize('viewAny', FinancialAccount::class);

        $mpMovements = $this->loadMercadoPagoMovements(refreshOutflowReports: false, notify: true);
        $mpBalance = round(
            (float) ($mpMovements['in_total'] ?? 0) - (float) ($mpMovements['out_total'] ?? 0),
            2
        );

        $saldoAtivas = $this->accountsWithBalanceSums()
            ->where('is_active', true)
            ->get()
            ->sum(function (FinancialAccount $account) use ($mpMovements) {
                $this->applyDisplayBalance($account, $mpMovements);

                return (float) $account->current_balance;
            });

        return response()->json([
            'success' => empty($mpMovements['error']),
            'saldo_ativas' => $saldoAtivas,
            'mp_balance' => $mpBalance,
            'movements' => $mpMovements,
        ]);
    }

    public function exportMercadoPagoPdf(Request $request)
    {
        $this->authorize('viewAny', FinancialAccount::class);

        $extract = $this->filteredMpExtract($request->input('q'));
        $binary = Pdf::loadView('financial.accounts.pdf.mp-extract', $extract)
            ->setPaper('a4', 'portrait')
            ->output();

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="extrato-mercado-pago-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }

    public function exportMercadoPagoExcel(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', FinancialAccount::class);

        $extract = $this->filteredMpExtract($request->input('q'));
        $filename = 'extrato-mercado-pago-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($extract) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Data', 'Tipo', 'Descrição', 'Pagador', 'Meio', 'Valor'], ';');
            foreach ($extract['items'] as $item) {
                $dir = $item['direction'] ?? '';
                $tipo = $dir === 'in' ? 'Entrada' : ($dir === 'out' ? 'Saída' : 'Pendente');
                $amount = (float) ($item['amount'] ?? 0);
                $signed = ($dir === 'out' ? '-' : '').number_format($amount, 2, ',', '.');
                fputcsv($out, [
                    $item['occurred_at_label'] ?? '',
                    $tipo,
                    $item['description'] ?? '',
                    $item['payer'] ?? '',
                    $item['method'] ?? '',
                    $signed,
                ], ';');
            }
            fputcsv($out, [], ';');
            fputcsv($out, ['Entradas', number_format((float) $extract['in_total'], 2, ',', '.')], ';');
            fputcsv($out, ['Saídas', number_format((float) $extract['out_total'], 2, ',', '.')], ';');
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create()
    {
        $this->authorize('create', FinancialAccount::class);

        return redirect()->route('financial.accounts.index');
    }

    public function store(Request $request)
    {
        $this->authorize('create', FinancialAccount::class);

        $validated = $this->validateAccount($request);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['bank_name'] = $validated['bank_name'] ?? null;
        $validated['description'] = $validated['description'] ?? null;

        FinancialAccount::create($validated);

        return redirect()->route('financial.accounts.index', ['status' => 'ativas'])
            ->with('success', 'Conta criada com sucesso!');
    }

    public function show(string $id)
    {
        return redirect()->route('financial.accounts.index');
    }

    public function edit(FinancialAccount $account)
    {
        $this->authorize('update', $account);

        return redirect()->route('financial.accounts.index');
    }

    public function update(Request $request, FinancialAccount $account)
    {
        $this->authorize('update', $account);

        $validated = $this->validateAccount($request);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['bank_name'] = $validated['bank_name'] ?? null;
        $validated['description'] = $validated['description'] ?? null;

        $account->update($validated);

        return redirect()->route('financial.accounts.index', [
            'status' => $request->input('redirect_status', $account->is_active ? 'ativas' : 'inativas'),
        ])->with('success', 'Conta atualizada com sucesso!');
    }

    public function toggleActive(FinancialAccount $account)
    {
        $this->authorize('update', $account);

        $account->update(['is_active' => !$account->is_active]);

        $status = $account->is_active ? 'ativas' : 'inativas';
        $msg = $account->is_active
            ? 'Conta reativada com sucesso!'
            : 'Conta desativada. O histórico foi preservado.';

        return redirect()->route('financial.accounts.index', ['status' => request('status', $status)])
            ->with('success', $msg);
    }

    public function destroy(FinancialAccount $account)
    {
        $this->authorize('delete', $account);

        try {
            $account->delete();

            return redirect()->route('financial.accounts.index', ['status' => request('status', 'ativas')])
                ->with('success', 'Conta removida com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('financial.accounts.index')
                ->with('error', 'Erro ao remover conta. Por favor, tente novamente.');
        }
    }

    private function validateAccount(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(FinancialAccount::TYPES))],
            'bank_name' => 'nullable|string|max:255',
            'initial_balance' => 'required|numeric',
            'color' => ['required', 'string', Rule::in(FinancialAccount::COLORS)],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'O campo nome da conta é obrigatório.',
            'type.required' => 'Selecione o tipo da conta.',
            'type.in' => 'Tipo de conta inválido.',
            'initial_balance.required' => 'Informe o saldo inicial.',
            'color.required' => 'Selecione uma cor de identificação.',
            'color.in' => 'Cor de identificação inválida.',
        ]);
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
    private function loadMercadoPagoMovements(bool $refreshOutflowReports, bool $notify = false): array
    {
        $mpMovements = $this->mercadoPago->getPaymentMovements(true, 30, 50, $refreshOutflowReports);

        if ($notify && empty($mpMovements['error'])) {
            try {
                app(FinancialNotificationService::class)->notificarMovimentosMercadoPagoRecentes($mpMovements);
            } catch (\Throwable $e) {
                Log::warning('Falha ao notificar tesouraria a partir dos movimentos Mercado Pago', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $mpMovements;
    }

    /**
     * @return array{
     *     days: int,
     *     q: string,
     *     items: list<array<string, mixed>>,
     *     in_total: float,
     *     out_total: float,
     *     error: ?string
     * }
     */
    private function filteredMpExtract(?string $search): array
    {
        $movements = $this->mercadoPago->getCachedPaymentMovements()
            ?? $this->mercadoPago->getPaymentMovements(false, 30, 50, false);

        $term = mb_strtolower(trim((string) $search));
        $items = [];
        $inTotal = 0.0;
        $outTotal = 0.0;

        foreach ($movements['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $dir = (string) ($item['direction'] ?? '');
            $tipo = $dir === 'in' ? 'Entrada' : ($dir === 'out' ? 'Saída' : 'Pendente');
            if ($term !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    $item['occurred_at_label'] ?? '',
                    $tipo,
                    $item['description'] ?? '',
                    $item['payer'] ?? '',
                    $item['method'] ?? '',
                    (string) ($item['amount'] ?? ''),
                ]));
                if (! str_contains($haystack, $term)) {
                    continue;
                }
            }

            $item['description'] = PdfText::stripEmoji((string) ($item['description'] ?? ''));
            $item['payer'] = PdfText::stripEmoji((string) ($item['payer'] ?? ''));
            $items[] = $item;
            $amount = (float) ($item['amount'] ?? 0);
            if ($dir === 'in') {
                $inTotal += $amount;
            } elseif ($dir === 'out') {
                $outTotal += $amount;
            }
        }

        return [
            'days' => (int) ($movements['days'] ?? 30),
            'q' => trim((string) $search),
            'items' => $items,
            'in_total' => round($inTotal, 2),
            'out_total' => round($outTotal, 2),
            'error' => $movements['error'] ?? null,
        ];
    }

    private function emptyMpMovements(): array
    {
        return [
            'days' => 30,
            'in_total' => 0.0,
            'out_total' => 0.0,
            'items' => [],
            'truncated' => false,
            'error' => null,
            'outflows_pending' => false,
            'loading' => true,
        ];
    }

    private function accountsWithBalanceSums(): Builder
    {
        return FinancialAccount::query()
            ->withSum(['transactions as paid_receitas_sum' => function ($q) {
                $q->where('type', 'receita')->where('is_paid', true);
            }], 'amount')
            ->withSum(['transactions as paid_despesas_sum' => function ($q) {
                $q->where('type', 'despesa')->where('is_paid', true);
            }], 'amount')
            ->withSum('transfersIn as transfers_in_sum', 'amount')
            ->withSum('transfersOut as transfers_out_sum', 'amount');
    }

    /**
     * @param  array{in_total?: float, out_total?: float, error?: string|null}|null  $mpMovements
     */
    private function applyDisplayBalance(FinancialAccount $account, ?array $mpMovements): void
    {
        if ($account->isMercadoPago() && is_array($mpMovements) && empty($mpMovements['error'])) {
            $account->current_balance = round(
                (float) ($mpMovements['in_total'] ?? 0) - (float) ($mpMovements['out_total'] ?? 0),
                2
            );
            $account->balance_source = 'mp_flow';

            return;
        }

        $receitas = $account->getAttribute('paid_receitas_sum');
        $despesas = $account->getAttribute('paid_despesas_sum');
        if ($receitas !== null || $despesas !== null) {
            $recebidoEmTransferencias = (float) $account->getAttribute('transfers_in_sum');
            $enviadoEmTransferencias = (float) $account->getAttribute('transfers_out_sum');

            $account->current_balance = round(
                (float) $account->initial_balance
                    + (float) $receitas - (float) $despesas
                    + $recebidoEmTransferencias - $enviadoEmTransferencias,
                2
            );
        } else {
            $account->current_balance = $account->currentBalance();
        }
        $account->balance_source = 'ledger';
    }
}
