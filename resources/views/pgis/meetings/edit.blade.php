@extends('layouts.porto')

@section('title', 'Editar reunião')

@section('page-title', 'Editar reunião')

@section('breadcrumbs')
    <li><a href="{{ route('pgis.index') }}">PGIs</a></li>
    <li><a href="{{ route('pgis.show', $pgi) }}">{{ $pgi->name }}</a></li>
    <li><a href="{{ route('pgis.meetings.show', [$pgi, $meeting]) }}">{{ $meeting->meeting_date->format('d/m/Y') }}</a></li>
    <li><span>Editar</span></li>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/css/pgis.css') }}?v={{ @filemtime(public_path('css/css/pgis.css')) ?: '1' }}">
@endpush

@section('content')
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card pgi-card">
            <header class="card-header">
                <h5 class="card-title mb-0"><i class="bx bx-edit me-2"></i>Dados da reunião</h5>
            </header>
            <div class="card-body">
                <p class="text-muted small">
                    A lista de presença não é alterada aqui. Para corrigir a chamada, use
                    <a href="{{ route('pgis.meetings.attendance', [$pgi, $meeting]) }}">registrar presença</a>.
                </p>

                <form action="{{ route('pgis.meetings.update', [$pgi, $meeting]) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="meeting_date" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('meeting_date') is-invalid @enderror"
                                   id="meeting_date" name="meeting_date"
                                   value="{{ old('meeting_date', $meeting->meeting_date->format('Y-m-d')) }}" required>
                            @error('meeting_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="subject" class="form-label">Tema / assunto</label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                   id="subject" name="subject" value="{{ old('subject', $meeting->subject) }}">
                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="total_value" class="form-label">Oferta arrecadada</label>
                            <input type="number" step="0.01" min="0"
                                   class="form-control @error('total_value') is-invalid @enderror"
                                   id="total_value" name="total_value"
                                   value="{{ old('total_value', $meeting->total_value) }}">
                            @error('total_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Observações</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes"
                                  rows="5">{{ old('notes', $meeting->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Salvar alterações
                        </button>
                        <a href="{{ route('pgis.meetings.show', [$pgi, $meeting]) }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
