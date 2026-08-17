@extends('layouts.porto')

@section('title', 'Detalhes do Discipulado')

@section('page-title', 'Detalhes do Discipulado')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><a href="{{ route('discipleship.members.index') }}">Membros</a></li>
    <li><span>{{ $member->member->name }}</span></li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-12 text-end">
        <a href="{{ route('discipleship.members.edit', $member) }}" class="btn btn-primary">
            <i class="bx bx-edit me-1"></i>Editar
        </a>
        <a href="{{ route('discipleship.meetings.create', ['discipleship_member_id' => $member->id]) }}" class="btn btn-success">
            <i class="bx bx-calendar-plus me-1"></i>Novo Encontro
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Informações do Vínculo</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Membro:</dt>
                    <dd class="col-sm-9">{{ $member->member->name }}</dd>

                    <dt class="col-sm-3">Ciclo:</dt>
                    <dd class="col-sm-9">{{ $member->cycle->nome }}</dd>

                    <dt class="col-sm-3">Discipulador:</dt>
                    <dd class="col-sm-9">{{ $member->discipulador->name ?? '-' }}</dd>

                    <dt class="col-sm-3">Status:</dt>
                    <dd class="col-sm-9">
                        @if($member->status === 'ativo')
                            <span class="badge bg-success">Ativo</span>
                        @elseif($member->status === 'concluido')
                            <span class="badge bg-info">Concluído</span>
                        @else
                            <span class="badge bg-warning">Pausado</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">Data de Início:</dt>
                    <dd class="col-sm-9">{{ $member->data_inicio->format('d/m/Y') }}</dd>

                    <dt class="col-sm-3">Data de Fim:</dt>
                    <dd class="col-sm-9">{{ $member->data_fim ? $member->data_fim->format('d/m/Y') : '-' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bx bx-history me-1"></i>Histórico de Encontros ({{ $member->meetings->count() }})</h5>
                <a href="{{ route('discipleship.meetings.create', ['discipleship_member_id' => $member->id]) }}" class="btn btn-sm btn-success">
                    <i class="bx bx-plus me-1"></i>Novo
                </a>
            </div>
            <div class="card-body">
                @if($member->meetings->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Propósitos</th>
                                    <th>Assuntos</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($member->meetings as $meeting)
                                    <tr>
                                        <td>{{ $meeting->data->format('d/m/Y') }}</td>
                                        <td>
                                            @if($meeting->tipo === 'presencial')
                                                <span class="badge bg-primary">Presencial</span>
                                            @else
                                                <span class="badge bg-info">Online</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($meeting->goals && $meeting->goals->count() > 0)
                                                <small>{{ $meeting->goals->pluck('descricao')->take(2)->join(', ') }}{{ $meeting->goals->count() > 2 ? '...' : '' }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ Str::limit($meeting->assuntos_tratados, 40) ?: '-' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('discipleship.meetings.show', $meeting) }}" class="btn btn-sm btn-info" title="Ver detalhes">
                                                <i class="bx bx-show"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-3">
                        <p>Nenhum encontro registrado.</p>
                    </div>
                @endif
            </div>
        </div>

        @if(count($chartData ?? []) >= 2)
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bx bx-line-chart me-1"></i>Gráfico Comparativo - Área Espiritual</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Evolução ao longo dos encontros: Oração (min/dia), Jejum (h/semana) e Leitura (cap/dia)</p>
                <div id="meetingsChart"></div>
                @if(count($chartData ?? []) > 7)
                    <div class="chart-note d-md-none">Mostrando os 7 encontros mais recentes.</div>
                @endif
            </div>
        </div>
        @endif

        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Propósitos ({{ $member->goals->count() }})</h5>
                <a href="{{ route('discipleship.goals.create', ['discipleship_member_id' => $member->id]) }}" class="btn btn-sm btn-success">
                    <i class="bx bx-plus me-1"></i>Novo
                </a>
            </div>
            <div class="card-body">
                @if($member->goals->count() > 0)
                    <div class="list-group">
                        @foreach($member->goals as $goal)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $goal->descricao }}</h6>
                                        <small class="text-muted">
                                            Tipo: {{ $goal->tipo === 'espiritual' ? 'Espiritual' : 'Material' }} | 
                                            Prazo: {{ $goal->prazo ? $goal->prazo->format('d/m/Y') : 'Sem prazo' }}
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge 
                                            @if($goal->status === 'concluido') bg-success
                                            @elseif($goal->status === 'pausado') bg-warning
                                            @else bg-primary
                                            @endif">
                                            {{ ucfirst(str_replace('_', ' ', $goal->status)) }}
                                        </span>
                                        <a href="{{ route('discipleship.goals.show', $goal) }}" class="btn btn-sm btn-outline-primary" title="Ver propósito">
                                            <i class="bx bx-show"></i> Ver
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted py-3">
                        <p>Nenhum propósito definido.</p>
                        <a href="{{ route('discipleship.goals.create', ['discipleship_member_id' => $member->id]) }}" class="btn btn-sm btn-success mt-2">
                            <i class="bx bx-plus me-1"></i>Criar propósito
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if(count($chartData ?? []) >= 2)
@include('partials.apexcharts')

@push('scripts')
<script>
(function () {
    var C = window.AdelssCharts;

    if (!C || typeof ApexCharts === 'undefined' || !document.getElementById('meetingsChart')) {
        return;
    }

    var chartData = @json($chartData ?? []);
    var labels = chartData.map(function (d) { return d.label; });
    var oracao = chartData.map(function (d) { return d.oracao_min; });
    var jejum = chartData.map(function (d) { return d.jejum_horas; });
    var leitura = chartData.map(function (d) { return d.leitura_cap; });

    // Cada série tem unidade própria, então o valor aparece com o sufixo certo.
    var unidades = [' min/dia', ' h/semana', ' cap/dia'];

    C.render('meetingsChart', function (mobile) {
        var dados = mobile
            ? C.lastPoints(labels, [oracao, jejum, leitura])
            : { labels: labels, series: [oracao, jejum, leitura] };

        return {
            chart: { type: 'line' },
            colors: [C.palette.success, C.palette.danger, C.palette.primary],
            stroke: { width: 2, curve: 'smooth' },
            markers: { size: mobile ? 0 : 3, hover: { size: 5 } },
            series: [
                { name: 'Oração', data: dados.series[0] },
                { name: 'Jejum', data: dados.series[1] },
                { name: 'Leitura', data: dados.series[2] }
            ],
            xaxis: { categories: dados.labels },
            tooltip: {
                y: {
                    formatter: function (valor, contexto) {
                        var indice = contexto && typeof contexto.seriesIndex === 'number' ? contexto.seriesIndex : 0;
                        return C.number(valor) + (unidades[indice] || '');
                    }
                }
            }
        };
    }, { currency: false });
})();
</script>
@endpush
@endif
@endsection
