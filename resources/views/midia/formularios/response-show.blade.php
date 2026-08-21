@extends('layouts.porto')

@section('title', 'Resposta — '.$form->title)
@section('page-title', 'Resposta')

@section('breadcrumbs')
    <li><a href="{{ route('midia.index') }}">Mídia</a></li>
    <li><a href="{{ route('midia.formularios.index') }}">Formulários</a></li>
    <li><a href="{{ route('midia.formularios.responses.index', $form) }}">Respostas</a></li>
    <li><span>#{{ $submission->id }}</span></li>
@endsection

@section('content')
@include('midia.formularios.partials.alerts')

@php $respostas = $submission->answersByField(); @endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h5 class="mb-1">{{ $form->title }}</h5>
                <p class="text-muted small mb-0">
                    Resposta #{{ $submission->id }} ·
                    enviada em {{ optional($submission->submitted_at)->format('d/m/Y \à\s H:i') }}
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('midia.formularios.responses.index', $form) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bx bx-arrow-back"></i> Voltar
                </a>
                @can('midia.formularios.manage')
                    <form method="POST" action="{{ route('midia.formularios.responses.destroy', [$form, $submission]) }}"
                          onsubmit="return confirm('Excluir esta resposta? A ação não pode ser desfeita.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bx bx-trash"></i> Excluir
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</div>

@if($form->requires_identification)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="small text-muted">Nome</div>
                    <div class="fw-semibold">{{ $submission->respondent_name ?: 'Não informado' }}</div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="small text-muted">Telefone</div>
                    <div class="fw-semibold">{{ $submission->respondent_phone ?: 'Não informado' }}</div>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h6 class="mb-0">Respostas do formulário</h6>
    </div>
    <div class="card-body">
        @foreach($fields as $campo)
            @php
                $resposta = $respostas[$campo->id] ?? null;
                $valor = $campo->formatValue($resposta);
            @endphp

            <div class="mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="small text-muted mb-1">
                    {{ $campo->label }}
                    @if($campo->trashed())
                        <span class="badge bg-light text-dark ms-1">campo removido</span>
                    @endif
                </div>

                @if($valor === '')
                    <div class="text-muted fst-italic">Sem resposta</div>
                @elseif($campo->acceptsMultipleValues() && $resposta)
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($resposta->selectedValues() as $escolha)
                            <span class="badge bg-primary">{{ $escolha }}</span>
                        @endforeach
                    </div>
                @else
                    <div style="white-space: pre-line;">{{ $valor }}</div>
                @endif
            </div>
        @endforeach

        @if($fields->isEmpty())
            <p class="text-muted mb-0">Este formulário não tem campos cadastrados.</p>
        @endif
    </div>
</div>
@endsection
