<?php

namespace App\Services\Api;

use App\Models\Campaign;
use App\Models\FinancialAccount;
use App\Models\FinancialAutomation;
use App\Models\FinancialCategory;
use App\Models\FinancialContact;
use App\Models\FinancialCostCenter;
use App\Models\FinancialFixedExpense;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use App\Models\FinancialTransfer;
use App\Models\User;
use App\Services\Financial\PdfSignatureService;
use App\Services\Financial\PrebendaReceiptService;
use App\Support\FinancialReceiptLogo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FinancialModuleService
{
    /**
     * @return array<string, mixed>
     */
    public function capabilities(User $user): array
    {
        $transactions = $user->can('viewAny', FinancialTransaction::class);
        $despesas = $user->is_admin
            || $user->hasPermission('financial.despesas.view')
            || $user->hasPermission('financial.despesas.manage');
        $campaigns = $user->is_admin
            || $user->hasPermission('eventos.campanhas.view')
            || $user->hasPermission('eventos.campanhas.manage');

        return [
            'can_view_summary' => $user->can('financial.view-summary'),
            'can_view_transactions' => $transactions,
            'can_create_receita' => $user->can('createReceita', FinancialTransaction::class),
            'can_create_despesa' => $user->can('createDespesa', FinancialTransaction::class),
            'can_view_receipts' => $transactions,
            'can_view_correction' => $transactions,
            'can_view_fixed_expenses' => $user->can('financial.view-fixed-expenses'),
            'can_manage_fixed_expenses' => $user->can('financial.manage-fixed-expenses'),
            'can_view_accounts' => $user->can('viewAny', FinancialAccount::class),
            'can_manage_accounts' => $user->can('create', FinancialAccount::class),
            'can_view_categories' => $user->can('viewAny', FinancialCategory::class),
            'can_manage_categories' => $user->can('create', FinancialCategory::class),
            'can_view_cost_centers' => $user->can('viewAny', FinancialCostCenter::class),
            'can_manage_cost_centers' => $user->can('create', FinancialCostCenter::class),
            'can_view_contacts' => $user->can('viewAny', FinancialContact::class),
            'can_manage_contacts' => $user->can('create', FinancialContact::class),
            'can_view_campaigns' => $campaigns,
            'can_manage_campaigns' => $user->is_admin || $user->hasPermission('eventos.campanhas.manage'),
            'can_view_reports' => $user->can('financial.view-reports'),
            'can_view_automations' => $user->can('financial.view-automations'),
            'can_manage_automations' => $user->can('financial.manage-automations'),
            'can_view_despesas' => $despesas,
        ];
    }

    /**
     * @return list<array{key: string, label: string, icon: string}>
     */
    public function nav(array $caps): array
    {
        $items = [];
        $add = function (bool $ok, string $key, string $label, string $icon) use (&$items) {
            if ($ok) {
                $items[] = compact('key', 'label', 'icon');
            }
        };

        $add($caps['can_view_summary'], 'resumo', 'Resumo', 'chart');
        $add($caps['can_view_transactions'], 'transacoes', 'Transações', 'dollar');
        $add($caps['can_view_receipts'], 'recibos', 'Recibos', 'receipt');
        $add($caps['can_view_correction'], 'correcao', 'Correção', 'wrench');
        $add($caps['can_view_fixed_expenses'], 'despesas-fixas', 'Despesas Fixas', 'refresh');
        $add($caps['can_view_accounts'], 'contas', 'Contas e Caixas', 'wallet');
        $add($caps['can_view_categories'], 'categorias', 'Categorias', 'tag');
        $add($caps['can_view_cost_centers'], 'centros-custo', 'Centros de Custo', 'target');
        $add($caps['can_view_contacts'], 'fornecedores', 'Fornecedores', 'store');
        $add($caps['can_view_campaigns'], 'campanhas', 'Campanhas', 'favorite');
        $add($caps['can_view_reports'], 'relatorios', 'Relatórios', 'file');
        $add($caps['can_view_automations'], 'automacoes', 'Automações', 'spark');

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(string $period = '1month'): array
    {
        [$start, $end, $label] = $this->periodBounds($period);

        $in = (float) FinancialTransaction::receitas()->where('is_paid', true)->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $out = (float) FinancialTransaction::despesas()->where('is_paid', true)->whereBetween('transaction_date', [$start, $end])->sum('amount');

        $accounts = FinancialAccount::query()->where('is_active', true)->orderBy('name')->get()
            ->map(fn (FinancialAccount $account) => $this->accountPayload($account))
            ->all();

        return [
            'period' => [
                'key' => $period,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => $label,
            ],
            'in_total' => round($in, 2),
            'out_total' => round($out, 2),
            'result' => round($in - $out, 2),
            'accounts' => $accounts,
            'periods' => [
                ['key' => 'today', 'label' => 'Hoje'],
                ['key' => '7days', 'label' => 'Últimos 7 dias'],
                ['key' => '1month', 'label' => 'Mês atual'],
                ['key' => 'last_month', 'label' => 'Mês anterior'],
                ['key' => '3months', 'label' => 'Últimos 3 meses'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'accounts' => FinancialAccount::query()->where('is_active', true)->orderBy('name')->get()
                ->map(fn (FinancialAccount $row) => $this->accountPayload($row))->all(),
            'categories' => FinancialCategory::query()->orderBy('name')->get()
                ->map(fn (FinancialCategory $row) => [
                    'id' => $row->id,
                    'name' => $row->name,
                    'type' => $row->type,
                ])->all(),
            'contacts' => FinancialContact::query()->orderBy('name')->get(['id', 'name', 'type'])
                ->map(fn (FinancialContact $row) => [
                    'id' => $row->id,
                    'name' => $row->name,
                    'type' => $row->type,
                ])->all(),
            'cost_centers' => FinancialCostCenter::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (FinancialCostCenter $row) => [
                    'id' => $row->id,
                    'name' => $row->name,
                ])->all(),
            'account_types' => collect(FinancialAccount::TYPES)->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transactionPayload(FinancialTransaction $row): array
    {
        $row->loadMissing(['category:id,name,type', 'account:id,name,type', 'member:id,name', 'contact:id,name', 'attachments']);

        return [
            'id' => $row->id,
            'type' => $row->type,
            'type_label' => $row->type === 'receita' ? 'Receita' : 'Despesa',
            'date' => $row->transaction_date?->toDateString(),
            'due_date' => $row->due_date?->toDateString(),
            'description' => FinancialTransaction::displayDescription($row->description),
            'amount' => round((float) $row->amount, 2),
            'paid' => (bool) $row->is_paid,
            'status' => $row->status,
            'status_label' => $this->statusLabel($row),
            'category_id' => $row->category_id,
            'category' => $row->category?->name,
            'account_id' => $row->account_id,
            'account' => $row->account?->name,
            'member_id' => $row->member_id,
            'member' => $row->member?->name,
            'contact_id' => $row->contact_id,
            'contact' => $row->contact?->name,
            'received_from_other' => $row->received_from_other,
            'cost_center_id' => $row->cost_center_id,
            'notes' => $row->notes,
            'payment_type' => $row->payment_type,
            'party' => $row->member?->name ?? $row->contact?->name ?? $row->received_from_other,
            'attachments_count' => $row->attachments->count(),
            'attachments' => $row->attachments->map(fn (FinancialTransactionAttachment $file) => $this->attachmentPayload($file))->all(),
            'can_view_receipt' => true,
            'receipt_api_path' => '/financial/transactions/'.$row->id.'/receipt',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attachmentPayload(FinancialTransactionAttachment $file): array
    {
        $exists = $file->file_path && Storage::disk('public')->exists($file->file_path);
        $generated = $file->isSystemGenerated();
        $host = rtrim((string) (request()?->getSchemeAndHttpHost() ?: config('app.url')), '/');

        return [
            'id' => $file->id,
            'transaction_id' => $file->transaction_id,
            'file_name' => $file->file_name,
            'file_type' => $file->file_type,
            'file_size' => (int) $file->file_size,
            'available' => $exists,
            'url' => $exists ? $host.'/storage/'.$file->file_path : null,
            'api_path' => '/financial/transactions/'.$file->transaction_id.'/attachments/'.$file->id,
            'generated' => $generated,
            'can_open' => $exists || $generated,
            'is_image' => str_starts_with((string) $file->file_type, 'image/'),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function periodBounds(string $period): array
    {
        $today = Carbon::today();

        return match ($period) {
            'today' => [$today->copy(), $today->copy(), 'Hoje'],
            '7days' => [$today->copy()->subDays(6), $today->copy(), 'Últimos 7 dias'],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
                'Mês anterior',
            ],
            '3months' => [
                $today->copy()->subMonths(2)->startOfMonth(),
                $today->copy()->endOfMonth(),
                'Últimos 3 meses',
            ],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth(), 'Mês atual'],
        };
    }

    public function streamReceiptPdf(FinancialTransaction $transaction): Response
    {
        $transaction->loadMissing(['member', 'contact', 'category']);
        $prebenda = app(PrebendaReceiptService::class);
        $signatures = app(PdfSignatureService::class);
        $signer = $prebenda->appliesTo($transaction)
            ? $prebenda->signer($transaction, asDataUri: true)
            : [
                'name' => $signatures->tesoureiroNome(),
                'signatureSrc' => $signatures->imageSrc(PdfSignatureService::ROLE_TESOUREIRO),
                'cpfRg' => '',
                'address' => '',
            ];

        $pdf = Pdf::loadView('financial.transactions.receipt-pdf', [
            'transaction' => $transaction,
            'fundoPath' => FinancialReceiptLogo::backgroundPathForPdf(),
            'signerName' => $signer['name'],
            'signerSignatureSrc' => $signer['signatureSrc'],
            'signerCpfRg' => $signer['cpfRg'],
            'signerAddress' => $signer['address'],
        ])->setPaper(FinancialReceiptLogo::pdfPaper());

        return $pdf->stream('recibo-'.$transaction->id.'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    public function accountPayload(FinancialAccount $account): array
    {
        $flow = $account->ledgerFlow();

        return [
            'id' => $account->id,
            'name' => $account->name,
            'label' => $account->paymentOptionLabel(),
            'type' => $account->type,
            'type_label' => $account->typeLabel(),
            'bank_name' => $account->bank_name,
            'color' => $account->color,
            'is_active' => (bool) $account->is_active,
            'initial_balance' => round((float) $account->initial_balance, 2),
            'inflow' => $flow['inflow'],
            'outflow' => $flow['outflow'],
            'balance' => round((float) $account->initial_balance + $flow['inflow'] - $flow['outflow'], 2),
            'description' => $account->description,
        ];
    }

    public function statusLabel(FinancialTransaction $row): string
    {
        return match ($row->status) {
            'recebido' => 'Recebido',
            'pago' => 'Pago',
            'a_receber' => 'A receber',
            'a_pagar' => 'A pagar',
            default => (string) $row->status,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function receipts(string $tab, string $start, string $end, int $limit = 40): array
    {
        $limit = min(80, max(1, $limit));

        if ($tab === 'pendentes') {
            $items = FinancialTransaction::query()
                ->despesas()
                ->where('is_paid', true)
                ->whereBetween('transaction_date', [$start, $end])
                ->whereDoesntHave('attachments')
                ->with(['category:id,name', 'contact:id,name', 'account:id,name'])
                ->orderByDesc('transaction_date')
                ->limit($limit)
                ->get()
                ->map(fn (FinancialTransaction $row) => $this->transactionPayload($row))
                ->all();

            return ['tab' => 'pendentes', 'items' => $items];
        }

        if ($tab === 'receitas') {
            $items = FinancialTransaction::query()
                ->receitas()
                ->where('is_paid', true)
                ->whereBetween('transaction_date', [$start, $end])
                ->with(['category:id,name', 'member:id,name', 'account:id,name'])
                ->orderByDesc('transaction_date')
                ->limit($limit)
                ->get()
                ->map(fn (FinancialTransaction $row) => $this->transactionPayload($row))
                ->all();

            return ['tab' => 'receitas', 'items' => $items];
        }

        $items = FinancialTransactionAttachment::query()
            ->whereHas('transaction', function ($query) use ($start, $end) {
                $query->whereBetween('transaction_date', [$start, $end]);
            })
            ->with(['transaction.category:id,name', 'transaction.member:id,name', 'transaction.contact:id,name'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (FinancialTransactionAttachment $row) {
                $tx = $row->transaction;

                return array_merge($this->attachmentPayload($row), [
                    'description' => $tx ? FinancialTransaction::displayDescription($tx->description) : $row->file_name,
                    'amount' => $tx ? round((float) $tx->amount, 2) : 0,
                    'date' => $tx?->transaction_date?->toDateString(),
                    'type' => $tx?->type,
                    'can_view_receipt' => true,
                    'receipt_api_path' => $tx ? '/financial/transactions/'.$tx->id.'/receipt' : null,
                ]);
            })
            ->all();

        return ['tab' => 'arquivos', 'items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    public function reports(string $start, string $end): array
    {
        $inByCategory = FinancialTransaction::query()
            ->receitas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$start, $end])
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->get();

        $outByCategory = FinancialTransaction::query()
            ->despesas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$start, $end])
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->get();

        $categories = FinancialCategory::query()->get(['id', 'name'])->keyBy('id');

        $map = function ($rows) use ($categories) {
            return $rows->map(fn ($row) => [
                'category_id' => $row->category_id,
                'category' => $categories[$row->category_id]->name ?? 'Sem categoria',
                'total' => round((float) $row->total, 2),
            ])->values()->all();
        };

        $in = (float) $inByCategory->sum('total');
        $out = (float) $outByCategory->sum('total');

        return [
            'start' => $start,
            'end' => $end,
            'in_total' => round($in, 2),
            'out_total' => round($out, 2),
            'result' => round($in - $out, 2),
            'receitas' => $map($inByCategory),
            'despesas' => $map($outByCategory),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function campaignPayload(Campaign $campaign): array
    {
        $campaign->loadMissing('department:id,name');
        $sponsors = $campaign->sponsors()->count();

        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'description' => $campaign->description,
            'status' => $campaign->status,
            'goal_amount' => round((float) ($campaign->goal_amount ?? 0), 2),
            'installment_amount' => round((float) $campaign->installment_amount, 2),
            'installments_count' => (int) $campaign->installments_count,
            'department' => $campaign->department?->name,
            'sponsors_count' => $sponsors,
            'start_date' => optional($campaign->start_date)?->toDateString(),
            'end_date' => optional($campaign->end_date)?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function automationPayload(FinancialAutomation $row): array
    {
        return [
            'id' => $row->id,
            'key' => $row->key,
            'name' => $row->name,
            'enabled' => (bool) $row->enabled,
            'settings' => $row->settings ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transferPayload(FinancialTransfer $row): array
    {
        $row->loadMissing(['fromAccount:id,name', 'toAccount:id,name']);

        return [
            'id' => $row->id,
            'from_account_id' => $row->from_account_id,
            'to_account_id' => $row->to_account_id,
            'from_account' => $row->fromAccount?->name,
            'to_account' => $row->toAccount?->name,
            'amount' => round((float) $row->amount, 2),
            'date' => $row->transfer_date?->toDateString(),
            'notes' => $row->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fixedExpensePayload(FinancialFixedExpense $row, ?FinancialTransaction $tx = null): array
    {
        $row->loadMissing(['category:id,name', 'account:id,name', 'contact:id,name']);

        return [
            'id' => $row->id,
            'description' => $row->description,
            'amount' => round((float) ($tx?->amount ?? $row->amount), 2),
            'due_day' => $row->due_day,
            'is_active' => (bool) $row->is_active,
            'amount_variable' => (bool) $row->amount_variable,
            'category' => $row->category?->name,
            'account' => $row->account?->name,
            'contact' => $row->contact?->name,
            'category_id' => $row->category_id,
            'account_id' => $row->account_id,
            'contact_id' => $row->contact_id,
            'cost_center_id' => $row->cost_center_id,
            'notes' => $row->notes,
            'generated' => (bool) $tx,
            'paid' => (bool) ($tx?->is_paid),
            'transaction_id' => $tx?->id,
        ];
    }
}
