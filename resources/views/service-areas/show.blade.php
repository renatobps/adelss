@extends('layouts.porto')

@section('title', 'Visualizar Área de Serviço')

@section('page-title', $area->name)

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.areas.index') }}">Áreas de Serviço</a></li>
    <li><span>Visualizar</span></li>
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
                    <i class="bx bx-category me-2"></i>{{ $area->name }}
                </h2>
            </header>
            <div class="card-body">
                <div class="d-flex justify-content-end mb-4">
                    <a href="{{ route('voluntarios.areas.edit', $area) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('voluntarios.areas.index') }}" class="btn btn-default">
                        <i class="bx bx-arrow-back me-2"></i>Voltar
                    </a>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <section class="card">
                            <header class="card-header">
                                <h2 class="card-title">Informações da Área</h2>
                            </header>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Nome:</strong><br>
                                    <span>{{ $area->name }}</span>
                                </div>
                                @if($area->description)
                                    <div class="mb-3">
                                        <strong>Descrição:</strong><br>
                                        <span>{{ $area->description }}</span>
                                    </div>
                                @endif
                                <div class="mb-3">
                                    <strong>Status:</strong><br>
                                    @if($area->status == 'ativo')
                                        <span class="badge badge-success">Ativo</span>
                                    @else
                                        <span class="badge badge-secondary">Inativo</span>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <strong>Público Permitido:</strong><br>
                                    @if($area->allowed_audience == 'adulto')
                                        <span class="badge badge-info">Adulto</span>
                                    @elseif($area->allowed_audience == 'jovem')
                                        <span class="badge badge-warning">Jovem</span>
                                    @else
                                        <span class="badge badge-success">Ambos</span>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <strong>Quantidade Mínima:</strong><br>
                                    <span>{{ $area->min_quantity }} pessoa(s)</span>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="col-md-6 mb-4">
                        <section class="card">
                            <header class="card-header">
                                <h2 class="card-title">Responsável</h2>
                            </header>
                            <div class="card-body">
                                @if($area->leader)
                                    <div class="d-flex align-items-center">
                                        @if($area->leader->photo_url)
                                            <img src="{{ $area->leader->photo_url }}" 
                                                 alt="{{ $area->leader->name }}" 
                                                 class="rounded-circle me-3" 
                                                 width="60" 
                                                 height="60"
                                                 style="object-fit: cover;">
                                        @else
                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="bx bx-user text-white" style="font-size: 2rem;"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h5 class="mb-0">{{ $area->leader->name }}</h5>
                                            <p class="text-muted mb-0">{{ $area->leader->email ?? '-' }}</p>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted mb-0">Nenhum responsável definido</p>
                                @endif
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
