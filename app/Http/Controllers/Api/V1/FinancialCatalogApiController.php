<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\FinancialContact;
use App\Models\FinancialCostCenter;
use App\Models\FinancialTransfer;
use App\Services\Api\FinancialModuleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinancialCatalogApiController extends Controller
{
    use ResolvesFinancialApi;

    public function __construct(private FinancialModuleService $module) {}

    public function accounts(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'viewAny', FinancialAccount::class)) {
            return $denied;
        }

        $status = (string) $request->query('status', 'ativas');
        $query = FinancialAccount::query()->orderBy('name');
        if ($status === 'ativas') {
            $query->where('is_active', true);
        } elseif ($status === 'inativas') {
            $query->where('is_active', false);
        }

        $accounts = $query->get()->map(fn (FinancialAccount $row) => $this->module->accountPayload($row))->all();
        $transfers = FinancialTransfer::query()
            ->with(['fromAccount:id,name', 'toAccount:id,name'])
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (FinancialTransfer $row) => $this->module->transferPayload($row))
            ->all();

        return ApiResponse::success([
            'items' => $accounts,
            'transfers' => $transfers,
            'types' => collect(FinancialAccount::TYPES)->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values()->all(),
            'colors' => FinancialAccount::COLORS,
            'can_manage' => $request->user()->can('create', FinancialAccount::class),
        ]);
    }

    public function storeAccount(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'create', FinancialAccount::class)) {
            return $denied;
        }

        $validated = $this->validateAccount($request);
        $validated['is_active'] = $request->boolean('is_active', true);
        $account = FinancialAccount::create($validated);

        return ApiResponse::success($this->module->accountPayload($account), [], 201);
    }

    public function updateAccount(Request $request, FinancialAccount $account): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'update', $account)) {
            return $denied;
        }

        $validated = $this->validateAccount($request);
        $validated['is_active'] = $request->boolean('is_active', $account->is_active);
        $account->update($validated);

        return ApiResponse::success($this->module->accountPayload($account->fresh()));
    }

    public function destroyAccount(Request $request, FinancialAccount $account): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'delete', $account)) {
            return $denied;
        }

        $account->delete();

        return ApiResponse::success(['ok' => true]);
    }

    public function toggleAccount(Request $request, FinancialAccount $account): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'update', $account)) {
            return $denied;
        }

        $account->update(['is_active' => $request->boolean('is_active', ! $account->is_active)]);

        return ApiResponse::success($this->module->accountPayload($account->fresh()));
    }

    public function storeTransfer(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'create', FinancialAccount::class)) {
            return $denied;
        }

        $validated = $request->validate([
            'from_account_id' => 'required|integer|different:to_account_id',
            'to_account_id' => 'required|integer',
            'transfer_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:2000',
        ], [
            'from_account_id.different' => 'A origem e o destino devem ser contas diferentes.',
        ]);
        $validated['created_by'] = $request->user()->id;
        $transfer = FinancialTransfer::create($validated);

        return ApiResponse::success($this->module->transferPayload($transfer), [], 201);
    }

    public function destroyTransfer(Request $request, FinancialTransfer $transfer): JsonResponse
    {
        $from = $transfer->fromAccount;
        if (! $from || $request->user()->cannot('delete', $from)) {
            return $this->denyFinancial();
        }

        $transfer->delete();

        return ApiResponse::success(['ok' => true]);
    }

    public function categories(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'viewAny', FinancialCategory::class)) {
            return $denied;
        }

        $items = FinancialCategory::query()->orderBy('name')->get()->map(fn (FinancialCategory $row) => [
            'id' => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
            'type' => $row->type,
            'description' => $row->description,
            'sends_receipt' => (bool) $row->sends_receipt,
        ])->all();

        return ApiResponse::success([
            'items' => $items,
            'can_manage' => $request->user()->can('create', FinancialCategory::class),
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'create', FinancialCategory::class)) {
            return $denied;
        }

        $validated = $this->validateCategory($request);
        $validated['sends_receipt'] = $request->boolean('sends_receipt') && $validated['type'] === 'receita';
        $row = FinancialCategory::create($validated);

        return ApiResponse::success([
            'id' => $row->id,
            'name' => $row->name,
            'type' => $row->type,
            'sends_receipt' => (bool) $row->sends_receipt,
        ], [], 201);
    }

    public function updateCategory(Request $request, FinancialCategory $category): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'update', $category)) {
            return $denied;
        }

        $validated = $this->validateCategory($request);
        $validated['sends_receipt'] = $request->boolean('sends_receipt') && $validated['type'] === 'receita';
        $category->update($validated);

        return ApiResponse::success([
            'id' => $category->id,
            'name' => $category->name,
            'type' => $category->type,
            'sends_receipt' => (bool) $category->sends_receipt,
        ]);
    }

    public function destroyCategory(Request $request, FinancialCategory $category): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'delete', $category)) {
            return $denied;
        }

        $category->delete();

        return ApiResponse::success(['ok' => true]);
    }

    public function contacts(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'viewAny', FinancialContact::class)) {
            return $denied;
        }

        $items = FinancialContact::query()->orderBy('name')->get()->map(fn (FinancialContact $row) => [
            'id' => $row->id,
            'name' => $row->name,
            'email' => $row->email,
            'type' => $row->type,
            'type_label' => $row->type_formatted,
            'phone' => $row->phone_1,
            'cpf' => $row->cpf,
            'cnpj' => $row->cnpj,
            'notes' => $row->notes,
        ])->all();

        return ApiResponse::success([
            'items' => $items,
            'can_manage' => $request->user()->can('create', FinancialContact::class),
        ]);
    }

    public function storeContact(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'create', FinancialContact::class)) {
            return $denied;
        }

        $row = FinancialContact::create($this->validateContact($request));

        return ApiResponse::success(['id' => $row->id, 'name' => $row->name, 'type' => $row->type], [], 201);
    }

    public function updateContact(Request $request, FinancialContact $contact): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'update', $contact)) {
            return $denied;
        }

        $contact->update($this->validateContact($request));

        return ApiResponse::success(['id' => $contact->id, 'name' => $contact->name, 'type' => $contact->type]);
    }

    public function destroyContact(Request $request, FinancialContact $contact): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'delete', $contact)) {
            return $denied;
        }

        $contact->delete();

        return ApiResponse::success(['ok' => true]);
    }

    public function costCenters(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'viewAny', FinancialCostCenter::class)) {
            return $denied;
        }

        $items = FinancialCostCenter::query()->orderBy('name')->get()->map(fn (FinancialCostCenter $row) => [
            'id' => $row->id,
            'name' => $row->name,
            'description' => $row->description,
        ])->all();

        return ApiResponse::success([
            'items' => $items,
            'can_manage' => $request->user()->can('create', FinancialCostCenter::class),
        ]);
    }

    public function storeCostCenter(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'create', FinancialCostCenter::class)) {
            return $denied;
        }

        $row = FinancialCostCenter::create($this->validateCostCenter($request));

        return ApiResponse::success(['id' => $row->id, 'name' => $row->name, 'description' => $row->description], [], 201);
    }

    public function updateCostCenter(Request $request, FinancialCostCenter $costCenter): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'update', $costCenter)) {
            return $denied;
        }

        $costCenter->update($this->validateCostCenter($request));

        return ApiResponse::success([
            'id' => $costCenter->id,
            'name' => $costCenter->name,
            'description' => $costCenter->description,
        ]);
    }

    public function destroyCostCenter(Request $request, FinancialCostCenter $costCenter): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'delete', $costCenter)) {
            return $denied;
        }

        $costCenter->delete();

        return ApiResponse::success(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAccount(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(FinancialAccount::TYPES))],
            'bank_name' => 'nullable|string|max:255',
            'initial_balance' => 'required|numeric',
            'color' => ['nullable', 'string', Rule::in(FinancialAccount::COLORS)],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $data['color'] = $data['color'] ?? FinancialAccount::COLORS[0];

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'type' => 'required|in:receita,despesa',
            'sends_receipt' => 'nullable|boolean',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateContact(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'type' => 'required|in:pessoa_fisica,pessoa_juridica',
            'cpf' => 'nullable|string|max:14',
            'cnpj' => 'nullable|string|max:18',
            'phone_1' => 'nullable|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCostCenter(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
    }
}
