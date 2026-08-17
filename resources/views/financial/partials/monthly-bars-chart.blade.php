{{--
    Gráfico de barras de uma única série por mês (resumos anuais).

    Desktop: barras verticais.
    Celular: barras horizontais — o nome do mês fica legível à esquerda sem
    rotação de texto, e a lista cresce na vertical, direção natural de rolagem.

    Parâmetros:
      chartId       id do container, único na página (obrigatório)
      labels        nomes dos meses
      values        totais de cada mês
      seriesName    legenda da série (ex.: "Receitas por mês")
      color         cor da série
      emptyMessage  mensagem quando não há movimentação
--}}
@php
    $chartId = $chartId ?? 'financialMonthlyBarsChart';
    $serieValores = array_map('floatval', $values ?? []);
    $chartTemDados = array_sum($serieValores) > 0;
@endphp

@if($chartTemDados)
    <div id="{{ $chartId }}"></div>
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
        var values = @json(array_values($serieValores));
        var seriesName = @json($seriesName ?? 'Total por mês');
        var color = @json($color ?? null);

        C.render(@json($chartId), function (mobile) {
            return {
                chart: { type: 'bar', height: mobile ? 420 : 320 },
                colors: [color || C.seriesColors.receitas],
                plotOptions: { bar: { horizontal: mobile } },
                legend: { show: false },
                series: [{ name: seriesName, data: values }],
                xaxis: {
                    categories: labels,
                    // Deitado, o eixo de valores tem pouca largura: menos
                    // marcações evitam rótulos colados uns nos outros.
                    tickAmount: mobile ? 3 : undefined,
                    labels: { formatter: mobile ? C.abbreviate : undefined }
                },
                yaxis: {
                    labels: { formatter: mobile ? undefined : C.abbreviate }
                },
                tooltip: { shared: false, intersect: true }
            };
        });
    })();
    </script>
    @endpush
@endif
