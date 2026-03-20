@extends('layouts.porto')

@section('title', 'Editar Vínculo de Membro')

@section('page-title', 'Editar Vínculo de Membro')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><a href="{{ route('discipleship.members.index') }}">Membros</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <form action="{{ route('discipleship.members.update', $member) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="cycle_id" class="form-label">Ciclo <span class="text-danger">*</span></label>
                        <select class="form-select @error('cycle_id') is-invalid @enderror" id="cycle_id" name="cycle_id" required>
                            <option value="">Selecione um ciclo</option>
                            @foreach($cycles as $cycle)
                                <option value="{{ $cycle->id }}" {{ old('cycle_id', $member->cycle_id) == $cycle->id ? 'selected' : '' }}>
                                    {{ $cycle->nome }}
                                </option>
                            @endforeach
                        </select>
                        @error('cycle_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="member_id" class="form-label">Membro <span class="text-danger">*</span></label>
                        <select class="form-select @error('member_id') is-invalid @enderror" id="member_id" name="member_id" required>
                            <option value="">Selecione um membro</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}" {{ old('member_id', $member->member_id) == $m->id ? 'selected' : '' }}>
                                    {{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('member_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="discipulador_id" class="form-label">Discipulador</label>
                        <select class="form-select @error('discipulador_id') is-invalid @enderror" id="discipulador_id" name="discipulador_id">
                            <option value="">Selecione um discipulador</option>
                            @foreach($discipuladores as $discipulador)
                                <option value="{{ $discipulador->id }}" {{ old('discipulador_id', $member->discipulador_id) == $discipulador->id ? 'selected' : '' }}>
                                    {{ $discipulador->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('discipulador_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data_inicio" class="form-label">Data de Início <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('data_inicio') is-invalid @enderror" 
                                   id="data_inicio" name="data_inicio" value="{{ old('data_inicio', $member->data_inicio->format('Y-m-d')) }}" required>
                            @error('data_inicio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="data_fim" class="form-label">Data de Fim</label>
                            <input type="date" class="form-control @error('data_fim') is-invalid @enderror" 
                                   id="data_fim" name="data_fim" value="{{ old('data_fim', $member->data_fim ? $member->data_fim->format('Y-m-d') : '') }}">
                            @error('data_fim')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="ativo" {{ old('status', $member->status) === 'ativo' ? 'selected' : '' }}>Ativo</option>
                            <option value="concluido" {{ old('status', $member->status) === 'concluido' ? 'selected' : '' }}>Concluído</option>
                            <option value="pausado" {{ old('status', $member->status) === 'pausado' ? 'selected' : '' }}>Pausado</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('discipleship.members.show', $member) }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
