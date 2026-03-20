@extends('layouts.porto')

@section('title', 'Enquetes - Notificações')
@section('page-title', 'Enquetes')
@section('breadcrumbs')
    <li><a href="{{ route('notificacoes.enquetes.index') }}">Notificações</a></li>
    <li><span>Enquetes</span></li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bx bx-bar-chart-alt-2 me-2"></i>Enquetes</h1>
    <a href="{{ route('notificacoes.enquetes.create') }}" class="btn btn-primary">
        <i class="bx bx-plus-circle me-1"></i> Nova Enquete
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<section class="card">
    <header class="card-header">
        <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>Lista de Enquetes</h5>
    </header>
    <div class="card-body">
        @if($enquetes->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Respostas</th>
                            <th>Criada em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enquetes as $enquete)
                            <tr>
                                <td>
                                    <strong>{{ $enquete->titulo }}</strong>
                                    @if($enquete->descricao)
                                        <br><small class="text-muted">{{ Str::limit($enquete->descricao, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary"><i class="bx bx-bar-chart me-1"></i> Poll</span>
                                </td>
                                <td>
                                    @if($enquete->ativa)
                                        <span class="badge bg-success">Ativa</span>
                                    @else
                                        <span class="badge bg-secondary">Inativa</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $enquete->respostas_count }} respostas</span>
                                </td>
                                <td>{{ $enquete->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('notificacoes.enquetes.show', $enquete) }}" class="btn btn-sm btn-outline-primary" title="Ver">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <a href="{{ route('notificacoes.enquetes.edit', $enquete) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <form action="{{ route('notificacoes.enquetes.destroy', $enquete) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta enquete?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $enquetes->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bx bx-bar-chart-alt-2" style="font-size: 4rem; color: #ccc;"></i>
                <h3 class="text-muted mt-3">Nenhuma enquete encontrada</h3>
                <p class="text-muted">Crie sua primeira enquete para começar a coletar dados dos membros.</p>
                <a href="{{ route('notificacoes.enquetes.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus-circle me-1"></i> Criar Primeira Enquete
                </a>
            </div>
        @endif
    </div>
</section>
@endsection
