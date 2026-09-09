<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Services\FinancialNotificationService;
use App\Services\Payments\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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

        $query = FinancialAccount::query()->orderBy('name');

        if ($status === 'ativas') {
            $query->where('is_active', true);
        } elseif ($status === 'inativas') {
            $query->where('is_active', false);
        }

        $listedAccounts = $query->get();
        $hasMercadoPagoAccount = FinancialAccount::query()
            ->get()
            ->contains(fn (FinancialAccount $account) => $account->isMercadoPago());
        $mpMovements = $hasMercadoPagoAccount
            ? $this->loadMercadoPagoMovements(refreshOutflowReports: true)
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

        $saldoAtivas = FinancialAccount::where('is_active', true)
            ->get()
            ->sum(function (FinancialAccount $account) use ($mpMovements) {
                $this->applyDisplayBalance($account, $mpMovements);

                return (float) $account->current_balance;
            });

        $types = FinancialAccount::TYPES;
        $colors = FinancialAccount::COLORS;

        return view('financial.accounts.index', compact(
            'accounts',
            'status',
            'counts',
            'saldoAtivas',
            'types',
            'colors',
            'mpMovements'
        ));
    }

    public function mercadoPagoMovements()
    {
        $this->authorize('viewAny', FinancialAccount::class);

        $mpMovements = $this->loadMercadoPagoMovements(refreshOutflowReports: false);
        $mpBalance = round(
            (float) ($mpMovements['in_total'] ?? 0) - (float) ($mpMovements['out_total'] ?? 0),
            2
        );

        $saldoAtivas = FinancialAccount::where('is_active', true)
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
    private function loadMercadoPagoMovements(bool $refreshOutflowReports): array
    {
        $mpMovements = $this->mercadoPago->getPaymentMovements(true, 30, 50, $refreshOutflowReports);

        if (empty($mpMovements['error'])) {
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

        $account->current_balance = $account->currentBalance();
        $account->balance_source = 'ledger';
    }
}
