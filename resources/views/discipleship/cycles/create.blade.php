@extends('layouts.porto')

@section('title', 'Criar Ciclo de Discipulado')

@section('page-title', 'Criar Ciclo de Discipulado')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><a href="{{ route('discipleship.cycles.index') }}">Ciclos</a></li>
    <li><span>Criar</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <form action="{{ route('discipleship.cycles.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do Ciclo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nome') is-invalid @enderror" 
                               id="nome" name="nome" value="{{ old('nome') }}" required>
                        @error('nome')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control @error('descricao') is-invalid @enderror" 
                                  id="descricao" name="descricao" rows="4">{{ old('descricao') }}</textarea>
                        @error('descricao')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data_inicio" class="form-label">Data de Início <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('data_inicio') is-invalid @enderror" 
                                   id="data_inicio" name="data_inicio" value="{{ old('data_inicio') }}" required>
                            @error('data_inicio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="data_fim" class="form-label">Data de Fim</label>
                            <input type="date" class="form-control @error('data_fim') is-invalid @enderror" 
                                   id="data_fim" name="data_fim" value="{{ old('data_fim') }}">
                            @error('data_fim')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="ativo" {{ old('status') === 'ativo' ? 'selected' : '' }}>Ativo</option>
                            <option value="encerrado" {{ old('status') === 'encerrado' ? 'selected' : '' }}>Encerrado</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('discipleship.cycles.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-check me-1"></i>Criar Ciclo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
