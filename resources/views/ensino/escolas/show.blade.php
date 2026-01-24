@extends('layouts.porto')

@section('title', 'Visualizar Escola')

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.escolas.index') }}">Ensino</a></li>
    <li><a href="{{ route('ensino.escolas.index') }}">Escolas</a></li>
    <li><span>Visualizar</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canEditEscolas = $isAdmin || 
                     ($user && ($user->hasPermission('ensino.escolas.edit') || 
                                $user->hasPermission('ensino.escolas.manage')));
@endphp

<div class="row">
    <!-- Conteúdo Principal -->
    <div class="col-lg-8">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Visualizar Escola</h5>
                <div>
                    @if($canEditEscolas)
                    <a href="{{ route('ensino.escolas.edit', $escola) }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-edit me-1"></i>Editar
                    </a>
                    @endif
                    <a href="{{ route('ensino.escolas.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bx bx-arrow-back me-1"></i>Voltar
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Nome da escola -->
                <div class="mb-4">
                    <h2 class="fw-bold">{{ $escola->name }}</h2>
                    <p class="text-muted small mb-0">
                        <i class="bx bx-calendar me-1"></i>
                        Criado em: {{ $escola->created_at->format('d/m/Y H:i') }}
                        @if($escola->updated_at != $escola->created_at)
                            | Atualizado em: {{ $escola->updated_at->format('d/m/Y H:i') }}
                        @endif
                    </p>
                </div>

                <!-- Descrição -->
                @if($escola->description)
                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Descrição</h5>
                    <p class="text-muted" style="line-height: 1.8;">{{ $escola->description }}</p>
                </div>
                @endif

                <!-- Gestor -->
                @if($escola->manager)
                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Gestor</h5>
                    <div class="d-flex align-items-center">
                        @if($escola->manager->photo_url)
                            <img src="{{ $escola->manager->photo_url }}" 
                                 alt="{{ $escola->manager->name }}" 
                                 class="rounded-circle me-3" 
                                 width="50" 
                                 height="50"
                                 style="object-fit: cover;">
                        @else
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 50px; height: 50px;">
                                <i class="bx bx-user text-white"></i>
                            </div>
                        @endif
                        <div>
                            <strong>{{ $escola->manager->name }}</strong>
                            @if($escola->manager->email)
                                <br><small class="text-muted">{{ $escola->manager->email }}</small>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Turmas -->
                @if($escola->turmas && $escola->turmas->count() > 0)
                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Turmas ({{ $escola->turmas->count() }})</h5>
                    <div class="list-group">
                        @foreach($escola->turmas as $turma)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $turma->name }}</strong>
                                        @if($turma->status)
                                            <span class="badge bg-info ms-2">{{ ucfirst($turma->status) }}</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('ensino.turmas.show', $turma) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-show me-1"></i>Ver
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar Direita -->
    <div class="col-lg-4">
        <!-- Informações -->
        <div class="card mb-3" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h6 class="mb-0">Informações</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>ID:</strong> #{{ $escola->id }}
                </div>
                <div class="mb-2">
                    <strong>Nome:</strong> {{ $escola->name }}
                </div>
                @if($escola->manager)
                <div class="mb-2">
                    <strong>Gestor:</strong> {{ $escola->manager->name }}
                </div>
                @else
                <div class="mb-2">
                    <strong>Gestor:</strong> <span class="text-muted">Não definido</span>
                </div>
                @endif
                <div class="mb-2">
                    <strong>Turmas:</strong> {{ $escola->turmas ? $escola->turmas->count() : 0 }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
