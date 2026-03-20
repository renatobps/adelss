@extends('layouts.porto')

@section('title', 'Editar Feedback')

@section('page-title', 'Editar Feedback')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><a href="{{ route('discipleship.feedbacks.index') }}">Feedbacks</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <form action="{{ route('discipleship.feedbacks.update', $feedback) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="discipleship_member_id" class="form-label">Membro em Discipulado <span class="text-danger">*</span></label>
                        <select class="form-select @error('discipleship_member_id') is-invalid @enderror" id="discipleship_member_id" name="discipleship_member_id" required>
                            <option value="">Selecione um membro</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}" {{ old('discipleship_member_id', $feedback->discipleship_member_id) == $m->id ? 'selected' : '' }}>
                                    {{ $m->member->name }} - {{ $m->cycle->nome }}
                                </option>
                            @endforeach
                        </select>
                        @error('discipleship_member_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="visibilidade" class="form-label">Visibilidade <span class="text-danger">*</span></label>
                        <select class="form-select @error('visibilidade') is-invalid @enderror" id="visibilidade" name="visibilidade" required>
                            <option value="discipulador" {{ old('visibilidade', $feedback->visibilidade) === 'discipulador' ? 'selected' : '' }}>Discipulador</option>
                            <option value="pastor" {{ old('visibilidade', $feedback->visibilidade) === 'pastor' ? 'selected' : '' }}>Pastor</option>
                            <option value="admin" {{ old('visibilidade', $feedback->visibilidade) === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('visibilidade')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="conteudo" class="form-label">Conteúdo <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('conteudo') is-invalid @enderror" 
                                  id="conteudo" name="conteudo" rows="6" required>{{ old('conteudo', $feedback->conteudo) }}</textarea>
                        @error('conteudo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('discipleship.feedbacks.index') }}" class="btn btn-secondary">
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
