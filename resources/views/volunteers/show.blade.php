@extends('layouts.porto')

@section('title', 'Visualizar Voluntário')

@section('page-title', $volunteer->member->name)

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.cadastro.index') }}">Cadastro de Voluntários</a></li>
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
                    <i class="bx bx-user me-2"></i>{{ $volunteer->member->name }}
                </h2>
            </header>
            <div class="card-body">
                <div class="d-flex justify-content-end mb-4">
                    <a href="{{ route('voluntarios.cadastro.edit', $volunteer) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('voluntarios.cadastro.index') }}" class="btn btn-default">
                        <i class="bx bx-arrow-back me-2"></i>Voltar
                    </a>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <section class="card">
                            <header class="card-header">
                                <h2 class="card-title">Informações do Voluntário</h2>
                            </header>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Membro:</strong><br>
                                    <span>{{ $volunteer->member->name }}</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Nível de Experiência:</strong><br>
                                    @if($volunteer->experience_level == 'novo')
                                        <span class="badge badge-warning">Novo</span>
                                    @elseif($volunteer->experience_level == 'em_treinamento')
                                        <span class="badge badge-info">Em Treinamento</span>
                                    @else
                                        <span class="badge badge-success">Experiente</span>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <strong>Status:</strong><br>
                                    @if($volunteer->status == 'ativo')
                                        <span class="badge badge-success">Ativo</span>
                                    @else
                                        <span class="badge badge-secondary">Inativo</span>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <strong>Data de Início:</strong><br>
                                    <span>{{ $volunteer->start_date->format('d/m/Y') }}</span>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="col-md-6 mb-4">
                        <section class="card">
                            <header class="card-header">
                                <h2 class="card-title">Áreas de Serviço</h2>
                            </header>
                            <div class="card-body">
                                @if($volunteer->serviceAreas->count() > 0)
                                    @foreach($volunteer->serviceAreas as $area)
                                        <span class="badge badge-info me-1 mb-2">{{ $area->name }}</span>
                                    @endforeach
                                @else
                                    <p class="text-muted mb-0">Nenhuma área de serviço definida</p>
                                @endif
                            </div>
                        </section>
                    </div>
                </div>

                @if($volunteer->leader_notes)
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <section class="card">
                                <header class="card-header">
                                    <h2 class="card-title">Observações do Líder</h2>
                                </header>
                                <div class="card-body">
                                    <p>{{ $volunteer->leader_notes }}</p>
                                </div>
                            </section>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
