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
        <form method="POST" action="{{ route('notificacoes.templates.store') }}" class="mb-4 pb-4 border-bottom">
            @csrf
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tipo do template</label>
                    <input type="text"
                           name="tipo_notificacao"
                           class="form-control @error('tipo_notificacao') is-invalid @enderror"
                           value="{{ old('tipo_notificacao') }}"
                           placeholder="Ex: lembrete, custom..."
                           required>
                    @error('tipo_notificacao')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-7">
                    <label class="form-label">Conteúdo</label>
                    <textarea name="template"
                              class="form-control font-monospace small @error('template') is-invalid @enderror"
                              rows="3"
                              placeholder="Ex: Olá {nome}! Lembrete: {mensagem}"
                              required>{{ old('template') }}</textarea>
                    @error('template')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-2">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="ativo" value="1" checked>
                        <label class="form-check-label small">Ativo</label>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Criar</button>
                </div>
            </div>
        </form>

        @if($templates->isEmpty())
            <div class="alert alert-info mb-0">
                Nenhum template cadastrado ainda.
            </div>
        @else
            @foreach($templates as $t)
                <form method="POST" action="{{ route('notificacoes.templates.update', $t) }}" class="mb-4 pb-4 border-bottom">
                    @csrf
                    @method('PUT')
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tipo</label>
                            <input type="text" name="tipo_notificacao" class="form-control" value="{{ $t->tipo_notificacao }}" required>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" {{ ($t->ativo ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label small">Ativo</label>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Template</label>
                            <textarea name="template" class="form-control font-monospace small" rows="3" required>{{ $t->template ?? '' }}</textarea>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">Salvar</button>
                        </div>
                    </div>
                </form>
                <form method="POST" action="{{ route('notificacoes.templates.destroy', $t) }}" class="mb-4">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Deseja excluir este template?')">
                            Excluir
                        </button>
                    </div>
                </form>
            @endforeach
        @endif
    </div>
</section>
@endsection
