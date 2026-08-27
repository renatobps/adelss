@extends('layouts.porto')

@section('title', 'Relatório: Receitas - Resumo anual por categoria')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.reports.index') }}">Relatórios</a></li>
    <li><span>Receitas - Resumo anual por categoria</span></li>
@endsection

@section('content')
<div class="row fr-page">
    @include('financial.reports.partials.sidebar')

    <div class="col-12 col-lg-9 fr-main">
        @include('financial.reports.partials.year-filters', [
            'action' => route('financial.reports.revenues.annual-summary'),
        ])

        <div class="card fr-card mb-4">
            <div class="card-body">
                @include('financial.reports.partials.report-head', [
                    'title' => 'Receitas — Resumo anual por categoria',
                    'subtitle' => 'Ano: ' . $year,
                ])

                <!-- Gráfico Mensal -->
                <div class="mb-4">
                    @include('financial.partials.monthly-bars-chart', [
                        'chartId' => 'monthlyChart',
                        'labels' => collect($chartData ?? [])->pluck('month_name')->all(),
                        'values' => collect($chartData ?? [])->pluck('total')->all(),
                        'seriesName' => 'Receitas por mês',
                        'color' => '#0088CC',
                        'emptyMessage' => 'Sem receitas registradas em ' . $year,
                    ])
                </div>

                <!-- Resumo por Categoria -->
                <div class="mb-5">
                    <h5 class="mb-3">Resumo por Categoria</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Categoria</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Quantidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byCategory as $item)
                                    <tr>
                                        <td>{{ $item['category_name'] }}</td>
                                        <td class="text-end text-primary">
                                            <strong>R$ {{ number_format($item['total'], 2, ',', '.') }}</strong>
                                        </td>
                                        <td class="text-end">{{ $item['count'] }}</td>
                                    </tr>
                                @endforeach
                                <tr class="table-secondary">
                                    <td><strong>Total Geral</strong></td>
                                    <td class="text-end"><strong class="text-primary">R$ {{ number_format($totalGeral, 2, ',', '.') }}</strong></td>
                                    <td class="text-end"><strong>{{ collect($byCategory)->sum('count') }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Resumo por Mês -->
                <div class="mb-5">
                    <h5 class="mb-3">Resumo Mensal</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Mês</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Quantidade</th>
                                    @foreach($categoriesReceitas as $category)
                                        <th class="text-end">{{ $category->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byMonth as $monthIndex => $monthData)
                                    <tr>
                                        <td><strong>{{ ucfirst($monthData['month_name']) }}</strong></td>
                                        <td class="text-end text-primary">
                                            <strong>R$ {{ number_format($monthData['total'], 2, ',', '.') }}</strong>
                                        </td>
                                        <td class="text-end">{{ $monthData['count'] }}</td>
                                        @foreach($categoriesReceitas as $category)
                                            <td class="text-end">
                                                @php
                                                    $categoryItem = collect($monthData['by_category'])->first(function ($item) use ($category) {
                                                        return isset($item['category_name']) && $item['category_name'] == $category->name;
                                                    });
                                                    $categoryTotal = $categoryItem['total'] ?? 0;
                                                @endphp
                                                @if($categoryTotal > 0)
                                                    R$ {{ number_format($categoryTotal, 2, ',', '.') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                <tr class="table-secondary">
                                    <td><strong>Total Geral</strong></td>
                                    <td class="text-end"><strong class="text-primary">R$ {{ number_format($totalGeral, 2, ',', '.') }}</strong></td>
                                    <td class="text-end"><strong>{{ collect($byMonth)->sum('count') }}</strong></td>
                                    @foreach($categoriesReceitas as $category)
                                        <td class="text-end">
                                            @php
                                                $categoryYearTotal = 0;
                                                foreach($byMonth as $monthData) {
                                                    $categoryItem = collect($monthData['by_category'])->first(function ($item) use ($category) {
                                                        return isset($item['category_name']) && $item['category_name'] == $category->name;
                                                    });
                                                    $categoryYearTotal += $categoryItem['total'] ?? 0;
                                                }
                                            @endphp
                                            @if($categoryYearTotal > 0)
                                                <strong>R$ {{ number_format($categoryYearTotal, 2, ',', '.') }}</strong>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- O gráfico é montado pelo partial financial.partials.monthly-bars-chart. --}}
@endsection


