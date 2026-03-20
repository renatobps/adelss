@extends('layouts.porto')

@section('title', 'Editar Grupo - Notificações')
@section('page-title', 'Editar Grupo')
@section('breadcrumbs')
    <li><a href="{{ route('notificacoes.grupos.index') }}">Notificações</a></li>
    <li><a href="{{ route('notificacoes.grupos.index') }}">Grupos</a></li>
    <li><span>{{ $grupo->nome }}</span></li>
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
        <h2 class="card-title"><i class="bx bx-group me-2"></i>Editar grupo</h2>
    </header>
    <div class="card-body">
        <form method="POST" action="{{ route('notificacoes.grupos.update', $grupo) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="nome" class="form-control @error('nome') is-invalid @enderror" required value="{{ old('nome', $grupo->nome) }}" maxlength="120">
                        @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-4 pt-2">
                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" {{ old('ativo', $grupo->ativo) ? 'checked' : '' }}>
                        <label class="form-check-label" for="ativo">Ativo</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-control @error('descricao') is-invalid @enderror" rows="3">{{ old('descricao', $grupo->descricao) }}</textarea>
                @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Membros</label>
                <select name="members[]" class="form-select @error('members') is-invalid @enderror" multiple size="10">
                    @foreach($members as $m)
                        <option value="{{ $m->id }}" {{ in_array($m->id, old('members', $selecionados)) ? 'selected' : '' }}>{{ $m->name }} @if($m->phone)({{ $m->phone }})@endif</option>
                    @endforeach
                </select>
                <small class="text-muted">Segure Ctrl (ou Cmd) para selecionar vários.</small>
                @error('members')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('notificacoes.grupos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Atualizar</button>
            </div>
        </form>
    </div>
</section>
@endsection
