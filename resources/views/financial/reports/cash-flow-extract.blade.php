@extends('layouts.porto')

@section('title', 'Relatório: Fluxo de Caixa - Extrato')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.reports.index') }}">Relatórios</a></li>
    <li><span>Extrato</span></li>
@endsection

@section('content')
<div class="row fr-page">
    @include('financial.reports.partials.sidebar')

    <div class="col-12 col-lg-9 fr-main">
        @include('financial.reports.partials.full-filters', [
            'action' => route('financial.reports.cash-flow.extract'),
        ])

        <div class="card fr-card mb-4">
            <div class="card-body">
                @include('financial.reports.partials.report-head', [
                    'title' => 'Fluxo de caixa — Extrato',
                    'subtitle' => \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' a ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y'),
                    'pdfUrl' => route('financial.reports.cash-flow.extract.pdf', request()->except(['page', 'per_page', 'search', 'sort_by', 'sort_order'])),
                    'pdfLabel' => 'Gerar PDF do fechamento',
                ])

                <!-- Gráfico -->
                <div class="mb-4">
                    @include('financial.partials.daily-chart', [
                        'chartId' => 'cashFlowChart',
                        'labels' => collect($chartData ?? [])->pluck('day')->all(),
                        'receitas' => collect($chartData ?? [])->pluck('receitas')->all(),
                        'despesas' => collect($chartData ?? [])->pluck('despesas')->all(),
                        'aReceber' => collect($chartData ?? [])->pluck('a_receber')->all(),
                        'aPagar' => collect($chartData ?? [])->pluck('a_pagar')->all(),
                        'area' => true,
                        'mobileNote' => 'Receitas e despesas agrupadas por período. Os previstos estão na tabela abaixo.',
                    ])
                </div>

                <!-- Tabela de Transações -->
                <div class="mb-4">
                <div class="fr-table-toolbar">
                    <strong>Transações: {{ $transactions->total() }}</strong>
                    <div class="fr-table-toolbar__actions">
                        <select class="form-select" style="width: auto;" onchange="this.form.submit()" form="filterForm" name="per_page">
                            <option value="50" @selected(request('per_page', 100) == 50)>50 por página</option>
                            <option value="100" @selected(request('per_page', 100) == 100)>100 por página</option>
                            <option value="200" @selected(request('per_page', 100) == 200)>200 por página</option>
                        </select>
                        <input type="search" class="form-control" placeholder="Pesquisar" name="search" form="filterForm" value="{{ request('search') }}" style="width: 200px;">
                        <button type="submit" form="filterForm" class="btn btn-outline-primary" aria-label="Pesquisar">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                </div>

                    @if($transactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <a href="{{ route('financial.reports.cash-flow.extract', array_merge(request()->all(), ['sort_by' => 'transaction_date', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                                Data
                                                @if(request('sort_by') == 'transaction_date')
                                                    <i class="bx bx-chevron-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="bx bx-sort-alt-2"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ route('financial.reports.cash-flow.extract', array_merge(request()->all(), ['sort_by' => 'description', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                                Descrição
                                                @if(request('sort_by') == 'description')
                                                    <i class="bx bx-chevron-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="bx bx-sort-alt-2"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ route('financial.reports.cash-flow.extract', array_merge(request()->all(), ['sort_by' => 'amount', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                                Total
                                                @if(request('sort_by') == 'amount')
                                                    <i class="bx bx-chevron-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="bx bx-sort-alt-2"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>Categoria</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                            <td>{{ $transaction->description }}</td>
                                            <td>
                                                <span class="{{ $transaction->type === 'despesa' ? 'text-danger' : 'text-primary' }}">
                                                    {{ $transaction->type === 'despesa' ? '-' : '' }}R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                                                </span>
                                                @if($transaction->is_paid)
                                                    <i class="bx bx-check-circle text-success ms-1"></i>
                                                @endif
                                            </td>
                                            <td>{{ $transaction->category ? $transaction->category->name : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <div class="mt-3">
                            {{ $transactions->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bx bx-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-2">Nenhuma transação encontrada para o período selecionado.</p>
                        </div>
                    @endif
                </div>

                <!-- Resumo -->
                <div class="card fr-summary" style="background-color: #f8f9fa; border: 1px solid #cfe8f6; border-top: 4px solid #0088CC;">
                    <div class="card-header bg-primary text-white">
                        <h6 class="card-title mb-0">Resumo</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td><strong>Saldo anterior em {{ \Carbon\Carbon::parse($startDate)->subDay()->format('d/m/Y') }}:</strong></td>
                                        <td class="text-end"><strong>R$ {{ number_format($previousBalance, 2, ',', '.') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Total de receitas no período:</td>
                                        <td class="text-end">R$ {{ number_format($totalReceitas, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total de despesas no período:</td>
                                        <td class="text-end text-danger">-R$ {{ number_format($totalDespesas, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="border-top pt-2">
                                            <strong>= R$ {{ number_format($previousBalance + $totalReceitas - $totalDespesas, 2, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>A receber:</td>
                                        <td class="text-end">R$ {{ number_format($aReceber, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>A pagar:</td>
                                        <td class="text-end text-danger">-R$ {{ number_format($aPagar, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="border-top pt-2">
                                            <strong>= R$ {{ number_format($aReceber - $aPagar, 2, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td>Transf. enviada:</td>
                                        <td class="text-end">R$ {{ number_format($transferenciasEnviadas, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Transf. recebida:</td>
                                        <td class="text-end">R$ {{ number_format($transferenciasRecebidas, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="border-top pt-2">
                                            <strong>= R$ {{ number_format($transferenciasRecebidas - $transferenciasEnviadas, 2, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="border-top pt-3">
                                            <h5 class="mb-0">
                                                <strong>Saldo final em {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}:</strong>
                                                <span class="float-end text-primary">R$ {{ number_format($saldoFinal, 2, ',', '.') }}</span>
                                            </h5>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top">
                            <small class="text-danger">
                                <i class="bx bx-info-circle me-1"></i>
                                O resultado apresentado é baseado nos filtros selecionados no topo da página.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- O gráfico é montado pelo partial financial.partials.daily-chart. --}}
@endsection

