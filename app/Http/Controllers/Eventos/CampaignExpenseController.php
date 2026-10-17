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
 * Gastos dos eventos: cada lançamento pertence a uma campanha e exige
 * ao menos um comprovante (nota fiscal, recibo, foto) anexado.
 */
class CampaignExpenseController extends Controller
{
    private const MAX_FILES = 5;
    private const MAX_FILE_KB = 10240; // 10MB

    /** Gastos de uma campanha específica (com formulário de cadastro). */
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
            'byCategory' => $this->byCategory($campaign),
            'categories' => CampaignExpense::CATEGORIES,
            'paymentMethods' => CampaignExpense::PAYMENT_METHODS,
        ]);
    }

    /** Visão consolidada de gastos de todos os eventos (menu "Gastos" do módulo). */
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
            ->withSum('expenses as expenses_total', 'amount')
            ->withCount('expenses')
            ->orderByDesc('created_at')
            ->get();

        return view('eventos.expenses.index', [
            'expenses' => $expenses,
            'campaigns' => $campaigns,
            'selectedCampaign' => $campaignId,
            'filters' => $filters,
            'grandTotal' => (float) CampaignExpense::query()->sum('amount'),
            'categories' => CampaignExpense::CATEGORIES,
        ]);
    }

    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $this->validateExpense($request, requireReceipt: true);

        DB::transaction(function () use ($campaign, $data, $request) {
            $expense = $campaign->expenses()->create([
                ...$this->expenseAttributes($data),
                'created_by' => Auth::id(),
            ]);

            $this->storeAttachments($expense, $request->file('attachments', []));
        });

        return redirect()
            ->route('eventos.campaigns.expenses.index', $campaign)
            ->with('success', 'Gasto registrado com comprovante.');
    }

    public function update(Request $request, CampaignExpense $expense): RedirectResponse
    {
        $expense->load('attachments');

        $removeIds = collect($request->input('remove_attachments', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        // O gasto não pode ficar sem comprovante: se o usuário remover todos
        // os existentes, precisa anexar ao menos um novo.
        $remaining = $expense->attachments->whereNotIn('id', $removeIds)->count();
        $data = $this->validateExpense($request, requireReceipt: $remaining === 0);

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
            ->with('success', 'Gasto atualizado.');
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
            ->with('success', 'Gasto excluído.');
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
     * @return array{q:string,category:string,from:?string,to:?string}
     */
    private function filters(Request $request): array
    {
        $text = fn (string $key) => is_string($v = $request->query($key)) ? trim($v) : '';
        $category = $text('category');

        return [
            'q' => $text('q'),
            'category' => array_key_exists($category, CampaignExpense::CATEGORIES) ? $category : '',
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
     * @return array{spent:float,raised:float,balance:float,count:int}
     */
    private function totals(Campaign $campaign): array
    {
        $spent = $campaign->totalExpenses();
        $raised = $campaign->totalRaised();

        return [
            'spent' => $spent,
            'raised' => $raised,
            'balance' => $raised - $spent,
            'count' => $campaign->expenses()->count(),
        ];
    }

    /**
     * @return array<string, float>  categoria => total
     */
    private function byCategory(Campaign $campaign): array
    {
        return $campaign->expenses()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    private function validateExpense(Request $request, bool $requireReceipt): array
    {
        return $request->validate(
            [
                'description' => 'required|string|max:255',
                'category' => 'required|in:' . implode(',', array_keys(CampaignExpense::CATEGORIES)),
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
                'description.required' => 'Informe a descrição do gasto.',
                'category.required' => 'Selecione a categoria do gasto.',
                'amount.required' => 'Informe o valor do gasto.',
                'amount.min' => 'O valor do gasto deve ser maior que zero.',
                'expense_date.required' => 'Informe a data do gasto.',
                'attachments.required' => 'Anexe ao menos um comprovante de despesa (nota, recibo ou foto).',
                'attachments.min' => 'Anexe ao menos um comprovante de despesa (nota, recibo ou foto).',
                'attachments.max' => 'Máximo de ' . self::MAX_FILES . ' comprovantes por gasto.',
                'attachments.*.mimes' => 'Comprovantes devem ser imagens (JPG, PNG, WEBP, HEIC) ou PDF.',
                'attachments.*.max' => 'Cada comprovante pode ter no máximo 10MB.',
            ]
        );
    }

    private function expenseAttributes(array $data): array
    {
        return [
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
