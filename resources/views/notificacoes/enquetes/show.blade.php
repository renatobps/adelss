@extends('layouts.porto')

@section('title', $enquete->titulo . ' - Enquetes')
@section('page-title', $enquete->titulo)
@section('breadcrumbs')
    <li><a href="{{ route('notificacoes.enquetes.index') }}">Notificações</a></li>
    <li><a href="{{ route('notificacoes.enquetes.index') }}">Enquetes</a></li>
    <li><span>{{ $enquete->titulo }}</span></li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bx bx-bar-chart-alt-2 me-2"></i>{{ $enquete->titulo }}</h1>
    <div>
        <a href="{{ route('notificacoes.enquetes.edit', $enquete) }}" class="btn btn-outline-secondary me-1">
            <i class="bx bx-edit me-1"></i> Editar
        </a>
        <a href="{{ route('notificacoes.enquetes.index') }}" class="btn btn-outline-primary">
            <i class="bx bx-arrow-back me-1"></i> Voltar
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i> <strong>Sucesso!</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i> <strong>Atenção!</strong> {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i> <strong>Erro!</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i> <strong>Erro na validação:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Informações da Enquete -->
<section class="card mb-4">
    <header class="card-header">
        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Informações da Enquete</h5>
    </header>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <h6>Descrição:</h6>
                <p>{{ $enquete->descricao ?: 'Sem descrição' }}</p>

                <h6>Opções:</h6>
                <ul class="list-group list-group-flush">
                    @foreach($enquete->opcoes ?? [] as $index => $opcao)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $index + 1 }}) {{ $opcao }}
                            <span class="badge bg-primary rounded-pill">{{ $estatisticas[$opcao]['count'] ?? 0 }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h3 class="text-primary">{{ $enquete->respostas_count }}</h3>
                        <p class="mb-0">Total de Respostas</p>
                    </div>
                </div>
                <div class="mt-3">
                    <strong>Status:</strong>
                    @if($enquete->ativa)
                        <span class="badge bg-success">Ativa</span>
                    @else
                        <span class="badge bg-secondary">Inativa</span>
                    @endif
                </div>
                <div class="mt-3">
                    <strong>Tipo:</strong>
                    <span class="badge bg-primary"><i class="bx bx-bar-chart me-1"></i> Botões (Evolution GO)</span>
                </div>
                @if($enquete->inicio_em)
                    <div class="mt-2">
                        <strong>Início:</strong><br>
                        {{ $enquete->inicio_em->format('d/m/Y H:i') }}
                    </div>
                @endif
                @if($enquete->fim_em)
                    <div class="mt-2">
                        <strong>Fim:</strong><br>
                        {{ $enquete->fim_em->format('d/m/Y H:i') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<!-- Gráfico de Resultados -->
@if($enquete->respostas_count > 0)
    <section class="card mb-4">
        <header class="card-header">
            <h5 class="mb-0"><i class="bx bx-pie-chart-alt me-2"></i>Resultados</h5>
        </header>
        <div class="card-body">
            <div class="mx-auto" style="max-width: 480px;">
                <div id="resultadosChart"></div>
            </div>
        </div>
    </section>
@else
    <section class="card mb-4">
        <header class="card-header">
            <h5 class="mb-0"><i class="bx bx-pie-chart-alt me-2"></i>Resultados</h5>
        </header>
        <div class="card-body">
            <p class="text-muted text-center mb-0">Nenhuma resposta ainda</p>
        </div>
    </section>
@endif

<!-- Enviar Enquete -->
<section class="card mb-4">
    <header class="card-header">
        <h5 class="mb-0"><i class="bx bx-send me-2"></i>Enviar Enquete</h5>
    </header>
    <div class="card-body">
        <form action="{{ route('notificacoes.enquetes.enviar', $enquete) }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bx bx-building me-1"></i> Departamentos
                    </label>
                    <select name="departments[]" class="form-select" multiple size="8">
                        @forelse($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @empty
                            <option disabled>Nenhum departamento disponível</option>
                        @endforelse
                    </select>
                    <div class="form-text">Enviar para todos os membros dos departamentos selecionados</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bx bx-user me-1"></i> Membros Individuais
                    </label>
                    <select name="members[]" class="form-select" multiple size="8">
                        @foreach($members as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Segure Ctrl/Cmd para selecionar múltiplos membros</div>
                </div>
            </div>
            <div class="alert alert-info mt-3 mb-0">
                <strong><i class="bx bx-info-circle me-1"></i> Como funciona:</strong>
                <ul class="mb-0 mt-2">
                    <li>Selecione <strong>departamentos</strong> e/ou <strong>membros</strong> individuais</li>
                    <li>É necessário selecionar pelo menos um departamento ou membro</li>
                    <li>A enquete será enviada com <strong>botões clicáveis</strong> no WhatsApp (Evolution GO <code>/send/button</code>)</li>
                </ul>
            </div>
            <div class="d-grid gap-2 mt-3">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bx bx-send me-1"></i> Enviar Enquete
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Respostas Recentes -->
@if($enquete->respostas_count > 0)
    <section class="card">
        <header class="card-header">
            <h5 class="mb-0"><i class="bx bx-list-check me-2"></i>Respostas Recentes</h5>
        </header>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Membro</th>
                            <th>Telefone</th>
                            <th>Resposta</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($respostas as $resposta)
                            <tr>
                                <td>{{ $resposta->member->name ?? 'Não identificado' }}</td>
                                <td>{{ $resposta->telefone }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $resposta->resposta }}</span>
                                </td>
                                <td>{{ $resposta->respondido_em->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @if($respostas->hasPages())
            <div class="card-footer">{{ $respostas->links() }}</div>
        @endif
    </section>
@endif

@if($enquete->respostas_count > 0)
@include('partials.apexcharts')

@push('scripts')
<script>
(function () {
    var C = window.AdelssCharts;

    if (!C || typeof ApexCharts === 'undefined' || !document.getElementById('resultadosChart')) {
        return;
    }

    var estatisticas = @json($estatisticas);
    var opcoes = Object.keys(estatisticas);
    var votos = opcoes.map(function (opcao) { return Number(estatisticas[opcao].count) || 0; });

    // Muitas opções em uma rosca ficam ilegíveis: acima de 6, viram barras
    // horizontais ordenadas por votos.
    var comoBarras = opcoes.length > 6;

    var total = votos.reduce(function (soma, valor) { return soma + valor; }, 0);

    function comPercentual(valor) {
        var percentual = total > 0 ? Math.round((valor / total) * 1000) / 10 : 0;
        return C.number(valor) + (valor === 1 ? ' voto' : ' votos') + ' (' + C.number(percentual) + '%)';
    }

    C.render('resultadosChart', function (mobile) {
        var dados = C.limitSlices(opcoes, votos, comoBarras ? opcoes.length : 5);

        if (comoBarras) {
            return {
                chart: { type: 'bar' },
                colors: [C.palette.primary],
                plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '70%' } },
                legend: { show: false },
                series: [{ name: 'Votos', data: dados.values }],
                xaxis: { categories: dados.labels },
                yaxis: { labels: { maxWidth: mobile ? 110 : 200 } },
                tooltip: { shared: false, y: { formatter: comPercentual } }
            };
        }

        return {
            chart: { type: 'donut' },
            colors: C.categoryColors,
            labels: dados.labels,
            series: dados.values,
            stroke: { width: 2 },
            plotOptions: { pie: { donut: { size: '62%' } } },
            legend: {
                formatter: function (nome, opcoes) {
                    return nome + ': ' + C.number(opcoes.w.globals.series[opcoes.seriesIndex]);
                }
            },
            tooltip: { shared: false, y: { formatter: comPercentual } }
        };
    }, { currency: false });
})();
</script>
@endpush
@endif
@endsection
