<?php

namespace App\Http\Controllers\Eventos;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignInstallment;
use App\Models\CampaignReminderLog;
use App\Models\CampaignSponsor;
use App\Models\Department;
use App\Support\ExpenseReceiptPdfImages;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::query()
            ->with('department')
            ->withCount('sponsors')
            ->withSum('expenses', 'amount')
            ->orderByDesc('created_at')
            ->get();

        return view('eventos.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();

        return view('eventos.campaigns.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCampaign($request);
        $data['created_by'] = Auth::id();

        $campaign = Campaign::create($data);

        return redirect()
            ->route('eventos.campaigns.show', $campaign)
            ->with('success', 'Campanha criada com sucesso.');
    }

    /**
     * Ordenações da listagem de patrocinadores.
     * O padrão prioriza quem precisa ser cobrado, não a ordem alfabética.
     */
    private const SPONSOR_SORTS = [
        'atraso' => 'Mais em atraso',
        'nome' => 'Nome (A-Z)',
        'maior_pago' => 'Maior valor pago',
        'menor_pago' => 'Menor valor pago',
        'recentes' => 'Adicionado recentemente',
    ];

    private const SPONSOR_PER_PAGE = [20, 50, 100];

    public function show(Request $request, Campaign $campaign)
    {
        $campaign->load('department');

        $filters = $this->sponsorFilters($request);

        // As parcelas não são carregadas aqui: a listagem usa apenas os
        // agregados de `withSummary()` e o accordion busca as parcelas por AJAX.
        $base = CampaignSponsor::query()
            ->where('campaign_id', $campaign->id)
            ->search($filters['q']);

        $counts = ['todos' => (clone $base)->count()];
        foreach (array_keys(CampaignSponsor::SITUACOES) as $situacao) {
            $counts[$situacao] = (clone $base)->situacao($situacao)->count();
        }

        // Quem realmente receberia a cobrança em massa (o restante não tem WhatsApp).
        $chargeableCount = (clone $base)->situacao('em_atraso')
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->count();

        $sponsors = $this->applySponsorSort(
            (clone $base)->withSummary()->situacao($filters['situacao']),
            $filters['sort']
        )->paginate($filters['per_page'])->withQueryString();

        $metrics = [
            'goal' => (float) ($campaign->goal_amount ?? 0),
            // Tudo que os patrocinadores já se comprometeram a doar (pago + a receber).
            'expected' => $campaign->totalExpected(),
            'raised' => $campaign->totalRaised(),
            'pending' => $campaign->totalPending(),
            'overdue' => $campaign->totalOverdue(),
            'progress' => $campaign->progressPercentage(),
            'sponsors' => $campaign->sponsors()->count(),
            'expenses' => $campaign->totalExpenses(),
        ];
        $metrics['balance'] = $metrics['raised'] - $metrics['expenses'];

        return view('eventos.campaigns.show', [
            'campaign' => $campaign,
            'metrics' => $metrics,
            'sponsors' => $sponsors,
            'reminderSettings' => $campaign->reminderSettings(),
            'reminderStats' => $this->reminderStats($sponsors->getCollection()),
            'counts' => $counts,
            'chargeableCount' => $chargeableCount,
            'filters' => $filters,
            'sortOptions' => self::SPONSOR_SORTS,
            'perPageOptions' => self::SPONSOR_PER_PAGE,
        ]);
    }

    /**
     * @return array{q:string,situacao:string,sort:string,view:string,per_page:int}
     */
    private function sponsorFilters(Request $request): array
    {
        $text = fn (string $key) => is_string($v = $request->query($key)) ? $v : '';

        $situacao = $text('situacao');
        $sort = $text('sort');
        $perPage = (int) $text('per_page');

        return [
            'q' => trim($text('q')),
            'situacao' => array_key_exists($situacao, CampaignSponsor::SITUACOES) ? $situacao : 'todos',
            'sort' => array_key_exists($sort, self::SPONSOR_SORTS) ? $sort : 'atraso',
            'view' => $text('view') === 'tabela' ? 'tabela' : 'lista',
            'per_page' => in_array($perPage, self::SPONSOR_PER_PAGE, true) ? $perPage : 20,
        ];
    }

    /**
     * Lembretes já enviados por patrocinador da página: total, data do último
     * e quantos foram para a parcela mais antiga em atraso (que é o que define
     * o limite). Uma consulta agregada, em vez de duas por linha.
     *
     * @return array<int, array{total:int,last:?string,for_oldest:int}>
     */
    private function reminderStats(\Illuminate\Support\Collection $sponsors): array
    {
        if ($sponsors->isEmpty()) {
            return [];
        }

        $rows = CampaignReminderLog::query()
            ->whereIn('campaign_sponsor_id', $sponsors->pluck('id'))
            ->where('status', CampaignReminderLog::STATUS_ENVIADO)
            ->where('type', CampaignReminderLog::TYPE_ATRASO)
            ->selectRaw('campaign_sponsor_id, campaign_installment_id, COUNT(*) as total, MAX(sent_at) as last_sent')
            ->groupBy('campaign_sponsor_id', 'campaign_installment_id')
            ->get();

        $stats = [];
        foreach ($sponsors as $sponsor) {
            $own = $rows->where('campaign_sponsor_id', $sponsor->id);
            $oldest = $sponsor->getAttribute('oldest_overdue_id');

            $stats[$sponsor->id] = [
                'total' => (int) $own->sum('total'),
                'last' => $own->max('last_sent'),
                'for_oldest' => $oldest
                    ? (int) $own->where('campaign_installment_id', $oldest)->sum('total')
                    : 0,
            ];
        }

        return $stats;
    }

    private function applySponsorSort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'nome' => $query->orderBy('name'),
            'maior_pago' => $query->orderByDesc('paid_amount')->orderBy('name'),
            'menor_pago' => $query->orderBy('paid_amount')->orderBy('name'),
            'recentes' => $query->orderByDesc('created_at')->orderBy('name'),
            default => $query->orderByDesc('overdue_amount')->orderByDesc('overdue_count')->orderBy('name'),
        };
    }

    public function edit(Campaign $campaign)
    {
        $departments = Department::orderBy('name')->get();

        return view('eventos.campaigns.edit', compact('campaign', 'departments'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $this->validateCampaign($request);

        // Alterar a data ou a mensagem programada libera um novo envio.
        $newDate = $data['reminder_send_date'] ?? null;
        $currentDate = $campaign->reminder_send_date?->format('Y-m-d');
        if ($newDate !== $currentDate || ($data['reminder_message'] ?? null) !== $campaign->reminder_message) {
            $data['reminder_sent_at'] = null;
        }

        // Valor/quantidade de parcelas só valem para novos patrocinadores;
        // parcelas já geradas não são alteradas retroativamente.
        $campaign->update($data);

        return redirect()
            ->route('eventos.campaigns.show', $campaign)
            ->with('success', 'Campanha atualizada com sucesso.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()
            ->route('eventos.campaigns.index')
            ->with('success', 'Campanha excluída com sucesso.');
    }

    /**
     * Carnês de todos os patrocinadores em um único PDF (impressão em lote).
     */
    public function carnesLote(Campaign $campaign)
    {
        $campaign->load([
            'department',
            'sponsors' => fn ($q) => $q->orderBy('name'),
            'sponsors.installments',
        ]);

        if ($campaign->sponsors->isEmpty()) {
            return back()->with('error', 'A campanha ainda não possui patrocinadores.');
        }

        $pdf = Pdf::loadView('eventos.campaigns.pdf.carne', [
            'campaign' => $campaign,
            'groups' => $campaign->sponsors
                ->map(fn ($sponsor) => ['sponsor' => $sponsor, 'installments' => $sponsor->installments])
                ->values()
                ->all(),
        ])->setPaper('a4');

        return $pdf->download('carnes-' . \Illuminate\Support\Str::slug($campaign->name) . '.pdf');
    }

    /**
     * Prestação de contas — dados gerados exclusivamente das tabelas de campanha.
     */
    public function report(Request $request, Campaign $campaign)
    {
        $data = $this->buildReportData($request, $campaign);

        return view('eventos.campaigns.report', $data);
    }

    public function reportPdf(Request $request, Campaign $campaign)
    {
        $data = $this->buildReportData($request, $campaign);
        $data['logoPath'] = public_path('img/img/LOG SS AZUL.png');
        // modo=simplificado: tabela de patrocinadores só com nome e valor pago.
        $data['simplified'] = $request->query('modo') === 'simplificado';
        // Comprovantes dos gastos do período, anexados nas últimas folhas.
        $data['receipts'] = ExpenseReceiptPdfImages::build($data['expenses']);

        $pdf = Pdf::loadView('eventos.campaigns.pdf.report', $data)->setPaper('a4');

        $suffix = $data['simplified'] ? '-simplificado' : '';

        return $pdf->download('prestacao-contas-' . \Illuminate\Support\Str::slug($campaign->name) . $suffix . '.pdf');
    }

    public function reportExcel(Request $request, Campaign $campaign)
    {
        $data = $this->buildReportData($request, $campaign);
        $filename = 'prestacao-contas-' . \Illuminate\Support\Str::slug($campaign->name) . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($data, $campaign) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, ['Prestação de Contas — ' . $campaign->name], ';');
            fputcsv($out, [], ';');

            fputcsv($out, ['RESUMO GERAL'], ';');
            fputcsv($out, ['Meta', 'Arrecadado', 'A receber', 'Em atraso', '% atingido', 'Patrocinadores', 'Gastos', 'Saldo'], ';');
            fputcsv($out, [
                number_format($data['metrics']['goal'], 2, ',', '.'),
                number_format($data['metrics']['raised'], 2, ',', '.'),
                number_format($data['metrics']['pending'], 2, ',', '.'),
                number_format($data['metrics']['overdue'], 2, ',', '.'),
                number_format($data['metrics']['progress'], 1, ',', '.') . '%',
                $data['metrics']['sponsors'],
                number_format($data['metrics']['expenses'], 2, ',', '.'),
                number_format($data['metrics']['balance'], 2, ',', '.'),
            ], ';');
            fputcsv($out, [], ';');

            fputcsv($out, ['ARRECADAÇÃO POR FORMA DE PAGAMENTO'], ';');
            fputcsv($out, ['Forma de pagamento', 'Valor'], ';');
            foreach ($data['byMethod'] as $method => $total) {
                fputcsv($out, [
                    CampaignInstallment::PAYMENT_METHODS[$method] ?? ($method ?: 'Não informado'),
                    number_format($total, 2, ',', '.'),
                ], ';');
            }
            fputcsv($out, [], ';');

            fputcsv($out, ['PATROCINADORES'], ';');
            fputcsv($out, ['Nome', 'Telefone', 'Comprometido', 'Pago', 'Pendente', 'Situação'], ';');
            foreach ($data['sponsorRows'] as $row) {
                fputcsv($out, [
                    $row['name'],
                    $row['phone'],
                    number_format($row['committed'], 2, ',', '.'),
                    number_format($row['paid'], 2, ',', '.'),
                    number_format($row['pending'], 2, ',', '.'),
                    $row['situacao'],
                ], ';');
            }
            fputcsv($out, [], ';');

            fputcsv($out, ['EXTRATO DE RECEBIMENTOS'], ';');
            fputcsv($out, ['Recibo', 'Data', 'Patrocinador', 'Parcela', 'Valor', 'Forma de pagamento'], ';');
            foreach ($data['payments'] as $installment) {
                fputcsv($out, [
                    $installment->receipt_number,
                    $installment->paid_at?->format('d/m/Y H:i'),
                    $installment->sponsor->name,
                    $installment->installment_number,
                    number_format((float) $installment->amount, 2, ',', '.'),
                    $installment->paymentMethodLabel(),
                ], ';');
            }
            fputcsv($out, [], ';');

            fputcsv($out, ['GASTOS DO EVENTO'], ';');
            fputcsv($out, ['Data', 'Descrição', 'Categoria', 'Fornecedor', 'Forma de pagamento', 'Comprovantes', 'Valor'], ';');
            foreach ($data['expenses'] as $expense) {
                fputcsv($out, [
                    $expense->expense_date?->format('d/m/Y'),
                    $expense->description,
                    $expense->categoryLabel(),
                    $expense->supplier ?: '',
                    $expense->paymentMethodLabel(),
                    $expense->attachments->count(),
                    number_format((float) $expense->amount, 2, ',', '.'),
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function buildReportData(Request $request, Campaign $campaign): array
    {
        $campaign->load(['department', 'sponsors.installments']);

        $from = $request->date('from');
        $to = $request->date('to');

        $paymentsQuery = CampaignInstallment::query()
            ->whereHas('sponsor', fn ($q) => $q->where('campaign_id', $campaign->id))
            ->where('status', CampaignInstallment::STATUS_PAGO)
            ->with('sponsor')
            ->orderBy('paid_at');

        if ($from) {
            $paymentsQuery->where('paid_at', '>=', $from->startOfDay());
        }
        if ($to) {
            $paymentsQuery->where('paid_at', '<=', $to->endOfDay());
        }

        $payments = $paymentsQuery->get();

        $byMethod = $payments
            ->groupBy(fn ($i) => $i->payment_method ?: 'outro')
            ->map(fn ($group) => (float) $group->sum('amount'))
            ->sortDesc();

        $situacaoLabels = ['quitado' => 'Quitado', 'em_atraso' => 'Em atraso', 'em_dia' => 'Em dia'];
        $sponsorRows = $campaign->sponsors->map(function ($sponsor) use ($situacaoLabels) {
            $committed = $sponsor->totalCommitted();
            $paid = $sponsor->totalPaid();
            $situacao = $sponsor->situacao();
            $installments = $sponsor->installments->where('status', '!=', CampaignInstallment::STATUS_CANCELADO);

            return [
                'name' => $sponsor->name,
                'phone' => $sponsor->phone ?: '—',
                'committed' => $committed,
                'paid' => $paid,
                'pending' => max(0, $committed - $paid),
                'situacao' => $situacaoLabels[$situacao] ?? $situacao,
                'situacao_key' => $situacao,
                'paid_count' => $installments->where('status', CampaignInstallment::STATUS_PAGO)->count(),
                'total_count' => $installments->count(),
                'progress' => $committed > 0 ? round(min(100, $paid / $committed * 100)) : 0,
            ];
        })->sortBy('name')->values();

        // Quantos patrocinadores em cada situação (para os resumos/legendas).
        $statusCounts = [
            'quitado' => $sponsorRows->where('situacao_key', 'quitado')->count(),
            'em_dia' => $sponsorRows->where('situacao_key', 'em_dia')->count(),
            'em_atraso' => $sponsorRows->where('situacao_key', 'em_atraso')->count(),
        ];

        // Gastos do evento no mesmo período (pela data do gasto).
        $expensesQuery = $campaign->expenses()->with('attachments')->orderBy('expense_date')->orderBy('id');
        if ($from) {
            $expensesQuery->whereDate('expense_date', '>=', $from->toDateString());
        }
        if ($to) {
            $expensesQuery->whereDate('expense_date', '<=', $to->toDateString());
        }
        $expenses = $expensesQuery->get();

        $expensesByCategory = $expenses
            ->groupBy('category')
            ->map(fn ($group) => (float) $group->sum('amount'))
            ->sortDesc();

        $metrics = [
            'goal' => (float) ($campaign->goal_amount ?? 0),
            'raised' => $campaign->totalRaised(),
            'pending' => $campaign->totalPending(),
            'overdue' => $campaign->totalOverdue(),
            'progress' => $campaign->progressPercentage(),
            'sponsors' => $campaign->sponsors->count(),
            'expenses' => $campaign->totalExpenses(),
        ];
        $metrics['balance'] = $metrics['raised'] - $metrics['expenses'];

        return compact('campaign', 'metrics', 'byMethod', 'sponsorRows', 'statusCounts', 'payments', 'expenses', 'expensesByCategory', 'from', 'to');
    }

    private function validateCampaign(Request $request): array
    {
        $data = $this->doValidateCampaign($request);

        // Checkbox desmarcado envia o campo desabilitado (ausente): limpa a cor
        // personalizada para voltar a herdar a cor do departamento/padrão.
        $data['accent_color'] = $data['accent_color'] ?? null;

        // Campos limpos no formulário devem apagar o valor salvo.
        $data['reminder_message'] = $data['reminder_message'] ?? null;
        $data['reminder_send_date'] = $data['reminder_send_date'] ?? null;

        return $data;
    }

    private function doValidateCampaign(Request $request): array
    {
        return $request->validate(
            [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'department_id' => 'nullable|exists:departments,id',
                'goal_amount' => 'nullable|numeric|min:0',
                'installment_amount' => 'required|numeric|min:0.01',
                'installments_count' => 'required|integer|min:1|max:120',
                'first_due_date' => 'nullable|date',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'receipt_message' => 'nullable|string|max:1000',
                'accent_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
                'pix_key' => 'nullable|string|max:255',
                'pix_recipient' => 'nullable|string|max:255|required_with:pix_key',
                'reminder_message' => 'nullable|string|max:1000|required_with:reminder_send_date',
                'reminder_send_date' => 'nullable|date|required_with:reminder_message',
                'status' => 'required|in:ativa,encerrada,cancelada',
            ],
            [
                'name.required' => 'Informe o nome da campanha.',
                'pix_recipient.required_with' => 'Informe o nome do recebedor da chave PIX.',
                'reminder_message.required_with' => 'Informe a mensagem a enviar na data programada.',
                'reminder_send_date.required_with' => 'Informe a data de envio da mensagem programada.',
                'installment_amount.required' => 'Informe o valor da parcela.',
                'installment_amount.min' => 'O valor da parcela deve ser maior que zero.',
                'installments_count.required' => 'Informe a quantidade de parcelas.',
                'installments_count.min' => 'A campanha deve ter pelo menos 1 parcela.',
                'end_date.after_or_equal' => 'A data final deve ser posterior à data inicial.',
            ]
        );
    }
}
