@extends('layouts.porto')

@section('title', 'Resumo Financeiro')

@section('page-title', 'Resumo Financeiro')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Resumo</span></li>
@endsection

@section('content')
@php
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $resultadoPositivo = $resultado >= 0;

    // Somas apenas para decidir se há gráfico a exibir e para o resumo em celular.
    // Não alteram nenhum valor calculado no controller.
    $somaSeries = function (array $dados, array $chaves) {
        $total = 0.0;
        foreach ($chaves as $chave) {
            $total += array_sum(array_map('floatval', $dados[$chave] ?? []));
        }

        return $total;
    };

    $todasAsSeries = ['receitas', 'despesas', 'aReceber', 'aPagar'];
    $mensalTemDados = $somaSeries($monthlyData ?? [], $todasAsSeries) > 0;
    $anualTemDados = $somaSeries($annualData ?? [], $todasAsSeries) > 0;
    $mensalAReceber = $somaSeries($monthlyData ?? [], ['aReceber']);
    $mensalAPagar = $somaSeries($monthlyData ?? [], ['aPagar']);
    $anualReceitas = $somaSeries($annualData ?? [], ['receitas']);
    $anualDespesas = $somaSeries($annualData ?? [], ['despesas']);
@endphp

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="financial-kpi-card h-100">
            <div class="financial-kpi-card__top">
                <div>
                    <div class="financial-kpi-card__title">Saldo Total</div>
                    <div class="financial-kpi-card__period">{{ $periodLabel }}</div>
                </div>
                <i class="bx bx-wallet financial-kpi-card__icon"></i>
            </div>
            <div class="financial-kpi-card__value text-success">{{ $fmt($saldoTotal) }}</div>
            <div class="financial-kpi-card__footer">Balanço do período</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="financial-kpi-card h-100">
            <div class="financial-kpi-card__top">
                <div>
                    <div class="financial-kpi-card__title">Entradas</div>
                    <div class="financial-kpi-card__period">{{ $periodLabel }}</div>
                </div>
                <i class="bx bx-trending-up financial-kpi-card__icon text-success"></i>
            </div>
            <div class="financial-kpi-card__value text-success">{{ $fmt($entradas) }}</div>
            <div class="financial-kpi-card__footer">{{ $entradasCount }} {{ $entradasCount === 1 ? 'transação' : 'transações' }}</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="financial-kpi-card h-100">
            <div class="financial-kpi-card__top">
                <div>
                    <div class="financial-kpi-card__title">Saídas</div>
                    <div class="financial-kpi-card__period">{{ $periodLabel }}</div>
                </div>
                <i class="bx bx-trending-down financial-kpi-card__icon text-danger"></i>
            </div>
            <div class="financial-kpi-card__value text-danger">{{ $fmt($saidas) }}</div>
            <div class="financial-kpi-card__footer">Despesas pagas</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="financial-kpi-card h-100">
            <div class="financial-kpi-card__top">
                <div>
                    <div class="financial-kpi-card__title">Resultado</div>
                    <div class="financial-kpi-card__period">{{ $periodLabel }}</div>
                </div>
                <i class="bx bx-dollar financial-kpi-card__icon"></i>
            </div>
            <div class="financial-kpi-card__value {{ $resultadoPositivo ? 'text-success' : 'text-danger' }}">
                {{ $fmt($resultado) }}
            </div>
            <div class="financial-kpi-card__footer">{{ $resultadoLabel }}</div>
        </div>
    </div>
</div>

<div class="card financial-period-card mb-4">
    <div class="card-body">
        <h5 class="financial-period-card__title mb-3">
            <i class="bx bx-calendar me-2"></i>Período do Resumo
        </h5>
        <form method="GET" action="{{ route('financial.summary') }}" class="row g-3 align-items-end">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <div class="col-md-4 col-lg-3">
                <label for="periodSelect" class="form-label mb-1">Período</label>
                <select class="form-select" id="periodSelect" name="period" onchange="this.form.submit()">
                    @foreach($periodOptions as $value => $label)
                        <option value="{{ $value }}" {{ $periodFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5 col-lg-4">
                <div class="financial-period-card__range">{{ $periodRangeLabel }}</div>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bx bx-calendar-check me-2"></i>Resumo mensal
                </h5>
                <form method="GET" action="{{ route('financial.summary') }}" style="display: inline;">
                    <input type="hidden" name="period" value="{{ $periodFilter }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <select class="form-select form-select-sm" style="max-width: 200px;" name="month" onchange="this.form.submit()">
                        @foreach($availableMonths as $month)
                            <option value="{{ $month['value'] }}" {{ $selectedMonth == $month['value'] ? 'selected' : '' }}>{{ $month['label'] }}</option>
                        @endforeach
                    </select>
                </form>
            </header>
            <div class="card-body">
                @include('financial.partials.daily-chart', [
                    'chartId' => 'monthlyChart',
                    'labels' => $monthlyData['labels'] ?? [],
                    'receitas' => $monthlyData['receitas'] ?? [],
                    'despesas' => $monthlyData['despesas'] ?? [],
                    'aReceber' => $monthlyData['aReceber'] ?? [],
                    'aPagar' => $monthlyData['aPagar'] ?? [],
                    'mobileNote' => 'Valores agrupados por semana do mês.',
                    'emptyMessage' => 'Sem movimentações neste mês',
                ])

                @if($mensalTemDados)
                    <div class="financial-chart-forecast d-md-none">
                        <div class="financial-chart-forecast__item">
                            <span>A receber</span>
                            <strong class="financial-chart-forecast__value--receber">{{ $fmt($mensalAReceber) }}</strong>
                        </div>
                        <div class="financial-chart-forecast__item">
                            <span>A pagar</span>
                            <strong class="financial-chart-forecast__value--pagar">- {{ $fmt($mensalAPagar) }}</strong>
                        </div>
                    </div>
                @else
                    @include('partials.chart-empty', ['message' => 'Sem movimentações neste mês'])
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bx bx-calendar me-2"></i>Resumo anual
                </h5>
                <form method="GET" action="{{ route('financial.summary') }}" style="display: inline;">
                    <input type="hidden" name="period" value="{{ $periodFilter }}">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <select class="form-select form-select-sm" style="max-width: 150px;" name="year" onchange="this.form.submit()">
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
            </header>
            <div class="card-body">
                @if($anualTemDados)
                    <div id="annualChart" class="d-none d-md-block"></div>

                    <div class="financial-annual-mobile d-md-none">
                        <div class="financial-annual-mobile__row">
                            <div class="financial-annual-mobile__figures">
                                <div class="financial-annual-mobile__label">Receitas em {{ $selectedYear }}</div>
                                <div class="financial-annual-mobile__value financial-annual-mobile__value--receitas">{{ $fmt($anualReceitas) }}</div>
                            </div>
                            <div id="annualSparkReceitas" class="financial-annual-mobile__spark"></div>
                        </div>
                        <div class="financial-annual-mobile__row">
                            <div class="financial-annual-mobile__figures">
                                <div class="financial-annual-mobile__label">Despesas em {{ $selectedYear }}</div>
                                <div class="financial-annual-mobile__value financial-annual-mobile__value--despesas">{{ $fmt($anualDespesas) }}</div>
                            </div>
                            <div id="annualSparkDespesas" class="financial-annual-mobile__spark"></div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#annualChartModal">
                            <i class="bx bx-expand-alt me-1"></i>Ver gráfico completo
                        </button>
                    </div>
                @else
                    @include('partials.chart-empty', ['message' => 'Sem movimentações em ' . $selectedYear])
                @endif
            </div>
        </div>
    </div>
</div>

@if($anualTemDados)
<div class="modal fade" id="annualChartModal" tabindex="-1" aria-labelledby="annualChartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="annualChartModalLabel">Resumo anual de {{ $selectedYear }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Gire o aparelho para a horizontal para ver todos os meses com folga.</p>
                <div id="annualChartFull"></div>
            </div>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
    .financial-kpi-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.85rem;
        padding: 1.15rem 1.2rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .financial-kpi-card__top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 0.85rem;
    }
    .financial-kpi-card__title {
        font-size: 0.98rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.2;
    }
    .financial-kpi-card__period {
        margin-top: 0.2rem;
        font-size: 0.82rem;
        color: #6b7280;
    }
    .financial-kpi-card__icon {
        font-size: 1.35rem;
        color: #6b7280;
        line-height: 1;
    }
    .financial-kpi-card__value {
        font-size: 1.65rem;
        font-weight: 700;
        line-height: 1.15;
        margin-bottom: 0.55rem;
    }
    .financial-kpi-card__footer {
        font-size: 0.82rem;
        color: #6b7280;
    }
    .financial-period-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.85rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .financial-period-card__title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #111827;
    }
    .financial-period-card__range {
        display: inline-flex;
        align-items: center;
        min-height: 38px;
        padding: 0.45rem 0.9rem;
        border-radius: 0.55rem;
        background: #f3f4f6;
        color: #4b5563;
        font-size: 0.92rem;
        font-weight: 500;
    }
    .financial-chart-forecast {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid #EEF0F2;
    }
    .financial-chart-forecast__item {
        flex: 1 1 0;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
        font-size: 0.82rem;
        color: #6C757D;
    }
    .financial-chart-forecast__item strong {
        font-size: 0.95rem;
    }
    /* Mesmas cores das séries do gráfico, vindas dos tokens do sistema. */
    .financial-chart-forecast__value--receber {
        color: #1FA855;
    }
    .financial-chart-forecast__value--pagar {
        color: #DC3545;
    }
    .financial-annual-mobile__value--receitas {
        color: #0088CC;
    }
    .financial-annual-mobile__value--despesas {
        color: #F5A623;
    }
    .financial-annual-mobile__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.6rem 0;
        border-bottom: 1px solid #EEF0F2;
    }
    .financial-annual-mobile__figures {
        min-width: 0;
    }
    .financial-annual-mobile__label {
        font-size: 0.82rem;
        color: #6C757D;
    }
    .financial-annual-mobile__value {
        font-size: 1.3rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .financial-annual-mobile__spark {
        width: 110px;
        flex: 0 0 110px;
    }
    .financial-annual-mobile .btn {
        margin-top: 0.85rem;
    }
</style>
@endpush

@include('partials.apexcharts')

@push('scripts')
<script>
(function () {
    var C = window.AdelssCharts;

    if (!C || typeof ApexCharts === 'undefined') {
        return;
    }

    var annual = @json($annualData ?? []);

    // Resumo anual: gráfico completo no desktop, sparklines com totais no celular.
    var annualChart = null;
    var annualSparksReady = false;
    var annualFullChart = null;

    function annualOptions(height) {
        return C.deepMerge(C.baseOptions(C.isMobile()), {
            chart: { type: 'line', height: height },
            stroke: { width: [3, 3, 2, 2], curve: 'smooth', dashArray: [0, 0, 5, 5] },
            markers: { size: 0, hover: { size: 4 } },
            series: [
                { name: 'Receitas', data: annual.receitas || [] },
                { name: 'Despesas', data: annual.despesas || [] },
                { name: 'A receber', data: annual.aReceber || [] },
                { name: 'A pagar', data: annual.aPagar || [] }
            ],
            xaxis: { categories: annual.labels || [] }
        });
    }

    function setupAnnual() {
        var container = document.getElementById('annualChart');

        if (!container) {
            return;
        }

        // Cada versão só é criada quando fica visível: o ApexCharts não consegue
        // medir a largura de um container escondido por display:none.
        if (!C.isMobile() && !annualChart) {
            annualChart = new ApexCharts(container, annualOptions(320));
            annualChart.render();
        }

        if (C.isMobile() && !annualSparksReady) {
            C.sparkline('annualSparkReceitas', annual.receitas || [], C.seriesColors.receitas);
            C.sparkline('annualSparkDespesas', annual.despesas || [], C.seriesColors.despesas);
            annualSparksReady = true;
        }
    }

    setupAnnual();

    var resizeTimer = null;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(setupAnnual, 200);
    });

    var annualModal = document.getElementById('annualChartModal');
    if (annualModal) {
        annualModal.addEventListener('shown.bs.modal', function () {
            if (annualFullChart) {
                return;
            }

            annualFullChart = new ApexCharts(
                document.getElementById('annualChartFull'),
                annualOptions(Math.max(260, window.innerHeight - 220))
            );
            annualFullChart.render();
        });
    }
})();
</script>
@endpush
@endsection
