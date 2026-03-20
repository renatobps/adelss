@extends('layouts.porto')

@section('title', 'Detalhes do Propósito')

@section('page-title', 'Detalhes do Propósito')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><a href="{{ route('discipleship.goals.index') }}">Propósitos</a></li>
    <li><span>{{ \Illuminate\Support\Str::limit($goal->descricao, 30) }}</span></li>
@endsection

@php
    $restricoesLabels = [
        'filmes' => 'Filmes',
        'series' => 'Séries',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'facebook' => 'Facebook'
    ];
    
    $alimentosLabels = [
        'derivados_trigo' => 'Derivados de trigo',
        'guloseimas' => 'Guloseimas',
        'almoco' => 'Almoço',
        'jantar' => 'Jantar',
        'cafe_manha' => 'Café da manhã'
    ];
@endphp

@section('content')
<div class="row mb-3">
    <div class="col-md-12 text-end">
        <a href="{{ route('discipleship.goals.pdf', $goal) }}" class="btn btn-danger" target="_blank">
            <i class="bx bx-file-pdf me-1"></i>Exportar PDF
        </a>
        <a href="{{ route('discipleship.goals.edit', $goal) }}" class="btn btn-primary">
            <i class="bx bx-edit me-1"></i>Editar
        </a>
        <a href="{{ route('discipleship.goals.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-10">
        <!-- Informações Básicas -->
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Informações Básicas</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Membro:</dt>
                    <dd class="col-sm-9">{{ $goal->discipleshipMember->member->name }}</dd>

                    <dt class="col-sm-3">Ciclo:</dt>
                    <dd class="col-sm-9">{{ $goal->discipleshipMember->cycle->nome }}</dd>

                    <dt class="col-sm-3">Discipulador:</dt>
                    <dd class="col-sm-9">{{ $goal->discipleshipMember->discipulador->name ?? '-' }}</dd>

                    <dt class="col-sm-3">Descrição:</dt>
                    <dd class="col-sm-9"><strong>{{ $goal->descricao }}</strong></dd>

                    <dt class="col-sm-3">Tipo:</dt>
                    <dd class="col-sm-9">
                        @if($goal->tipo === 'espiritual')
                            <span class="badge bg-primary">Espiritual</span>
                        @else
                            <span class="badge bg-info">Material</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">Status:</dt>
                    <dd class="col-sm-9">
                        @if($goal->status === 'concluido')
                            <span class="badge bg-success">Concluído</span>
                        @elseif($goal->status === 'pausado')
                            <span class="badge bg-warning">Pausado</span>
                        @else
                            <span class="badge bg-primary">Em Andamento</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">Prazo:</dt>
                    <dd class="col-sm-9">{{ $goal->prazo ? $goal->prazo->format('d/m/Y') : '-' }}</dd>
                </dl>
            </div>
        </div>

        <!-- Área de Propósito -->
        @if($goal->quantidade_dias || $goal->restricoes)
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h5 class="mb-0">🟦 Área de Propósito</h5>
            </div>
            <div class="card-body">
                @if($goal->quantidade_dias)
                <p><strong>Quantidade de dias:</strong> {{ $goal->quantidade_dias }} dias</p>
                @endif

                @if($goal->restricoes && count($goal->restricoes) > 0)
                <p><strong>Restrições durante o propósito:</strong></p>
                <ul>
                    @foreach($goal->restricoes as $restricao)
                        <li>{{ $restricoesLabels[$restricao] ?? $restricao }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
        @endif

        <!-- Área de Jejum -->
        @if($goal->tipo_jejum && $goal->tipo_jejum !== 'nenhum')
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h5 class="mb-0">🟦 Área de Jejum</h5>
            </div>
            <div class="card-body">
                @if($goal->tipo_jejum === 'total')
                    <p><strong>Tipo:</strong> Jejum Total</p>
                    @if($goal->horas_jejum_total)
                        <p><strong>Quantidade de horas:</strong> {{ $goal->horas_jejum_total }} horas</p>
                    @endif
                @elseif($goal->tipo_jejum === 'parcial')
                    <p><strong>Tipo:</strong> Jejum Parcial</p>
                    @if($goal->dias_jejum_parcial)
                        <p><strong>Quantidade de dias:</strong> {{ $goal->dias_jejum_parcial }} dias</p>
                    @endif
                    @if($goal->alimentos_retirados && count($goal->alimentos_retirados) > 0)
                        <p><strong>Alimentos a serem retirados:</strong></p>
                        <ul>
                            @foreach($goal->alimentos_retirados as $alimento)
                                <li>{{ $alimentosLabels[$alimento] ?? $alimento }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </div>
        </div>
        @endif

        <!-- Área de Oração -->
        @if($goal->periodos_oracao_dia || $goal->minutos_oracao_periodo)
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h5 class="mb-0">🟦 Área de Oração</h5>
            </div>
            <div class="card-body">
                @if($goal->periodos_oracao_dia)
                    <p><strong>Período de oração por dia:</strong> {{ $goal->periodos_oracao_dia }} {{ $goal->periodos_oracao_dia == 1 ? 'vez' : 'vezes' }} ao dia</p>
                @endif
                @if($goal->minutos_oracao_periodo)
                    <p><strong>Quantidade de minutos por período:</strong> {{ $goal->minutos_oracao_periodo }} minutos</p>
                @endif
            </div>
        </div>
        @endif

        <!-- Área de Estudo da Palavra -->
        @if($goal->livro_biblia || $goal->capitulos_por_dia)
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h5 class="mb-0">🟦 Área de Estudo da Palavra</h5>
            </div>
            <div class="card-body">
                @if($goal->livro_biblia)
                    <p><strong>Livro a ser estudado:</strong> {{ $goal->livro_biblia }}</p>
                @endif
                @if($goal->capitulos_por_dia)
                    <p><strong>Quantidade de capítulos por dia:</strong> {{ $goal->capitulos_por_dia }} {{ $goal->capitulos_por_dia == 1 ? 'capítulo' : 'capítulos' }}</p>
                @endif
            </div>
        </div>
        @endif

        <!-- Observação -->
        @if($goal->observacao)
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h5 class="mb-0">Observação</h5>
            </div>
            <div class="card-body">
                <div class="observacao-content">
                    {!! $goal->observacao !!}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
.observacao-content {
    line-height: 1.6;
    font-size: 14px;
    white-space: normal;
}
.observacao-content p {
    margin-bottom: 0.75em;
}
.observacao-content p:last-child {
    margin-bottom: 0;
}
.observacao-content p:empty {
    margin-bottom: 0.5em;
    min-height: 1em;
}
.observacao-content ul,
.observacao-content ol {
    margin: 0.5em 0 0.75em 1.5em;
    padding-left: 1em;
}
.observacao-content li {
    margin-bottom: 0.25em;
}
.observacao-content strong { font-weight: bold; }
.observacao-content em { font-style: italic; }
.observacao-content u { text-decoration: underline; }
.observacao-content h1 { font-size: 1.5em; margin: 0.75em 0 0.5em; font-weight: bold; }
.observacao-content h2 { font-size: 1.25em; margin: 0.75em 0 0.5em; font-weight: bold; }
.observacao-content h3 { font-size: 1.1em; margin: 0.5em 0 0.25em; font-weight: bold; }
.observacao-content .ql-align-center,
.observacao-content p.ql-align-center,
.observacao-content [style*="text-align: center"] { text-align: center; }
.observacao-content .ql-align-right,
.observacao-content p.ql-align-right,
.observacao-content [style*="text-align: right"] { text-align: right; }
.observacao-content .ql-align-justify,
.observacao-content p.ql-align-justify,
.observacao-content [style*="text-align: justify"] { text-align: justify; }
.observacao-content table {
    border-collapse: collapse;
    width: 100%;
    margin: 0.75em 0;
}
.observacao-content th,
.observacao-content td {
    border: 1px solid #dee2e6;
    padding: 8px 12px;
    text-align: left;
}
.observacao-content th {
    background-color: #f8f9fa;
    font-weight: bold;
}
</style>
@endsection
