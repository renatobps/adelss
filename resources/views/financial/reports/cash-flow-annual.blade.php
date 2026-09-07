@extends('layouts.porto')

@section('title', 'Relatório: Resumo anual')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.reports.index') }}">Relatórios</a></li>
    <li><span>Resumo anual</span></li>
@endsection

@section('content')
<div class="row fr-page">
    @include('financial.reports.partials.sidebar')

    <div class="col-12 col-lg-9 fr-main">
        @include('financial.reports.partials.year-filters', [
            'action' => route('financial.reports.cash-flow.annual'),
        ])

        <div class="card fr-card mb-4">
            <div class="card-body">
                @include('financial.reports.partials.report-head', [
                    'title' => 'Fluxo de caixa — Resumo anual',
                    'subtitle' => 'Ano: '.$year.' · entrada, saída e saldo de cada mês',
                ])

                @include('financial.reports.partials.yearly-cashflow-chart', [
                    'chartId' => 'annualCashFlowChart',
                    'labels' => collect($byMonth)->pluck('month_name')->all(),
                    'entradas' => collect($byMonth)->pluck('entrada')->all(),
                    'saidas' => collect($byMonth)->pluck('saida')->all(),
                    'saldos' => collect($byMonth)->pluck('saldo_acumulado')->all(),
                    'emptyMessage' => 'Sem movimentação paga em '.$year,
                ])

                <div class="table-responsive mt-4">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Mês</th>
                                <th class="text-end">Entrada</th>
                                <th class="text-end">Saída</th>
                                <th class="text-end">Saldo do mês</th>
                                <th class="text-end">Saldo acumulado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byMonth as $month)
                                <tr>
                                    <td><strong>{{ $month['month_name'] }}</strong></td>
                                    <td class="text-end text-success">R$ {{ number_format($month['entrada'], 2, ',', '.') }}</td>
                                    <td class="text-end text-danger">R$ {{ number_format($month['saida'], 2, ',', '.') }}</td>
                                    <td class="text-end fw-semibold {{ $month['saldo'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        R$ {{ number_format($month['saldo'], 2, ',', '.') }}
                                    </td>
                                    <td class="text-end fw-semibold {{ $month['saldo_acumulado'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        R$ {{ number_format($month['saldo_acumulado'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-secondary">
                                <th>Ano {{ $year }}</th>
                                <th class="text-end text-success">R$ {{ number_format($totalEntrada, 2, ',', '.') }}</th>
                                <th class="text-end text-danger">R$ {{ number_format($totalSaida, 2, ',', '.') }}</th>
                                <th class="text-end {{ ($totalEntrada - $totalSaida) >= 0 ? 'text-success' : 'text-danger' }}">
                                    R$ {{ number_format($totalEntrada - $totalSaida, 2, ',', '.') }}
                                </th>
                                <th class="text-end {{ ($totalEntrada - $totalSaida) >= 0 ? 'text-success' : 'text-danger' }}">
                                    R$ {{ number_format($totalEntrada - $totalSaida, 2, ',', '.') }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
