@extends('layouts.porto')

@section('title', 'Visualizar Estudo')

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.estudos.index') }}">Ensino</a></li>
    <li><a href="{{ route('ensino.estudos.index') }}">Estudos</a></li>
    <li><span>Visualizar</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canEditEstudos = $isAdmin || 
                     ($user && ($user->hasPermission('ensino.estudos.edit') || 
                                $user->hasPermission('ensino.estudos.manage')));
@endphp

<div class="row">
    <!-- Conteúdo Principal -->
    <div class="col-lg-8">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Visualizar Estudo</h5>
                <div>
                    @if($canEditEstudos)
                    <a href="{{ route('ensino.estudos.edit', $estudo) }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-edit me-1"></i>Editar
                    </a>
                    @endif
                    <a href="{{ route('ensino.estudos.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bx bx-arrow-back me-1"></i>Voltar
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Título do estudo -->
                <div class="mb-4">
                    <h2 class="fw-bold">{{ $estudo->name }}</h2>
                    <p class="text-muted small mb-0">
                        <i class="bx bx-calendar me-1"></i>
                        Criado em: {{ $estudo->created_at->format('d/m/Y H:i') }}
                        @if($estudo->updated_at != $estudo->created_at)
                            | Atualizado em: {{ $estudo->updated_at->format('d/m/Y H:i') }}
                        @endif
                    </p>
                </div>

                <!-- Conteúdo -->
                <div class="mb-4">
                    @if($estudo->content)
                        <div class="study-content">
                            {!! $estudo->content !!}
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            Este estudo não possui conteúdo.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Direita -->
    <div class="col-lg-4">
        <!-- Imagem em destaque -->
        @if($estudo->featured_image)
        <div class="card mb-3" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h6 class="mb-0">Imagem em destaque</h6>
            </div>
            <div class="card-body text-center">
                <img src="{{ asset('storage/' . $estudo->featured_image) }}" 
                     alt="Imagem em destaque" 
                     class="img-fluid rounded" 
                     style="max-height: 300px;">
            </div>
        </div>
        @endif

        <!-- Anexar arquivo -->
        @if($estudo->attachment)
        <div class="card mb-3" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h6 class="mb-0">Arquivo anexado</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bx bx-file me-1"></i>{{ $estudo->attachment_name ?? 'Arquivo anexado' }}
                    </span>
                    <a href="{{ asset('storage/' . $estudo->attachment) }}" 
                       target="_blank" 
                       class="btn btn-sm btn-primary">
                        <i class="bx bx-download me-1"></i>Baixar
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Informações -->
        <div class="card mb-3" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h6 class="mb-0">Informações</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>Notificação push:</strong>
                    <span class="badge {{ $estudo->send_notification ? 'bg-success' : 'bg-secondary' }}">
                        {{ $estudo->send_notification ? 'Ativada' : 'Desativada' }}
                    </span>
                </div>
                @if($estudo->category)
                <div class="mb-2">
                    <strong>Categoria:</strong> {{ $estudo->category }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .study-content {
        line-height: 1.8;
        color: #333;
    }
    .study-content img {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
        margin: 1rem 0;
    }
    .study-content h1, .study-content h2, .study-content h3 {
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }
    .study-content p {
        margin-bottom: 1rem;
    }
    .study-content ul, .study-content ol {
        margin-bottom: 1rem;
        padding-left: 2rem;
    }
    .study-content blockquote {
        border-left: 4px solid #007bff;
        padding-left: 1rem;
        margin: 1rem 0;
        font-style: italic;
        color: #6c757d;
    }
</style>
@endpush
@endsection
