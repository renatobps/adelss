@extends('layouts.porto')

@section('title', 'Editar Voluntário')

@section('page-title', 'Editar Voluntário')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.cadastro.index') }}">Cadastro de Voluntários</a></li>
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
                    <i class="bx bx-edit me-2"></i>Editar Voluntário
                </h2>
            </header>
            <div class="card-body">
                <form action="{{ route('voluntarios.cadastro.update', $volunteer) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h5 class="border-bottom pb-2">Informações do Voluntário</h5>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="member_id" class="form-label">Membro <span class="text-danger">*</span></label>
                            <select class="form-select @error('member_id') is-invalid @enderror" 
                                    id="member_id" name="member_id" required>
                                <option value="">Selecione um membro...</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->id }}" {{ old('member_id', $volunteer->member_id) == $member->id ? 'selected' : '' }}>
                                        {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('member_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="experience_level" class="form-label">Nível de Experiência <span class="text-danger">*</span></label>
                            <select class="form-select @error('experience_level') is-invalid @enderror" 
                                    id="experience_level" name="experience_level" required>
                                <option value="">Selecione...</option>
                                <option value="novo" {{ old('experience_level', $volunteer->experience_level) == 'novo' ? 'selected' : '' }}>Novo</option>
                                <option value="em_treinamento" {{ old('experience_level', $volunteer->experience_level) == 'em_treinamento' ? 'selected' : '' }}>Em Treinamento</option>
                                <option value="experiente" {{ old('experience_level', $volunteer->experience_level) == 'experiente' ? 'selected' : '' }}>Experiente</option>
                            </select>
                            @error('experience_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" name="status" required>
                                <option value="ativo" {{ old('status', $volunteer->status) == 'ativo' ? 'selected' : '' }}>Ativo</option>
                                <option value="inativo" {{ old('status', $volunteer->status) == 'inativo' ? 'selected' : '' }}>Inativo</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Data de Início <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                   id="start_date" name="start_date" value="{{ old('start_date', $volunteer->start_date->format('Y-m-d')) }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="service_areas" class="form-label">Áreas de Serviço</label>
                            <select class="form-select @error('service_areas') is-invalid @enderror" 
                                    id="service_areas" name="service_areas[]" multiple size="5">
                                @foreach($serviceAreas as $area)
                                    <option value="{{ $area->id }}" {{ in_array($area->id, old('service_areas', $volunteer->serviceAreas->pluck('id')->toArray())) ? 'selected' : '' }}>
                                        {{ $area->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Segure Ctrl (ou Cmd no Mac) para selecionar múltiplas áreas</small>
                            @error('service_areas')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="leader_notes" class="form-label">Observações do Líder</label>
                            <textarea class="form-control @error('leader_notes') is-invalid @enderror" 
                                      id="leader_notes" name="leader_notes" rows="4" 
                                      placeholder="Observações sobre o voluntário...">{{ old('leader_notes', $volunteer->leader_notes) }}</textarea>
                            @error('leader_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('voluntarios.cadastro.index') }}" class="btn btn-default">
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
