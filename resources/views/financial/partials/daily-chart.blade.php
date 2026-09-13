{{--
    Gráfico de barras do Financeiro (série diária).

    Desktop e celular: barras agrupadas. Com muitos dias, os valores são
    somados por semana para as barras ficarem legíveis.

    Parâmetros:
      chartId       id do container, único na página (obrigatório)
      labels        rótulos dos dias
      receitas      série de receitas pagas
      despesas      série de despesas pagas
      aReceber      série de receitas previstas
      aPagar        série de despesas previstas
      mobileNote    texto auxiliar exibido só em celular
      emptyMessage  mensagem quando não há movimentação
--}}
@php
    $chartId = $chartId ?? 'financialDailyChart';
    $serieReceitas = array_map('floatval', $receitas ?? []);
    $serieDespesas = array_map('floatval', $despesas ?? []);
    $serieAReceber = array_map('floatval', $aReceber ?? []);
    $serieAPagar = array_map('floatval', $aPagar ?? []);

    $chartTemDados = array_sum($serieReceitas)
        + array_sum($serieDespesas)
        + array_sum($serieAReceber)
        + array_sum($serieAPagar) > 0;
@endphp

@if($chartTemDados)
    <div id="{{ $chartId }}"></div>
    <div class="chart-note d-md-none">
        {{ $mobileNote ?? 'Valores agrupados por período.' }}
    </div>
@else
    @include('partials.chart-empty', ['message' => $emptyMessage ?? 'Sem movimentações neste período'])
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
        var receitas = @json(array_values($serieReceitas));
        var despesas = @json(array_values($serieDespesas));
        var aReceber = @json(array_values($serieAReceber));
        var aPagar = @json(array_values($serieAPagar));

        C.render(@json($chartId), function (mobile) {
            var seriesList = mobile
                ? [receitas, despesas]
                : [receitas, despesas, aReceber, aPagar];
            var names = mobile
                ? ['Receitas', 'Despesas']
                : ['Receitas', 'Despesas', 'A receber', 'A pagar'];
            var colors = mobile
                ? [C.seriesColors.receitas, C.seriesColors.despesas]
                : [C.seriesColors.receitas, C.seriesColors.despesas, C.seriesColors.aReceber, C.seriesColors.aPagar];
            var agrupado = labels.length > 10
                ? C.groupIntoWeeks(labels, seriesList)
                : { labels: labels, series: seriesList };

            return {
                chart: { type: 'bar', stacked: false },
                colors: colors,
                series: names.map(function (name, index) {
                    return { name: name, data: agrupado.series[index] };
                }),
                xaxis: { categories: agrupado.labels }
            };
        });
    })();
    </script>
    @endpush
@endif
