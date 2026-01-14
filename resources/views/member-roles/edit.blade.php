@extends('layouts.porto')

@section('title', 'Editar Cargo')

@section('page-title', 'Editar Cargo')

@section('breadcrumbs')
    <li><a href="{{ route('members.index') }}">Membros</a></li>
    <li><a href="{{ route('member-roles.index') }}">Cargos</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <div class="card-actions">
                    <a href="#" class="card-action card-action-toggle" data-card-toggle></a>
                    <a href="#" class="card-action card-action-dismiss" data-card-dismiss></a>
                </div>
                <h2 class="card-title">
                    <i class="bx bx-briefcase me-2"></i>Editar Cargo
                </h2>
                <p class="card-subtitle">Edite as informações do cargo</p>
            </header>
            <div class="card-body">
                <form action="{{ route('member-roles.update', $memberRole) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">Nome do Cargo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $memberRole->name) }}" 
                                   placeholder="Ex: Pastor, Diácono, Secretário..." required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4" 
                                      placeholder="Descreva as responsabilidades deste cargo...">{{ old('description', $memberRole->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                       {{ old('is_active', $memberRole->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Cargo Ativo
                                </label>
                                <small class="form-text text-muted d-block">Cargos inativos não aparecerão na lista ao cadastrar membros</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-2"></i>Atualizar
                        </button>
                        <a href="{{ route('member-roles.index') }}" class="btn btn-default">
                            <i class="bx bx-arrow-back me-2"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection


