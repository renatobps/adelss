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
                <canvas id="meetingsChart" height="120"></canvas>
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
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = @json($chartData ?? []);
    if (chartData.length < 2) return;
    const ctx = document.getElementById('meetingsChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(d => d.label),
            datasets: [
                {
                    label: 'Oração (min/dia)',
                    data: chartData.map(d => d.oracao_min),
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.1)',
                    tension: 0.3,
                },
                {
                    label: 'Jejum (h/semana)',
                    data: chartData.map(d => d.jejum_horas),
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    tension: 0.3
                },
                {
                    label: 'Leitura (cap/dia)',
                    data: chartData.map(d => d.leitura_cap),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            var labels = [' min/dia', ' h/semana', ' cap/dia'];
                            return ctx.dataset.label + ': ' + ctx.parsed.y + (labels[ctx.datasetIndex] || '');
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>
@endpush
@endif
@endsection
