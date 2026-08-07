<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignInstallment;
use App\Models\Department;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::query()
            ->with('department')
            ->withCount('sponsors')
            ->orderByDesc('created_at')
            ->get();

        return view('financial.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();

        return view('financial.campaigns.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCampaign($request);
        $data['created_by'] = Auth::id();

        $campaign = Campaign::create($data);

        return redirect()
            ->route('financial.campaigns.show', $campaign)
            ->with('success', 'Campanha criada com sucesso.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load([
            'department',
            'sponsors' => fn ($q) => $q->orderBy('name'),
            'sponsors.installments',
        ]);

        $metrics = [
            'goal' => (float) ($campaign->goal_amount ?? 0),
            'raised' => $campaign->totalRaised(),
            'pending' => $campaign->totalPending(),
            'overdue' => $campaign->totalOverdue(),
            'progress' => $campaign->progressPercentage(),
            'sponsors' => $campaign->sponsors->count(),
        ];

        return view('financial.campaigns.show', compact('campaign', 'metrics'));
    }

    public function edit(Campaign $campaign)
    {
        $departments = Department::orderBy('name')->get();

        return view('financial.campaigns.edit', compact('campaign', 'departments'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $this->validateCampaign($request);

        // Valor/quantidade de parcelas só valem para novos patrocinadores;
        // parcelas já geradas não são alteradas retroativamente.
        $campaign->update($data);

        return redirect()
            ->route('financial.campaigns.show', $campaign)
            ->with('success', 'Campanha atualizada com sucesso.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()
            ->route('financial.campaigns.index')
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

        $pdf = Pdf::loadView('financial.campaigns.pdf.carne', [
            'campaign' => $campaign,
            'sponsors' => $campaign->sponsors,
            'logoPath' => public_path('img/img/LOG SS AZUL.png'),
        ])->setPaper('a4');

        return $pdf->download('carnes-' . \Illuminate\Support\Str::slug($campaign->name) . '.pdf');
    }

    /**
     * Prestação de contas — dados gerados exclusivamente das tabelas de campanha.
     */
    public function report(Request $request, Campaign $campaign)
    {
        $data = $this->buildReportData($request, $campaign);

        return view('financial.campaigns.report', $data);
    }

    public function reportPdf(Request $request, Campaign $campaign)
    {
        $data = $this->buildReportData($request, $campaign);
        $data['logoPath'] = public_path('img/img/LOG SS AZUL.png');

        $pdf = Pdf::loadView('financial.campaigns.pdf.report', $data)->setPaper('a4');

        return $pdf->download('prestacao-contas-' . \Illuminate\Support\Str::slug($campaign->name) . '.pdf');
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
            fputcsv($out, ['Meta', 'Arrecadado', 'A receber', 'Em atraso', '% atingido', 'Patrocinadores'], ';');
            fputcsv($out, [
                number_format($data['metrics']['goal'], 2, ',', '.'),
                number_format($data['metrics']['raised'], 2, ',', '.'),
                number_format($data['metrics']['pending'], 2, ',', '.'),
                number_format($data['metrics']['overdue'], 2, ',', '.'),
                number_format($data['metrics']['progress'], 1, ',', '.') . '%',
                $data['metrics']['sponsors'],
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

            return [
                'name' => $sponsor->name,
                'phone' => $sponsor->phone ?: '—',
                'committed' => $committed,
                'paid' => $paid,
                'pending' => max(0, $committed - $paid),
                'situacao' => $situacaoLabels[$sponsor->situacao()] ?? $sponsor->situacao(),
            ];
        })->sortBy('name')->values();

        $metrics = [
            'goal' => (float) ($campaign->goal_amount ?? 0),
            'raised' => $campaign->totalRaised(),
            'pending' => $campaign->totalPending(),
            'overdue' => $campaign->totalOverdue(),
            'progress' => $campaign->progressPercentage(),
            'sponsors' => $campaign->sponsors->count(),
        ];

        return compact('campaign', 'metrics', 'byMethod', 'sponsorRows', 'payments', 'from', 'to');
    }

    private function validateCampaign(Request $request): array
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
                'status' => 'required|in:ativa,encerrada,cancelada',
            ],
            [
                'name.required' => 'Informe o nome da campanha.',
                'installment_amount.required' => 'Informe o valor da parcela.',
                'installment_amount.min' => 'O valor da parcela deve ser maior que zero.',
                'installments_count.required' => 'Informe a quantidade de parcelas.',
                'installments_count.min' => 'A campanha deve ter pelo menos 1 parcela.',
                'end_date.after_or_equal' => 'A data final deve ser posterior à data inicial.',
            ]
        );
    }
}
