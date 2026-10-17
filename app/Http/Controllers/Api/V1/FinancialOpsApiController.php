<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignInstallment;
use App\Models\CampaignSponsor;
use App\Models\FinancialAutomation;
use App\Models\FinancialFixedExpense;
use App\Models\FinancialTransaction;
use App\Policies\Eventos\CampaignPolicy;
use App\Services\Api\FinancialModuleService;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinancialOpsApiController extends Controller
{
    use ResolvesFinancialApi;

    public function __construct(private FinancialModuleService $module) {}

    public function fixedExpenses(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.view-fixed-expenses')) {
            return $denied;
        }

        $reference = $this->resolveMonth($request->query('month'));
        $fixedExpenses = FinancialFixedExpense::query()
            ->with(['category:id,name', 'account:id,name', 'contact:id,name'])
            ->orderBy('due_day')
            ->orderBy('description')
            ->get();

        $monthTransactions = FinancialTransaction::query()
            ->despesas()
            ->whereNotNull('fixed_expense_id')
            ->whereYear('competence_date', $reference->year)
            ->whereMonth('competence_date', $reference->month)
            ->get()
            ->keyBy('fixed_expense_id');

        $items = $fixedExpenses->map(function (FinancialFixedExpense $item) use ($monthTransactions) {
            $tx = $monthTransactions->get($item->id);

            return $this->module->fixedExpensePayload($item, $tx);
        })->values()->all();

        return ApiResponse::success([
            'month' => $reference->format('Y-m'),
            'month_label' => $reference->translatedFormat('F Y'),
            'items' => $items,
            'can_manage' => $request->user()->can('financial.manage-fixed-expenses'),
        ]);
    }

    public function storeFixedExpense(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.manage-fixed-expenses')) {
            return $denied;
        }

        $validated = $this->validateFixedExpense($request);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['amount_variable'] = $request->boolean('amount_variable', false);
        $validated['created_by'] = $request->user()->id;
        $row = FinancialFixedExpense::create($validated);

        return ApiResponse::success($this->module->fixedExpensePayload($row), [], 201);
    }

    public function updateFixedExpense(Request $request, FinancialFixedExpense $fixedExpense): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.manage-fixed-expenses')) {
            return $denied;
        }

        $validated = $this->validateFixedExpense($request);
        $validated['is_active'] = $request->boolean('is_active', $fixedExpense->is_active);
        $validated['amount_variable'] = $request->boolean('amount_variable', $fixedExpense->amount_variable);
        $fixedExpense->update($validated);

        return ApiResponse::success($this->module->fixedExpensePayload($fixedExpense->fresh()));
    }

    public function destroyFixedExpense(Request $request, FinancialFixedExpense $fixedExpense): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.manage-fixed-expenses')) {
            return $denied;
        }

        $fixedExpense->delete();

        return ApiResponse::success(['ok' => true]);
    }

    public function toggleFixedExpense(Request $request, FinancialFixedExpense $fixedExpense): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.manage-fixed-expenses')) {
            return $denied;
        }

        $fixedExpense->update(['is_active' => $request->boolean('is_active', ! $fixedExpense->is_active)]);

        return ApiResponse::success($this->module->fixedExpensePayload($fixedExpense->fresh()));
    }

    public function generateFixedExpenses(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.manage-fixed-expenses')) {
            return $denied;
        }

        $reference = $this->resolveMonth($request->input('month'));
        $monthsAhead = max(1, min(24, (int) $request->input('months_ahead', 12)));
        $fixedExpenses = FinancialFixedExpense::query()->active()->get();
        if ($fixedExpenses->isEmpty()) {
            return ApiResponse::error('Nenhuma despesa fixa ativa para gerar.', 422);
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

        return ApiResponse::success([
            'created' => $created,
            'month' => $reference->format('Y-m'),
        ]);
    }

    public function payFixedExpense(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.manage-fixed-expenses')) {
            return $denied;
        }

        if (! $transaction->fixed_expense_id || $transaction->type !== 'despesa') {
            return ApiResponse::error('Transação inválida para despesa fixa.', 404);
        }

        $validated = $request->validate(['amount' => 'nullable|numeric|min:0.01']);
        if (isset($validated['amount'])) {
            $transaction->amount = $validated['amount'];
        }
        $transaction->is_paid = true;
        $transaction->status = 'pago';
        if (! $transaction->transaction_date) {
            $transaction->transaction_date = now()->toDateString();
        }
        $transaction->save();

        return ApiResponse::success($this->module->transactionPayload($transaction->fresh()));
    }

    public function campaigns(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessCampaign($request, 'view')) {
            return $denied;
        }

        $items = Campaign::query()
            ->with('department:id,name')
            ->withCount('sponsors')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Campaign $row) => $this->module->campaignPayload($row))
            ->all();

        return ApiResponse::success([
            'items' => $items,
            'can_manage' => FinancialSummaryController::campaignAllowed($request->user(), 'manage'),
        ]);
    }

    public function showCampaign(Request $request, Campaign $campaign): JsonResponse
    {
        if ($denied = $this->denyUnlessCampaign($request, 'view')) {
            return $denied;
        }

        $campaign->load('department:id,name');
        $sponsors = CampaignSponsor::query()
            ->where('campaign_id', $campaign->id)
            ->with(['installments' => fn ($q) => $q->orderBy('installment_number')])
            ->orderBy('name')
            ->get()
            ->map(function (CampaignSponsor $sponsor) {
                $paid = $sponsor->installments->where('status', CampaignInstallment::STATUS_PAGO)->sum('amount');
                $pending = $sponsor->installments->where('status', CampaignInstallment::STATUS_PENDENTE);

                return [
                    'id' => $sponsor->id,
                    'name' => $sponsor->name,
                    'phone' => $sponsor->phone,
                    'paid_total' => round((float) $paid, 2),
                    'pending_count' => $pending->count(),
                    'installments' => $sponsor->installments->map(fn (CampaignInstallment $row) => [
                        'id' => $row->id,
                        'number' => $row->installment_number,
                        'amount' => round((float) $row->amount, 2),
                        'due_date' => $row->due_date?->toDateString(),
                        'status' => $row->status,
                        'paid_at' => optional($row->paid_at)?->toDateString(),
                        'payment_method' => $row->payment_method,
                        'receipt_number' => $row->receipt_number,
                    ])->all(),
                ];
            })->all();

        return ApiResponse::success([
            'campaign' => $this->module->campaignPayload($campaign),
            'sponsors' => $sponsors,
            'can_manage' => FinancialSummaryController::campaignAllowed($request->user(), 'manage'),
        ]);
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessCampaign($request, 'manage')) {
            return $denied;
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'goal_amount' => 'nullable|numeric|min:0',
            'installment_amount' => 'required|numeric|min:0.01',
            'installments_count' => 'required|integer|min:1|max:120',
            'first_due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|in:ativa,encerrada,cancelada',
        ]);
        $data['status'] = $data['status'] ?? Campaign::STATUS_ATIVA;
        $data['created_by'] = $request->user()->id;
        $campaign = Campaign::create($data);

        return ApiResponse::success($this->module->campaignPayload($campaign), [], 201);
    }

    public function payInstallment(Request $request, CampaignInstallment $installment): JsonResponse
    {
        if ($denied = $this->denyUnlessCampaign($request, 'manage')) {
            return $denied;
        }

        $data = $request->validate([
            'payment_method' => 'required|in:dinheiro,pix,cartao,outro',
            'paid_at' => 'nullable|date',
        ]);

        if ($installment->isPaid()) {
            return ApiResponse::error('Esta parcela já está paga.', 422);
        }

        $installment->load('sponsor.campaign');
        DB::transaction(function () use ($installment, $data) {
            $campaign = $installment->sponsor->campaign;
            $installment->update([
                'status' => CampaignInstallment::STATUS_PAGO,
                'paid_at' => ! empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : now(),
                'paid_by' => Auth::id(),
                'payment_method' => $data['payment_method'],
                'receipt_number' => $campaign->nextReceiptNumber(),
            ]);
        });

        return ApiResponse::success(['ok' => true, 'receipt_number' => $installment->fresh()->receipt_number]);
    }

    public function reverseInstallment(Request $request, CampaignInstallment $installment): JsonResponse
    {
        if ($denied = $this->denyUnlessCampaign($request, 'manage')) {
            return $denied;
        }

        if (! $installment->isPaid()) {
            return ApiResponse::error('Apenas parcelas pagas podem ser estornadas.', 422);
        }

        $installment->update([
            'status' => CampaignInstallment::STATUS_PENDENTE,
            'paid_at' => null,
            'payment_method' => null,
            'receipt_number' => null,
            'receipt_sent_at' => null,
            'paid_by' => null,
            'reversed_at' => now(),
            'reversed_by' => Auth::id(),
        ]);

        return ApiResponse::success(['ok' => true]);
    }

    public function automations(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.view-automations')) {
            return $denied;
        }

        $items = collect([
            FinancialAutomation::contributionThanks(),
            FinancialAutomation::treasurers(),
            FinancialAutomation::mpTreasuryGroup(),
            FinancialAutomation::dueReminder(),
            FinancialAutomation::smartSummary(),
        ])->map(fn (FinancialAutomation $row) => $this->module->automationPayload($row))->all();

        return ApiResponse::success([
            'items' => $items,
            'can_manage' => $request->user()->can('financial.manage-automations'),
        ]);
    }

    public function toggleAutomation(Request $request, FinancialAutomation $automation): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.manage-automations')) {
            return $denied;
        }

        $validated = $request->validate(['enabled' => 'required|boolean']);
        $automation->update(['enabled' => $validated['enabled']]);

        return ApiResponse::success($this->module->automationPayload($automation->fresh()));
    }

    private function denyUnlessCampaign(Request $request, string $action): ?JsonResponse
    {
        $policy = app(CampaignPolicy::class);
        if (! $policy->action($request->user(), $action)) {
            return $this->denyFinancial();
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFixedExpense(Request $request): array
    {
        return $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'due_day' => 'required|integer|min:1|max:28',
            'category_id' => 'required|integer',
            'account_id' => 'required|integer',
            'cost_center_id' => 'nullable|integer',
            'contact_id' => 'nullable|integer',
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
