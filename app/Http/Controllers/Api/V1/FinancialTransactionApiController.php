<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use App\Services\Api\FinancialModuleService;
use App\Services\Financial\PrebendaReceiptService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class FinancialTransactionApiController extends Controller
{
    use ResolvesFinancialApi;

    public function __construct(
        private FinancialModuleService $module,
        private PrebendaReceiptService $prebendaReceipts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'viewAny', FinancialTransaction::class)) {
            return $denied;
        }

        $start = (string) $request->query('start_date', now()->startOfMonth()->toDateString());
        $end = (string) $request->query('end_date', now()->endOfMonth()->toDateString());
        $perPage = min(100, max(1, (int) $request->query('per_page', 30)));
        $page = max(1, (int) $request->query('page', 1));

        $query = FinancialTransaction::query()
            ->with(['category:id,name,type', 'account:id,name,type', 'member:id,name', 'contact:id,name', 'attachments'])
            ->whereBetween('transaction_date', [$start, $end]);

        $this->restrictByTypePermission($request, $query);

        if ($type = $request->query('type')) {
            if (in_array($type, ['receita', 'despesa'], true)) {
                $query->where('type', $type);
            }
        }
        if ($request->query('paid') === '1') {
            $query->where('is_paid', true);
        } elseif ($request->query('paid') === '0') {
            $query->where('is_paid', false);
        }
        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($q) {
                $builder->where('description', 'like', "%{$q}%")
                    ->orWhere('received_from_other', 'like', "%{$q}%");
            });
        }

        $paginator = $query->orderByDesc('transaction_date')->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        $summary = [
            'total_received' => round((float) FinancialTransaction::receitas()->whereBetween('transaction_date', [$start, $end])->where('is_paid', true)->sum('amount'), 2),
            'total_paid' => round((float) FinancialTransaction::despesas()->whereBetween('transaction_date', [$start, $end])->where('is_paid', true)->sum('amount'), 2),
            'to_receive' => round((float) FinancialTransaction::receitas()->whereBetween('transaction_date', [$start, $end])->where('is_paid', false)->sum('amount'), 2),
            'to_pay' => round((float) FinancialTransaction::despesas()->whereBetween('transaction_date', [$start, $end])->where('is_paid', false)->sum('amount'), 2),
        ];

        return ApiResponse::success([
            'items' => $paginator->getCollection()->map(fn (FinancialTransaction $row) => $this->module->transactionPayload($row))->all(),
            'summary' => $summary,
            'start_date' => $start,
            'end_date' => $end,
            'page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function show(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'view', $transaction)) {
            return $denied;
        }

        return ApiResponse::success($this->module->transactionPayload($transaction));
    }

    public function store(Request $request): JsonResponse
    {
        $type = $request->input('type') === 'despesa' ? 'despesa' : 'receita';
        $ability = $type === 'receita' ? 'createReceita' : 'createDespesa';
        if ($denied = $this->guardFinancial($request, $ability, FinancialTransaction::class)) {
            return $denied;
        }

        $validated = $this->validatePayload($request, $type);
        $paid = $request->boolean('is_paid', true);
        $validated['type'] = $type;
        $validated['is_paid'] = $paid;
        $validated['status'] = $paid
            ? ($type === 'receita' ? 'recebido' : 'pago')
            : ($type === 'receita' ? 'a_receber' : 'a_pagar');
        $validated['payment_type'] = $validated['payment_type'] ?? 'unico';
        $validated['created_by'] = $request->user()->id;
        $this->normalizeParty($request, $validated, $type);

        $row = FinancialTransaction::create($validated);

        return ApiResponse::success($this->module->transactionPayload($row->fresh()), [], 201);
    }

    public function update(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'update', $transaction)) {
            return $denied;
        }

        $validated = $this->validatePayload($request, $transaction->type);
        $paid = $request->boolean('is_paid', $transaction->is_paid);
        $validated['is_paid'] = $paid;
        $validated['status'] = $paid
            ? ($transaction->type === 'receita' ? 'recebido' : 'pago')
            : ($transaction->type === 'receita' ? 'a_receber' : 'a_pagar');
        $this->normalizeParty($request, $validated, $transaction->type);
        $transaction->update($validated);

        return ApiResponse::success($this->module->transactionPayload($transaction->fresh()));
    }

    public function destroy(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'delete', $transaction)) {
            return $denied;
        }

        $transaction->delete();

        return ApiResponse::success(['ok' => true]);
    }

    public function pay(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'update', $transaction)) {
            return $denied;
        }

        $amount = $request->input('amount');
        if ($amount !== null) {
            $request->validate(['amount' => 'numeric|min:0.01']);
            $transaction->amount = $amount;
        }
        $transaction->is_paid = true;
        $transaction->status = $transaction->type === 'receita' ? 'recebido' : 'pago';
        if (! $transaction->transaction_date) {
            $transaction->transaction_date = now()->toDateString();
        }
        $transaction->save();

        return ApiResponse::success($this->module->transactionPayload($transaction->fresh()));
    }

    public function duplicate(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'duplicate', $transaction)) {
            return $denied;
        }

        $copy = $transaction->replicate();
        $copy->transaction_date = now()->toDateString();
        $copy->is_paid = false;
        $copy->status = $transaction->type === 'receita' ? 'a_receber' : 'a_pagar';
        $copy->created_by = $request->user()->id;
        $copy->save();

        return ApiResponse::success($this->module->transactionPayload($copy), [], 201);
    }

    public function correct(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'update', $transaction)) {
            return $denied;
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0.01',
            'member_id' => 'nullable|integer',
            'received_from_other' => 'nullable|string|max:255',
        ]);

        if (array_key_exists('description', $validated) && $validated['description'] !== null) {
            $transaction->description = $validated['description'];
        }
        if (array_key_exists('amount', $validated) && $validated['amount'] !== null) {
            $transaction->amount = $validated['amount'];
        }
        if (array_key_exists('member_id', $validated)) {
            $transaction->member_id = $validated['member_id'] ?: null;
            if ($validated['member_id']) {
                $transaction->received_from_other = null;
                $transaction->contact_id = null;
            }
        }
        if (array_key_exists('received_from_other', $validated)) {
            $transaction->received_from_other = $validated['received_from_other'] ?: null;
            if ($transaction->received_from_other) {
                $transaction->member_id = null;
            }
        }
        $transaction->save();

        return ApiResponse::success($this->module->transactionPayload($transaction->fresh()));
    }

    public function receipts(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'viewAny', FinancialTransaction::class)) {
            return $denied;
        }

        $tab = (string) $request->query('tab', 'arquivos');
        if (! in_array($tab, ['pendentes', 'arquivos', 'receitas'], true)) {
            $tab = 'arquivos';
        }
        [$startPeriod, $endPeriod] = $this->module->periodBounds((string) $request->query('period', '1month'));
        $start = (string) $request->query('start_date', $startPeriod->toDateString());
        $end = (string) $request->query('end_date', $endPeriod->toDateString());

        return ApiResponse::success($this->module->receipts($tab, $start, $end));
    }

    public function receiptPdf(Request $request, FinancialTransaction $transaction): Response|JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'receipt', $transaction)) {
            return $denied;
        }

        return $this->module->streamReceiptPdf($transaction);
    }

    public function showAttachment(Request $request, FinancialTransaction $transaction, FinancialTransactionAttachment $attachment): Response|JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'view', $transaction)) {
            return $denied;
        }

        if ((int) $attachment->transaction_id !== (int) $transaction->id) {
            return ApiResponse::error('Arquivo não encontrado neste lançamento.', 404);
        }

        $disk = Storage::disk('public');
        if ($attachment->isSystemGenerated() && (! $attachment->file_path || ! $disk->exists($attachment->file_path))) {
            $fresh = $this->prebendaReceipts->regenerate($transaction);
            if ($fresh) {
                $attachment = $fresh;
            }
        }

        if ($attachment->file_path && $disk->exists($attachment->file_path)) {
            return $disk->response($attachment->file_path, $attachment->file_name, [
                'Content-Disposition' => 'inline; filename="'.$attachment->file_name.'"',
            ]);
        }

        if ($attachment->isSystemGenerated()) {
            return $this->module->streamReceiptPdf($transaction);
        }

        return ApiResponse::error('O arquivo deste recibo não está no servidor. Envie o comprovante novamente.', 404);
    }

    public function storeAttachments(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'update', $transaction)) {
            return $denied;
        }

        $files = $this->incomingFiles($request);
        if ($files === []) {
            return ApiResponse::error('Envie pelo menos um arquivo.', 422);
        }

        $remaining = 5 - $transaction->attachments()->count();
        if ($remaining <= 0) {
            return ApiResponse::error('Este lançamento já tem o máximo de 5 arquivos.', 422);
        }
        if (count($files) > $remaining) {
            return ApiResponse::error("Você pode enviar no máximo {$remaining} arquivo(s) neste lançamento.", 422);
        }

        foreach ($files as $file) {
            if (! $file->isValid()) {
                return ApiResponse::error('Arquivo inválido.', 422);
            }
            if ($file->getSize() > 10240 * 1024) {
                return ApiResponse::error('Cada arquivo pode ter no máximo 10 MB.', 422);
            }
            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'heic', 'heif'], true)) {
                return ApiResponse::error('Envie imagem (JPG, PNG, WEBP) ou PDF.', 422);
            }
        }

        foreach ($files as $file) {
            $path = $file->store('financial/transactions/attachments', 'public');
            FinancialTransactionAttachment::create([
                'transaction_id' => $transaction->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName() ?: 'recibo',
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        return ApiResponse::success($this->module->transactionPayload($transaction->fresh()), [], 201);
    }

    public function destroyAttachment(Request $request, FinancialTransaction $transaction, FinancialTransactionAttachment $attachment): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'update', $transaction)) {
            return $denied;
        }

        if ((int) $attachment->transaction_id !== (int) $transaction->id) {
            return ApiResponse::error('Arquivo não encontrado neste lançamento.', 404);
        }

        if ($attachment->file_path) {
            Storage::disk('public')->delete($attachment->file_path);
        }
        $attachment->delete();

        return ApiResponse::success($this->module->transactionPayload($transaction->fresh()));
    }

    /**
     * @return list<UploadedFile>
     */
    private function incomingFiles(Request $request): array
    {
        $collected = [];
        foreach ($request->allFiles() as $file) {
            if ($file instanceof UploadedFile) {
                $collected[] = $file;
                continue;
            }
            if (is_array($file)) {
                foreach ($file as $inner) {
                    if ($inner instanceof UploadedFile) {
                        $collected[] = $inner;
                    }
                }
            }
        }

        return $collected;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\FinancialTransaction>  $query
     */
    private function restrictByTypePermission(Request $request, $query): void
    {
        $user = $request->user();
        if ($user->is_admin) {
            return;
        }

        $canReceita = $user->hasPermission('financial.receitas.view') || $user->hasPermission('financial.receitas.manage');
        $canDespesa = $user->hasPermission('financial.despesas.view') || $user->hasPermission('financial.despesas.manage');
        if ($canReceita && ! $canDespesa) {
            $query->receitas();
        } elseif ($canDespesa && ! $canReceita) {
            $query->despesas();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, string $type): array
    {
        return $request->validate([
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'is_paid' => 'nullable|boolean',
            'category_id' => 'nullable|integer',
            'account_id' => 'required|integer',
            'cost_center_id' => 'nullable|integer',
            'member_id' => 'nullable|integer',
            'contact_id' => 'nullable|integer',
            'received_from_other' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date',
            'competence_date' => 'nullable|date',
            'payment_type' => ['nullable', Rule::in(['unico', 'parcelado'])],
        ], [
            'transaction_date.required' => 'Informe a data.',
            'description.required' => 'Informe a descrição.',
            'amount.required' => 'Informe o valor.',
            'account_id.required' => $type === 'receita'
                ? 'Escolha como o valor foi recebido.'
                : 'Escolha como o valor foi pago.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function normalizeParty(Request $request, array &$validated, string $type): void
    {
        if ($type === 'receita') {
            $memberId = $request->input('member_id');
            if ($memberId === 'other' || empty($memberId)) {
                $validated['member_id'] = null;
            } else {
                $validated['received_from_other'] = null;
            }
            $validated['contact_id'] = null;
        } else {
            $validated['member_id'] = null;
            $validated['received_from_other'] = null;
        }
    }
}
