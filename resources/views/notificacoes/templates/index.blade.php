@extends('layouts.porto')

@section('title', 'Templates - Notificações')
@section('page-title', 'Templates')
@section('breadcrumbs')
    <li><a href="{{ route('notificacoes.templates.index') }}">Notificações</a></li>
    <li><span>Templates</span></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<section class="card">
    <header class="card-header">
        <h2 class="card-title"><i class="bx bx-file-blank me-2"></i>Templates de mensagens</h2>
        <p class="card-subtitle mb-0">Use as variáveis: @foreach($variaveis as $key => $label) <code>{{ $key }}</code> ({{ $label }}) @endforeach</p>
    </header>
    <div class="card-body">
        @foreach($templates as $t)
            <form method="POST" action="{{ route('notificacoes.templates.update') }}" class="mb-4 pb-4 border-bottom">
                @csrf
                <input type="hidden" name="tipo" value="{{ $t->tipo_notificacao }}">
                <div class="row align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-bold">{{ $t->tipo_notificacao }}</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="ativo" value="1" {{ ($t->ativo ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label small">Ativo</label>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label visually-hidden">Template</label>
                        <textarea name="template" class="form-control font-monospace small" rows="3" placeholder="Ex: Olá {nome}! Lembrete: {mensagem}">{{ $t->template ?? '' }}</textarea>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </div>
            </form>
        @endforeach
    </div>
</section>
@endsection
