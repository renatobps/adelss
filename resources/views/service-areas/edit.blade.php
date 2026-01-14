@extends('layouts.porto')

@section('title', 'Editar Área de Serviço')

@section('page-title', 'Editar Área de Serviço')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.areas.index') }}">Áreas de Serviço</a></li>
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
                    <i class="bx bx-edit me-2"></i>Editar Área de Serviço
                </h2>
            </header>
            <div class="card-body">
                <form action="{{ route('voluntarios.areas.update', $area) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h5 class="border-bottom pb-2">Informações da Área</h5>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">Nome da Área <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $area->name) }}" 
                                   placeholder="Ex: Portaria, Recepção..." required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Descreva a área de serviço...">{{ old('description', $area->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="leader_id" class="form-label">Responsável</label>
                            <select class="form-select @error('leader_id') is-invalid @enderror" 
                                    id="leader_id" name="leader_id">
                                <option value="">Selecione um responsável...</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->id }}" {{ old('leader_id', $area->leader_id) == $member->id ? 'selected' : '' }}>
                                        {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('leader_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="min_quantity" class="form-label">Quantidade Mínima <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('min_quantity') is-invalid @enderror" 
                                   id="min_quantity" name="min_quantity" value="{{ old('min_quantity', $area->min_quantity) }}" 
                                   min="1" required>
                            <small class="form-text text-muted">Quantidade mínima de voluntários por culto</small>
                            @error('min_quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="allowed_audience" class="form-label">Público Permitido <span class="text-danger">*</span></label>
                            <select class="form-select @error('allowed_audience') is-invalid @enderror" 
                                    id="allowed_audience" name="allowed_audience" required>
                                <option value="ambos" {{ old('allowed_audience', $area->allowed_audience) == 'ambos' ? 'selected' : '' }}>Ambos</option>
                                <option value="adulto" {{ old('allowed_audience', $area->allowed_audience) == 'adulto' ? 'selected' : '' }}>Adulto</option>
                                <option value="jovem" {{ old('allowed_audience', $area->allowed_audience) == 'jovem' ? 'selected' : '' }}>Jovem</option>
                            </select>
                            @error('allowed_audience')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" name="status" required>
                                <option value="ativo" {{ old('status', $area->status) == 'ativo' ? 'selected' : '' }}>Ativo</option>
                                <option value="inativo" {{ old('status', $area->status) == 'inativo' ? 'selected' : '' }}>Inativo</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('voluntarios.areas.index') }}" class="btn btn-default">
                                <i class="bx bx-arrow-back me-2"></i>Voltar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-check me-2"></i>Salvar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
