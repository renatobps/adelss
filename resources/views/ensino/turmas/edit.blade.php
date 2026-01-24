@extends('layouts.porto')

@section('title', 'Editar Turma')

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.turmas.index') }}">Ensino</a></li>
    <li><a href="{{ route('ensino.turmas.index') }}">Turmas</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Sucesso!</strong> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Erro!</strong> {{ session('error') }}
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: #2c3e50; font-weight: 600;">Editar Turma</h5>
                <a href="{{ route('ensino.turmas.index') }}" class="btn btn-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i>Voltar
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('ensino.turmas.update', $turma) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Nome da turma -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">Nome da turma <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $turma->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Escola -->
                    <div class="mb-3">
                        <label for="school_id" class="form-label fw-bold">Escola <span class="text-danger">*</span></label>
                        <select class="form-select @error('school_id') is-invalid @enderror" 
                                id="school_id" name="school_id" required>
                            <option value="">Selecione</option>
                            @foreach($schools as $schoolOption)
                                <option value="{{ $schoolOption->id }}" {{ old('school_id', $turma->school_id) == $schoolOption->id ? 'selected' : '' }}>
                                    {{ $schoolOption->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('school_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Horário -->
                    <div class="mb-3">
                        <label for="schedule" class="form-label fw-bold">Horário</label>
                        <select class="form-select @error('schedule') is-invalid @enderror" 
                                id="schedule" name="schedule">
                            <option value="">Não definido</option>
                            <option value="manhã" {{ old('schedule', $turma->schedule) == 'manhã' ? 'selected' : '' }}>Manhã</option>
                            <option value="tarde" {{ old('schedule', $turma->schedule) == 'tarde' ? 'selected' : '' }}>Tarde</option>
                            <option value="noite" {{ old('schedule', $turma->schedule) == 'noite' ? 'selected' : '' }}>Noite</option>
                        </select>
                        @error('schedule')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label for="status" class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" 
                                id="status" name="status" required>
                            <option value="preparando turma" {{ old('status', $turma->status) == 'preparando turma' ? 'selected' : '' }}>Preparando turma</option>
                            <option value="em andamento" {{ old('status', $turma->status) == 'em andamento' ? 'selected' : '' }}>Em andamento</option>
                            <option value="pausada" {{ old('status', $turma->status) == 'pausada' ? 'selected' : '' }}>Pausada</option>
                            <option value="finalizada" {{ old('status', $turma->status) == 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Descrição -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Descrição</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="4">{{ old('description', $turma->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-save me-1"></i>Salvar Alterações
                        </button>
                        <a href="{{ route('ensino.turmas.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
