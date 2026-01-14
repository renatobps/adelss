@extends('layouts.porto')

@section('title', 'Nova Turma')

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.turmas.index') }}">Ensino</a></li>
    <li><a href="{{ route('ensino.turmas.index') }}">Turmas</a></li>
    <li><span>Nova Turma</span></li>
@endsection

@section('content')
<div class="alert alert-info mb-4" style="background-color: #e3f2fd; color: #1976d2; border: none;">
    <i class="bx bx-info-circle me-2"></i>
    Cadastre uma nova turma ou grupo de estudo.
</div>

<div class="row">
    <div class="col-12">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-graduation-cap me-2"></i>Nova Turma
                </h5>
            </header>
            <div class="card-body text-center py-5">
                <i class="fa-solid fa-graduation-cap" style="font-size: 64px; color: #6c757d; opacity: 0.3;"></i>
                <h4 class="mt-3 text-muted">Formulário em desenvolvimento</h4>
                <p class="text-muted">Esta funcionalidade estará disponível em breve.</p>
                <a href="{{ route('ensino.turmas.index') }}" class="btn btn-secondary mt-3">
                    <i class="bx bx-arrow-back me-1"></i>Voltar
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

