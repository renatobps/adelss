@extends('layouts.porto')

@section('title', 'Prestação de Contas — ' . $campaign->name)

@section('page-title', 'Prestação de Contas')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.campaigns.index') }}">Campanhas</a></li>
    <li><a href="{{ route('financial.campaigns.show', $campaign) }}">{{ $campaign->name }}</a></li>
    <li><span>Prestação de Contas</span></li>
@endsection

@section('content')
@php
    use App\Models\CampaignInstallment;
@endphp

@include('financial.campaigns.partials.alerts')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0">{{ $campaign->name }}</h4>
        <small class="text-muted">Relatório gerado exclusivamente a partir dos dados da campanha — não inclui valores do módulo Financeiro.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('financial.campaigns.report.pdf', array_merge(['campaign' => $campaign->id], request()->only('from', 'to'))) }}"
           class="btn btn-outline-danger btn-sm"><i class="bx bxs-file-pdf me-1"></i>Exportar PDF</a>
        <a href="{{ route('financial.campaigns.report.excel', array_merge(['campaign' => $campaign->id], request()->only('from', 'to'))) }}"
           class="btn btn-outline-success btn-sm"><i class="bx bxs-file me-1"></i>Exportar Excel</a>
    </div>
</div>

<!-- Filtro por período -->
<section class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1">Pagamentos de</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">até</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-filter me-1"></i>Filtrar</button>
                @if(request('from') || request('to'))
                    <a href="{{ route('financial.campaigns.report', $campaign) }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
                @endif
            </div>
        </form>
    </div>
</section>

<!-- Resumo geral -->
<section class="card mb-3">
    <header class="card-header"><h5 class="mb-0"><i class="bx bx-bar-chart-alt-2 me-2"></i>Resumo Geral</h5></header>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-6 col-md-2 mb-2"><div class="text-muted small">Meta</div><strong>{{ $metrics['goal'] > 0 ? 'R$ ' . number_format($metrics['goal'], 2, ',', '.') : '—' }}</strong></div>
            <div class="col-6 col-md-2 mb-2"><div class="text-muted small">Arrecadado</div><strong class="text-success">R$ {{ number_format($metrics['raised'], 2, ',', '.') }}</strong></div>
            <div class="col-6 col-md-2 mb-2"><div class="text-muted small">A receber</div><strong class="text-warning">R$ {{ number_format($metrics['pending'], 2, ',', '.') }}</strong></div>
            <div class="col-6 col-md-2 mb-2"><div class="text-muted small">Em atraso</div><strong class="text-danger">R$ {{ number_format($metrics['overdue'], 2, ',', '.') }}</strong></div>
            <div class="col-6 col-md-2 mb-2"><div class="text-muted small">% atingido</div><strong>{{ number_format($metrics['progress'], 1, ',', '.') }}%</strong></div>
            <div class="col-6 col-md-2 mb-2"><div class="text-muted small">Patrocinadores</div><strong>{{ $metrics['sponsors'] }}</strong></div>
        </div>
    </div>
</section>

<div class="row">
    <!-- Por forma de pagamento -->
    <div class="col-lg-4 mb-3">
        <section class="card h-100">
            <header class="card-header"><h5 class="mb-0"><i class="bx bx-credit-card me-2"></i>Por Forma de Pagamento</h5></header>
            <div class="card-body p-0">
                @if($byMethod->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">Nenhum pagamento no período.</p>
                @else
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            @foreach($byMethod as $method => $total)
                                <tr>
                                    <td class="ps-3">{{ CampaignInstallment::PAYMENT_METHODS[$method] ?? ucfirst($method) }}</td>
                                    <td class="text-end pe-3"><strong>R$ {{ number_format($total, 2, ',', '.') }}</strong></td>
                                </tr>
                            @endforeach
                            <tr class="table-light">
                                <td class="ps-3"><strong>Total</strong></td>
                                <td class="text-end pe-3"><strong>R$ {{ number_format($byMethod->sum(), 2, ',', '.') }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            </div>
        </section>
    </div>

    <!-- Patrocinadores -->
    <div class="col-lg-8 mb-3">
        <section class="card h-100">
            <header class="card-header"><h5 class="mb-0"><i class="bx bx-user me-2"></i>Patrocinadores</h5></header>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Nome</th>
                                <th>Comprometido</th>
                                <th>Pago</th>
                                <th>Pendente</th>
                                <th class="pe-3">Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sponsorRows as $row)
                                <tr>
                                    <td class="ps-3">{{ $row['name'] }}</td>
                                    <td>R$ {{ number_format($row['committed'], 2, ',', '.') }}</td>
                                    <td class="text-success">R$ {{ number_format($row['paid'], 2, ',', '.') }}</td>
                                    <td>R$ {{ number_format($row['pending'], 2, ',', '.') }}</td>
                                    <td class="pe-3">
                                        @if($row['situacao'] === 'Quitado')
                                            <span class="badge bg-success">Quitado</span>
                                        @elseif($row['situacao'] === 'Em atraso')
                                            <span class="badge bg-danger">Em atraso</span>
                                        @else
                                            <span class="badge bg-info text-dark">Em dia</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Nenhum patrocinador.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Extrato de recebimentos -->
<section class="card">
    <header class="card-header">
        <h5 class="mb-0"><i class="bx bx-list-check me-2"></i>Extrato de Recebimentos</h5>
        <p class="card-subtitle mb-0">Todas as parcelas pagas em ordem cronológica — documento para prestação de contas à liderança.</p>
    </header>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Recibo</th>
                        <th>Data</th>
                        <th>Patrocinador</th>
                        <th>Parcela</th>
                        <th>Forma</th>
                        <th class="text-end pe-3">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $installment)
                        <tr>
                            <td class="ps-3">{{ $installment->receipt_number ?? '—' }}</td>
                            <td>{{ $installment->paid_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $installment->sponsor->name }}</td>
                            <td>{{ $installment->installment_number }}</td>
                            <td>{{ $installment->paymentMethodLabel() }}</td>
                            <td class="text-end pe-3">R$ {{ number_format((float) $installment->amount, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum recebimento no período.</td></tr>
                    @endforelse
                    @if($payments->isNotEmpty())
                        <tr class="table-light">
                            <td colspan="5" class="ps-3"><strong>Total do período</strong></td>
                            <td class="text-end pe-3"><strong>R$ {{ number_format((float) $payments->sum('amount'), 2, ',', '.') }}</strong></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
