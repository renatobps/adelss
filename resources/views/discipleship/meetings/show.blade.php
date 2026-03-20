@extends('layouts.porto')

@section('title', 'Detalhes do Encontro')

@section('page-title', 'Detalhes do Encontro')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><a href="{{ route('discipleship.meetings.index') }}">Encontros</a></li>
    <li><span>{{ $meeting->data->format('d/m/Y') }}</span></li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-12 text-end">
        <a href="{{ route('discipleship.meetings.edit', $meeting) }}" class="btn btn-primary">
            <i class="bx bx-edit me-1"></i>Editar
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-10">
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Informações do Encontro</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Membro:</dt>
                    <dd class="col-sm-9">{{ $meeting->discipleshipMember->member->name }}</dd>

                    <dt class="col-sm-3">Ciclo:</dt>
                    <dd class="col-sm-9">{{ $meeting->discipleshipMember->cycle->nome }}</dd>

                    <dt class="col-sm-3">Data:</dt>
                    <dd class="col-sm-9">{{ $meeting->data->format('d/m/Y') }}</dd>

                    <dt class="col-sm-3">Tipo:</dt>
                    <dd class="col-sm-9">
                        @if($meeting->tipo === 'presencial')
                            <span class="badge bg-primary">Presencial</span>
                        @else
                            <span class="badge bg-info">Online</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        @if($meeting->goals->count() > 0)
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bx bx-target-lock me-1"></i>Propósitos Vinculados</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @foreach($meeting->goals as $goal)
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>{{ $goal->descricao }}</span>
                            <a href="{{ route('discipleship.goals.show', $goal) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bx bx-show me-1"></i>Ver
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if($meeting->assuntos_tratados)
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h5 class="mb-0">Assuntos Tratados</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $meeting->assuntos_tratados }}</p>
            </div>
        </div>
        @endif

        {{-- Questionário Área Espiritual --}}
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bx bx-heart me-1"></i>Questionário - Área Espiritual</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6 class="text-success">Oração</h6>
                        <dl class="mb-0 small">
                            <dt>Tempo por dia:</dt>
                            <dd>@if($meeting->oracao_tempo_dia){{ $meeting->oracao_tempo_dia == 'mais_1h' ? '+ de 1 hora' : $meeting->oracao_tempo_dia . ' min' }}@else-@endif</dd>
                            <dt>Como são:</dt>
                            <dd>{{ $meeting->oracao_como_sao ?? '-' }}</dd>
                            @if($meeting->oracao_observacoes)
                            <dt>Observações:</dt>
                            <dd>{{ $meeting->oracao_observacoes }}</dd>
                            @endif
                        </dl>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-danger">Jejum</h6>
                        <dl class="mb-0 small">
                            <dt>Horas/semana:</dt>
                            <dd>@if($meeting->jejum_horas_semana !== null){{ $meeting->jejum_horas_semana == 'mais_24' ? '+ de 24h' : $meeting->jejum_horas_semana . 'h' }}@else-@endif</dd>
                            <dt>Tipo:</dt>
                            <dd>{{ $meeting->jejum_tipo ? ucfirst($meeting->jejum_tipo) : '-' }}</dd>
                            <dt>Com propósito:</dt>
                            <dd>{{ $meeting->jejum_com_proposito ? ucfirst($meeting->jejum_com_proposito) : '-' }}</dd>
                            @if($meeting->jejum_observacoes)
                            <dt>Observações:</dt>
                            <dd>{{ $meeting->jejum_observacoes }}</dd>
                            @endif
                        </dl>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-primary">Leitura Bíblica</h6>
                        <dl class="mb-0 small">
                            <dt>Capítulos/dia:</dt>
                            <dd>{{ $meeting->leitura_capitulos_dia == 'mais_10' ? '+ de 10' : ($meeting->leitura_capitulos_dia ?? '-') }}</dd>
                            <dt>Estuda os capítulos:</dt>
                            <dd>{{ $meeting->leitura_estuda ? ucfirst($meeting->leitura_estuda) : '-' }}</dd>
                            @if($meeting->leitura_observacoes)
                            <dt>Observações:</dt>
                            <dd>{{ $meeting->leitura_observacoes }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        @if($meeting->proximo_passo)
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h5 class="mb-0">Próximo Passo</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $meeting->proximo_passo }}</p>
            </div>
        </div>
        @endif

        @if($meeting->observacoes_privadas)
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Observações Privadas</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $meeting->observacoes_privadas }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
