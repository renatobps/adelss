@extends('layouts.porto')

@section('title', 'Relatório — '.$form->title)
@section('page-title', 'Relatório do formulário')

@section('breadcrumbs')
    <li><a href="{{ route('midia.index') }}">Mídia</a></li>
    <li><a href="{{ route('midia.formularios.index') }}">Formulários</a></li>
    <li><span>Relatório</span></li>
@endsection

@section('content')
@include('partials.apexcharts')
@include('midia.formularios.partials.alerts')

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="mb-1">{{ $form->title }}</h5>
        <p class="text-muted small mb-0">
            @if(filled($filters['start_date']) || filled($filters['end_date']))
                Período de
                {{ filled($filters['start_date']) ? \Illuminate\Support\Carbon::parse($filters['start_date'])->format('d/m/Y') : 'o início' }}
                até
                {{ filled($filters['end_date']) ? \Illuminate\Support\Carbon::parse($filters['end_date'])->format('d/m/Y') : 'hoje' }}
            @else
                Todas as respostas recebidas
            @endif
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('midia.formularios.responses.index', array_merge([$form], request()->query())) }}"
           class="btn btn-sm btn-outline-primary">
            <i class="bx bx-table"></i> Ver respostas
        </a>
        <a href="{{ route('midia.formularios.export.csv', array_merge([$form], request()->query())) }}"
           class="btn btn-sm btn-success">
            <i class="bx bx-spreadsheet"></i> Excel
        </a>
        <a href="{{ route('midia.formularios.export.pdf', array_merge([$form], request()->query())) }}"
           class="btn btn-sm btn-danger">
            <i class="bx bx-file"></i> PDF
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Respostas</div>
                <div class="h3 mb-0">{{ number_format($total, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Primeira resposta</div>
                <div class="h5 mb-0">
                    {{ $primeira ? \Illuminate\Support\Carbon::parse($primeira)->format('d/m/Y') : '—' }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Última resposta</div>
                <div class="h5 mb-0">
                    {{ $ultima ? \Illuminate\Support\Carbon::parse($ultima)->format('d/m/Y') : '—' }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-transparent">
        <h6 class="mb-0">Respostas por dia</h6>
    </div>
    <div class="card-body">
        @if(count($porDia['labels']) === 0)
            @include('partials.chart-empty', [
                'message' => 'Sem respostas neste período',
                'icon' => 'bx-message-square-x',
            ])
        @else
            <div id="chartPorDia"></div>
            <p class="chart-note d-md-none" id="notaPorDia" hidden>Valores agrupados por semana</p>
        @endif
    </div>
</div>

@foreach($resumoEscolhas as $indice => $resumo)
    @php
        $campo = $resumo['campo'];
        $contagem = $resumo['contagem'];
        $totalMarcacoes = array_sum($contagem);
    @endphp

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent">
            <h6 class="mb-1">{{ $campo->label }}</h6>
            <small class="text-muted">
                {{ $campo->typeLabel() }} ·
                {{ $resumo['respondidos'] }} {{ Str::plural('resposta', $resumo['respondidos']) }}
                @if($resumo['semResposta'] > 0)
                    · {{ $resumo['semResposta'] }} em branco
                @endif
            </small>
        </div>
        <div class="card-body">
            @if($totalMarcacoes === 0)
                @include('partials.chart-empty', [
                    'message' => 'Ninguém respondeu este campo no período',
                    'icon' => 'bx-message-square-x',
                ])
            @else
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg-7">
                        <div id="chartCampo{{ $indice }}"
                             data-labels="{{ json_encode(array_keys($contagem)) }}"
                             data-valores="{{ json_encode(array_values($contagem)) }}"
                             data-chart-campo></div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Opção</th>
                                        <th class="text-end">Respostas</th>
                                        <th class="text-end">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contagem as $opcao => $quantidade)
                                        <tr>
                                            <td>{{ $opcao }}</td>
                                            <td class="text-end">{{ $quantidade }}</td>
                                            <td class="text-end">
                                                {{ number_format($totalMarcacoes > 0 ? $quantidade / $totalMarcacoes * 100 : 0, 1, ',', '.') }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($campo->acceptsMultipleValues())
                            <p class="text-muted small mb-0 mt-2">
                                Cada pessoa pode marcar mais de uma opção, então o percentual é sobre o total de marcações.
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endforeach

@if(count($resumoNumeros) > 0)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent">
            <h6 class="mb-0">Campos numéricos</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Campo</th>
                        <th class="text-end">Respostas</th>
                        <th class="text-end">Soma</th>
                        <th class="text-end">Média</th>
                        <th class="text-end">Menor</th>
                        <th class="text-end">Maior</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumoNumeros as $resumo)
                        <tr>
                            <td>{{ $resumo['campo']->label }}</td>
                            <td class="text-end">{{ $resumo['quantidade'] }}</td>
                            <td class="text-end">{{ number_format($resumo['soma'], 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($resumo['media'], 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($resumo['minimo'], 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($resumo['maximo'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($camposTexto->count() > 0)
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="mb-2">Campos abertos</h6>
            <p class="text-muted small mb-2">
                Respostas em texto livre não são resumidas em gráfico. Consulte uma a uma na tela de respostas
                ou baixe a planilha.
            </p>
            <div class="d-flex flex-wrap gap-1">
                @foreach($camposTexto as $campo)
                    <span class="badge bg-light text-dark">{{ $campo->label }}</span>
                @endforeach
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    if (typeof AdelssCharts === 'undefined') return;

    var porDia = @json($porDia);

    if (porDia.labels.length > 0) {
        var nota = document.getElementById('notaPorDia');

        AdelssCharts.render('chartPorDia', function (mobile) {
            var labels = porDia.labels;
            var valores = porDia.valores;
            var agrupado = false;

            // Muitos dias comprimidos em tela pequena viram uma serra ilegível;
            // agrupar por semana responde a mesma pergunta.
            if (mobile && labels.length > AdelssCharts.MAX_MOBILE_POINTS) {
                var grupos = AdelssCharts.groupIntoWeeks(labels, [valores]);
                labels = grupos.labels;
                valores = grupos.series[0];
                agrupado = true;
            }

            if (nota) nota.hidden = !agrupado;

            return {
                chart: { type: 'bar' },
                series: [{ name: 'Respostas', data: valores }],
                xaxis: { categories: labels },
                yaxis: { labels: { formatter: function (v) { return AdelssCharts.abbreviateNumber(v); } } },
                tooltip: { shared: false, intersect: false }
            };
        }, { currency: false });
    }

    document.querySelectorAll('[data-chart-campo]').forEach(function (elemento) {
        var labels = JSON.parse(elemento.dataset.labels || '[]');
        var valores = JSON.parse(elemento.dataset.valores || '[]');

        AdelssCharts.render(elemento, function (mobile) {
            // Até 5 opções a rosca comunica proporção bem; acima disso as fatias
            // ficam finas e os rótulos se sobrepõem, então vão para barras.
            if (labels.length <= 5) {
                return {
                    chart: { type: 'donut', height: mobile ? 260 : 300 },
                    colors: AdelssCharts.categoryColors,
                    series: valores,
                    labels: labels,
                    legend: { position: 'bottom' },
                    tooltip: { shared: false, y: { formatter: function (v) { return AdelssCharts.number(v); } } },
                    yaxis: { labels: { formatter: undefined } }
                };
            }

            var dados = mobile ? AdelssCharts.limitSlices(labels, valores, 6) : { labels: labels, values: valores };

            return {
                chart: { type: 'bar', height: Math.max(220, dados.labels.length * (mobile ? 34 : 30)) },
                plotOptions: { bar: { horizontal: true, barHeight: '65%', borderRadius: 3 } },
                series: [{ name: 'Respostas', data: dados.values }],
                xaxis: {
                    categories: dados.labels,
                    labels: { formatter: function (v) { return AdelssCharts.abbreviateNumber(v); } }
                },
                yaxis: { labels: { formatter: undefined } },
                legend: { show: false },
                tooltip: { shared: false, y: { formatter: function (v) { return AdelssCharts.number(v); } } }
            };
        }, { currency: false });
    });
})();
</script>
@endpush
