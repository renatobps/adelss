<?php

namespace App\Http\Controllers\Eventos;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignExpense;
use App\Models\CampaignExpenseAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Transações do evento: gastos (comprovante obrigatório) e receitas extras
 * (doação externa, venda, rifa — comprovante opcional).
 */
class CampaignExpenseController extends Controller
{
    private const MAX_FILES = 5;
    private const MAX_FILE_KB = 10240; // 10MB

    /** Transações de uma campanha específica (com formulário de cadastro). */
    public function index(Request $request, Campaign $campaign)
    {
        $campaign->load('department');
        $filters = $this->filters($request);

        $expenses = $this->applyFilters($campaign->expenses()->getQuery(), $filters)
            ->with(['attachments', 'creator:id,name'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totals = $this->totals($campaign);

        return view('eventos.campaigns.expenses.index', [
            'campaign' => $campaign,
            'expenses' => $expenses,
            'filters' => $filters,
            'totals' => $totals,
            'byCategory' => $this->byCategory($campaign, CampaignExpense::KIND_GASTO),
            'byIncomeCategory' => $this->byCategory($campaign, CampaignExpense::KIND_RECEITA),
            'categories' => CampaignExpense::CATEGORIES,
            'incomeCategories' => CampaignExpense::INCOME_CATEGORIES,
            'paymentMethods' => CampaignExpense::PAYMENT_METHODS,
        ]);
    }

    /** Visão consolidada de transações de todos os eventos. */
    public function all(Request $request)
    {
        $filters = $this->filters($request);
        $campaignId = (int) $request->query('campaign');

        $query = CampaignExpense::query()->with(['campaign:id,name,status', 'attachments']);
        if ($campaignId > 0) {
            $query->where('campaign_id', $campaignId);
        }

        $expenses = $this->applyFilters($query, $filters)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $campaigns = Campaign::query()
            ->withSum(['expenses as expenses_total' => fn ($q) => $q->gastos()], 'amount')
            ->withSum(['expenses as income_total' => fn ($q) => $q->receitas()], 'amount')
            ->withCount('expenses')
            ->orderByDesc('created_at')
            ->get();

        return view('eventos.expenses.index', [
            'expenses' => $expenses,
            'campaigns' => $campaigns,
            'selectedCampaign' => $campaignId,
            'filters' => $filters,
            'grandTotalExpenses' => (float) CampaignExpense::query()->gastos()->sum('amount'),
            'grandTotalIncome' => (float) CampaignExpense::query()->receitas()->sum('amount'),
            'categories' => CampaignExpense::CATEGORIES,
            'incomeCategories' => CampaignExpense::INCOME_CATEGORIES,
        ]);
    }

    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        $kind = $this->requestedKind($request);
        $data = $this->validateExpense($request, $kind, requireReceipt: $kind === CampaignExpense::KIND_GASTO);

        DB::transaction(function () use ($campaign, $data, $request) {
            $expense = $campaign->expenses()->create([
                ...$this->expenseAttributes($data),
                'created_by' => Auth::id(),
            ]);

            $this->storeAttachments($expense, $request->file('attachments', []));
        });

        $label = $kind === CampaignExpense::KIND_RECEITA ? 'Receita registrada.' : 'Gasto registrado com comprovante.';

        return redirect()
            ->route('eventos.campaigns.expenses.index', $campaign)
            ->with('success', $label);
    }

    public function update(Request $request, CampaignExpense $expense): RedirectResponse
    {
        $expense->load('attachments');
        $kind = $this->requestedKind($request);

        $removeIds = collect($request->input('remove_attachments', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        $remaining = $expense->attachments->whereNotIn('id', $removeIds)->count();
        $requireReceipt = $kind === CampaignExpense::KIND_GASTO && $remaining === 0;
        $data = $this->validateExpense($request, $kind, requireReceipt: $requireReceipt);

        DB::transaction(function () use ($expense, $data, $request, $removeIds) {
            $expense->update($this->expenseAttributes($data));

            foreach ($expense->attachments->whereIn('id', $removeIds) as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
                $attachment->delete();
            }

            $this->storeAttachments($expense, $request->file('attachments', []));
        });

        return redirect()
            ->route('eventos.campaigns.expenses.index', $expense->campaign_id)
            ->with('success', 'Lançamento atualizado.');
    }

    public function destroy(CampaignExpense $expense): RedirectResponse
    {
        $campaignId = $expense->campaign_id;

        DB::transaction(function () use ($expense) {
            foreach ($expense->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            $expense->delete();
        });

        return redirect()
            ->route('eventos.campaigns.expenses.index', $campaignId)
            ->with('success', 'Lançamento excluído.');
    }

    /** Entrega o comprovante (inline para imagens/PDF, download para o resto). */
    public function attachment(Request $request, CampaignExpenseAttachment $attachment): StreamedResponse
    {
        $disk = Storage::disk('public');
        abort_unless($disk->exists($attachment->file_path), 404);

        $inline = $attachment->isImage() || $attachment->file_type === 'application/pdf';
        if ($request->boolean('download')) {
            $inline = false;
        }

        return $inline
            ? $disk->response($attachment->file_path, $attachment->file_name)
            : $disk->download($attachment->file_path, $attachment->file_name);
    }

    // ------------------------------------------------------------------

    /**
     * @return array{q:string,kind:string,category:string,from:?string,to:?string}
     */
    private function filters(Request $request): array
    {
        $text = fn (string $key) => is_string($v = $request->query($key)) ? trim($v) : '';
        $kind = $text('kind');
        $category = $text('category');
        $allCategories = CampaignExpense::CATEGORIES + CampaignExpense::INCOME_CATEGORIES;

        return [
            'q' => $text('q'),
            'kind' => array_key_exists($kind, CampaignExpense::KINDS) ? $kind : '',
            'category' => array_key_exists($category, $allCategories) ? $category : '',
            'from' => $text('from') ?: null,
            'to' => $text('to') ?: null,
        ];
    }

    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        if ($filters['q'] !== '') {
            $term = '%' . $filters['q'] . '%';
            $query->where(fn ($q) => $q->where('description', 'like', $term)
                ->orWhere('supplier', 'like', $term)
                ->orWhere('notes', 'like', $term));
        }
        if ($filters['kind'] !== '') {
            $query->where('kind', $filters['kind']);
        }
        if ($filters['category'] !== '') {
            $query->where('category', $filters['category']);
        }
        if ($filters['from']) {
            $query->whereDate('expense_date', '>=', $filters['from']);
        }
        if ($filters['to']) {
            $query->whereDate('expense_date', '<=', $filters['to']);
        }

        return $query;
    }

    /**
     * @return array{spent:float,income:float,raised:float,balance:float,count:int}
     */
    private function totals(Campaign $campaign): array
    {
        $spent = $campaign->totalExpenses();
        $income = $campaign->totalExtraIncome();
        $raised = $campaign->totalRaised();

        return [
            'spent' => $spent,
            'income' => $income,
            'raised' => $raised,
            'balance' => $raised + $income - $spent,
            'count' => $campaign->expenses()->count(),
        ];
    }

    /**
     * @return array<string, float>
     */
    private function byCategory(Campaign $campaign, string $kind): array
    {
        return $campaign->expenses()
            ->where('kind', $kind)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    private function requestedKind(Request $request): string
    {
        $kind = (string) $request->input('kind', CampaignExpense::KIND_GASTO);

        return array_key_exists($kind, CampaignExpense::KINDS) ? $kind : CampaignExpense::KIND_GASTO;
    }

    private function validateExpense(Request $request, string $kind, bool $requireReceipt): array
    {
        $isIncome = $kind === CampaignExpense::KIND_RECEITA;
        $categories = $isIncome ? CampaignExpense::INCOME_CATEGORIES : CampaignExpense::CATEGORIES;
        $noun = $isIncome ? 'receita' : 'gasto';

        return $request->validate(
            [
                'kind' => 'required|in:' . implode(',', array_keys(CampaignExpense::KINDS)),
                'description' => 'required|string|max:255',
                'category' => 'required|in:' . implode(',', array_keys($categories)),
                'amount' => 'required|numeric|min:0.01',
                'expense_date' => 'required|date',
                'supplier' => 'nullable|string|max:255',
                'payment_method' => 'nullable|in:' . implode(',', array_keys(CampaignExpense::PAYMENT_METHODS)),
                'notes' => 'nullable|string|max:2000',
                'attachments' => ($requireReceipt ? 'required' : 'nullable') . '|array|min:' . ($requireReceipt ? 1 : 0) . '|max:' . self::MAX_FILES,
                'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,heic,pdf|max:' . self::MAX_FILE_KB,
                'remove_attachments' => 'nullable|array',
                'remove_attachments.*' => 'integer',
            ],
            [
                'kind.required' => 'Informe se o lançamento é gasto ou receita.',
                'description.required' => "Informe a descrição da {$noun}.",
                'category.required' => "Selecione a categoria da {$noun}.",
                'amount.required' => "Informe o valor da {$noun}.",
                'amount.min' => "O valor da {$noun} deve ser maior que zero.",
                'expense_date.required' => "Informe a data da {$noun}.",
                'attachments.required' => 'Anexe ao menos um comprovante de despesa (nota, recibo ou foto).',
                'attachments.min' => 'Anexe ao menos um comprovante de despesa (nota, recibo ou foto).',
                'attachments.max' => 'Máximo de ' . self::MAX_FILES . ' comprovantes por lançamento.',
                'attachments.*.mimes' => 'Comprovantes devem ser imagens (JPG, PNG, WEBP, HEIC) ou PDF.',
                'attachments.*.max' => 'Cada comprovante pode ter no máximo 10MB.',
            ]
        );
    }

    private function expenseAttributes(array $data): array
    {
        return [
            'kind' => $data['kind'],
            'description' => $data['description'],
            'category' => $data['category'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'supplier' => $data['supplier'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeAttachments(CampaignExpense $expense, array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store(CampaignExpenseAttachment::STORAGE_DIR . '/' . $expense->campaign_id, 'public');

            $expense->attachments()->create([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }
    }
}
