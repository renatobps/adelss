{{--
    Gráfico de série diária do Financeiro.

    Desktop: uma linha por dia, com as quatro séries.
    Celular: barras agrupadas por semana e apenas Receitas e Despesas — no
    celular a pergunta útil é "entrou mais do que saiu nesta semana?", e os
    valores previstos já aparecem como totais na própria tela.

    Parâmetros:
      chartId       id do container, único na página (obrigatório)
      labels        rótulos dos dias
      receitas      série de receitas pagas
      despesas      série de despesas pagas
      aReceber      série de receitas previstas
      aPagar        série de despesas previstas
      area          preenche a área sob a linha no desktop (padrão: false)
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
        var area = @json((bool) ($area ?? false));

        C.render(@json($chartId), function (mobile) {
            if (mobile) {
                var agrupado = C.groupIntoWeeks(labels, [receitas, despesas]);

                return {
                    chart: { type: 'bar' },
                    colors: [C.seriesColors.receitas, C.seriesColors.despesas],
                    series: [
                        { name: 'Receitas', data: agrupado.series[0] },
                        { name: 'Despesas', data: agrupado.series[1] }
                    ],
                    xaxis: { categories: agrupado.labels }
                };
            }

            return {
                chart: { type: area ? 'area' : 'line' },
                stroke: { width: [3, 3, 2, 2], curve: 'smooth', dashArray: [0, 0, 5, 5] },
                fill: area
                    ? { type: ['gradient', 'gradient', 'solid', 'solid'], opacity: [0.2, 0.2, 0, 0] }
                    : { opacity: 0 },
                markers: { size: 0, hover: { size: 4 } },
                series: [
                    { name: 'Receitas', data: receitas },
                    { name: 'Despesas', data: despesas },
                    { name: 'A receber', data: aReceber },
                    { name: 'A pagar', data: aPagar }
                ],
                xaxis: { categories: labels }
            };
        });
    })();
    </script>
    @endpush
@endif
