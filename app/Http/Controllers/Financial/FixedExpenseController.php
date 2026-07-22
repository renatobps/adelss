<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\FinancialContact;
use App\Models\FinancialCostCenter;
use App\Models\FinancialFixedExpense;
use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FixedExpenseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('financial.view-fixed-expenses');

        $reference = $this->resolveMonth($request->input('month'));
        $fixedExpenses = FinancialFixedExpense::query()
            ->with(['category', 'account', 'contact', 'costCenter'])
            ->orderBy('due_day')
            ->orderBy('description')
            ->get();

        $monthTransactions = FinancialTransaction::query()
            ->despesas()
            ->whereNotNull('fixed_expense_id')
            ->whereYear('competence_date', $reference->year)
            ->whereMonth('competence_date', $reference->month)
            ->with(['category', 'account', 'fixedExpense'])
            ->get()
            ->keyBy('fixed_expense_id');

        $activeCount = $fixedExpenses->where('is_active', true)->count();
        $pendingGeneration = $fixedExpenses
            ->where('is_active', true)
            ->filter(fn (FinancialFixedExpense $item) => !$monthTransactions->has($item->id))
            ->values();

        $monthItems = $fixedExpenses
            ->where('is_active', true)
            ->map(function (FinancialFixedExpense $item) use ($monthTransactions, $reference) {
                $tx = $monthTransactions->get($item->id);

                return [
                    'fixed' => $item,
                    'transaction' => $tx,
                    'due_date' => $item->dueDateFor($reference),
                    'generated' => (bool) $tx,
                    'paid' => (bool) ($tx?->is_paid),
                    'amount' => (float) ($tx?->amount ?? $item->amount),
                ];
            })
            ->values();

        $generatedCount = $monthItems->where('generated', true)->count();
        $paidCount = $monthItems->where('paid', true)->count();
        $monthTotal = $monthItems->sum('amount');
        $monthPendingTotal = $monthItems->where('generated', true)->where('paid', false)->sum('amount');

        $categories = FinancialCategory::despesas()->orderBy('name')->get(['id', 'name']);
        $accounts = FinancialAccount::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $costCenters = FinancialCostCenter::orderBy('name')->get(['id', 'name']);
        $contacts = FinancialContact::orderBy('name')->get(['id', 'name']);
        $canManage = Auth::user()?->can('financial.manage-fixed-expenses');

        return view('financial.fixed-expenses.index', compact(
            'fixedExpenses',
            'monthItems',
            'reference',
            'pendingGeneration',
            'activeCount',
            'generatedCount',
            'paidCount',
            'monthTotal',
            'monthPendingTotal',
            'categories',
            'accounts',
            'costCenters',
            'contacts',
            'canManage'
        ));
    }

    public function store(Request $request)
    {
        $this->authorize('financial.manage-fixed-expenses');

        $validated = $this->validateFixedExpense($request);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['amount_variable'] = $request->boolean('amount_variable', false);
        $validated['created_by'] = Auth::id();

        FinancialFixedExpense::create($validated);

        return redirect()
            ->route('financial.fixed-expenses.index')
            ->with('success', 'Despesa fixa cadastrada com sucesso.');
    }

    public function update(Request $request, FinancialFixedExpense $fixedExpense)
    {
        $this->authorize('financial.manage-fixed-expenses');

        $validated = $this->validateFixedExpense($request);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['amount_variable'] = $request->boolean('amount_variable', false);

        $fixedExpense->update($validated);

        return redirect()
            ->route('financial.fixed-expenses.index', ['month' => $request->input('month')])
            ->with('success', 'Despesa fixa atualizada com sucesso.');
    }

    public function destroy(FinancialFixedExpense $fixedExpense)
    {
        $this->authorize('financial.manage-fixed-expenses');

        $fixedExpense->delete();

        return redirect()
            ->route('financial.fixed-expenses.index')
            ->with('success', 'Despesa fixa removida com sucesso.');
    }

    public function toggle(Request $request, FinancialFixedExpense $fixedExpense)
    {
        $this->authorize('financial.manage-fixed-expenses');

        $fixedExpense->update([
            'is_active' => $request->boolean('is_active', !$fixedExpense->is_active),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $fixedExpense->is_active,
                'message' => $fixedExpense->is_active ? 'Despesa fixa ativada.' : 'Despesa fixa desativada.',
            ]);
        }

        return back()->with('success', $fixedExpense->is_active ? 'Despesa fixa ativada.' : 'Despesa fixa desativada.');
    }

    public function generate(Request $request)
    {
        $this->authorize('financial.manage-fixed-expenses');

        $reference = $this->resolveMonth($request->input('month'));
        // Gera o mês atual e os meses subsequentes (até 12 meses à frente)
        $monthsAhead = max(1, min(24, (int) $request->input('months_ahead', 12)));

        $fixedExpenses = FinancialFixedExpense::query()->active()->get();
        if ($fixedExpenses->isEmpty()) {
            return back()->with('error', 'Nenhuma despesa fixa ativa para gerar.');
        }

        $created = 0;

        DB::transaction(function () use ($fixedExpenses, $reference, $monthsAhead, &$created) {
            foreach ($fixedExpenses as $fixedExpense) {
                for ($i = 0; $i < $monthsAhead; $i++) {
                    $month = $reference->copy()->startOfMonth()->addMonths($i);
                    if ($this->createTransactionForMonth($fixedExpense, $month)) {
                        $created++;
                    }
                }
            }
        });

        if ($created === 0) {
            return back()->with('success', 'Nenhuma despesa nova foi gerada. Os meses já estavam preenchidos.');
        }

        $label = $reference->translatedFormat('F Y');

        return redirect()
            ->route('financial.fixed-expenses.index', ['month' => $reference->format('Y-m')])
            ->with('success', "{$created} despesa(s) gerada(s) a partir de {$label} (meses subsequentes incluídos).");
    }

    public function pay(Request $request, FinancialTransaction $transaction)
    {
        $this->authorize('financial.manage-fixed-expenses');

        if (!$transaction->fixed_expense_id || $transaction->type !== 'despesa') {
            abort(404);
        }

        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        if (isset($validated['amount'])) {
            $transaction->amount = $validated['amount'];
        }

        $transaction->is_paid = true;
        $transaction->status = 'pago';
        if (!$transaction->transaction_date) {
            $transaction->transaction_date = now()->toDateString();
        }
        $transaction->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Despesa marcada como paga.',
            ]);
        }

        return back()->with('success', 'Despesa marcada como paga.');
    }

    private function validateFixedExpense(Request $request): array
    {
        return $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'due_day' => 'required|integer|min:1|max:28',
            'category_id' => 'required|exists:financial_categories,id',
            'account_id' => 'required|exists:financial_accounts,id',
            'cost_center_id' => 'nullable|exists:financial_cost_centers,id',
            'contact_id' => 'nullable|exists:financial_contacts,id',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    private function resolveMonth(?string $month): Carbon
    {
        try {
            if ($month) {
                return Carbon::createFromFormat('Y-m', $month)->startOfMonth()->locale('pt_BR');
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return now()->startOfMonth()->locale('pt_BR');
    }

    private function createTransactionForMonth(FinancialFixedExpense $fixedExpense, Carbon $month): bool
    {
        $exists = FinancialTransaction::query()
            ->where('fixed_expense_id', $fixedExpense->id)
            ->whereYear('competence_date', $month->year)
            ->whereMonth('competence_date', $month->month)
            ->exists();

        if ($exists) {
            return false;
        }

        $dueDate = $fixedExpense->dueDateFor($month);

        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => $dueDate->toDateString(),
            'competence_date' => $month->copy()->startOfMonth()->toDateString(),
            'description' => $fixedExpense->description,
            'amount' => $fixedExpense->amount,
            'is_paid' => false,
            'due_date' => $dueDate->toDateString(),
            'status' => 'a_pagar',
            'contact_id' => $fixedExpense->contact_id,
            'category_id' => $fixedExpense->category_id,
            'account_id' => $fixedExpense->account_id,
            'cost_center_id' => $fixedExpense->cost_center_id,
            'payment_type' => 'unico',
            'notes' => $fixedExpense->notes,
            'fixed_expense_id' => $fixedExpense->id,
            'created_by' => Auth::id(),
        ]);

        return true;
    }
}
