@extends('layouts.porto')

@section('title', 'Grupos - Notificações')
@section('page-title', 'Grupos')
@section('breadcrumbs')
    <li><a href="{{ route('notificacoes.grupos.index') }}">Notificações</a></li>
    <li><span>Grupos</span></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row mb-3">
    <div class="col-md-6"></div>
    <div class="col-md-6 text-end">
        <a href="{{ route('notificacoes.grupos.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i>Novo Grupo
        </a>
    </div>
</div>

<section class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Membros</th>
                        <th>Ativo</th>
                        <th>Criado em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grupos as $grupo)
                        <tr>
                            <td>
                                <strong>{{ $grupo->nome }}</strong>
                                @if($grupo->descricao)
                                    <br><small class="text-muted">{{ Str::limit($grupo->descricao, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $grupo->members_count }} membro(s)</span>
                                @if($grupo->members_count > 0)
                                    <button type="button" class="btn btn-sm btn-link p-0 ms-1" data-bs-toggle="collapse" data-bs-target="#membros-{{ $grupo->id }}" title="Ver membros">
                                        <i class="bx bx-show"></i>
                                    </button>
                                    <div class="collapse mt-2" id="membros-{{ $grupo->id }}">
                                        <small>
                                            @foreach($grupo->members as $m)
                                                <span class="badge bg-secondary me-1 mb-1">{{ $m->name }}</span>
                                            @endforeach
                                        </small>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($grupo->ativo)
                                    <span class="badge bg-success">Ativo</span>
                                @else
                                    <span class="badge bg-secondary">Inativo</span>
                                @endif
                            </td>
                            <td>{{ $grupo->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('notificacoes.grupos.edit', $grupo) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bx bx-edit"></i>
                                </a>
                                <form action="{{ route('notificacoes.grupos.destroy', $grupo) }}" method="POST" class="d-inline" onsubmit="return confirm('Excluir este grupo?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">Nenhum grupo cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($grupos->hasPages())
        <div class="card-footer">{{ $grupos->links() }}</div>
    @endif
</section>
@endsection
