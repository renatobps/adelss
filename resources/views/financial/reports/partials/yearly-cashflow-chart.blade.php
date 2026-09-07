{{--
    Gráfico anual: barras de entrada e saída + linha de saldo acumulado.
--}}
@php
    $chartId = $chartId ?? 'yearlyCashFlowChart';
    $serieEntradas = array_map('floatval', $entradas ?? []);
    $serieSaidas = array_map('floatval', $saidas ?? []);
    $serieSaldos = array_map('floatval', $saldos ?? []);
    $chartTemDados = array_sum($serieEntradas) + array_sum($serieSaidas) > 0;
@endphp

@if($chartTemDados)
    <div id="{{ $chartId }}" class="mb-2"></div>
@else
    @include('partials.chart-empty', ['message' => $emptyMessage ?? 'Sem movimentações neste ano'])
@endif

@include('partials.apexcharts')

@if($chartTemDados)
    @push('scripts')
    <script>
    (function () {
        var C = window.AdelssCharts;
        if (!C || typeof ApexCharts === 'undefined' || !document.getElementById(@json($chartId))) {
            return;
        }

        var labels = @json(array_values($labels ?? []));
        var entradas = @json(array_values($serieEntradas));
        var saidas = @json(array_values($serieSaidas));
        var saldos = @json(array_values($serieSaldos));

        C.render(@json($chartId), function (mobile) {
            return {
                chart: { type: 'line', height: mobile ? 420 : 360, stacked: false },
                colors: [C.seriesColors.aReceber, C.seriesColors.aPagar, C.seriesColors.receitas],
                stroke: { width: [0, 0, 3], curve: 'smooth' },
                plotOptions: { bar: { columnWidth: mobile ? '70%' : '55%' } },
                series: [
                    { name: 'Entrada', type: 'column', data: entradas },
                    { name: 'Saída', type: 'column', data: saidas },
                    { name: 'Saldo acumulado', type: 'line', data: saldos }
                ],
                xaxis: { categories: labels },
                yaxis: {
                    labels: { formatter: C.abbreviate }
                },
                legend: { position: 'top' },
                tooltip: { shared: true, intersect: false }
            };
        });
    })();
    </script>
    @endpush
@endif
