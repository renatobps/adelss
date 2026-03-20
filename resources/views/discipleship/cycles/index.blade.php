@extends('layouts.porto')

@section('title', 'Ciclos de Discipulado')

@section('page-title', 'Ciclos de Discipulado')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><span>Ciclos</span></li>
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
    <div class="col-md-6">
        <div class="btn-group" role="group">
            <a href="{{ route('discipleship.cycles.index', ['status' => 'ativo']) }}" 
               class="btn btn-sm {{ $status === 'ativo' ? 'btn-primary' : 'btn-outline-primary' }}">
                Ativos
            </a>
            <a href="{{ route('discipleship.cycles.index', ['status' => 'encerrado']) }}" 
               class="btn btn-sm {{ $status === 'encerrado' ? 'btn-primary' : 'btn-outline-primary' }}">
                Encerrados
            </a>
        </div>
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('discipleship.cycles.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i>Novo Ciclo
        </a>
    </div>
</div>

<div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-body">
        @if($cycles->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Data Início</th>
                            <th>Data Fim</th>
                            <th>Status</th>
                            <th>Membros</th>
                            <th>Criado por</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cycles as $cycle)
                            <tr>
                                <td>
                                    <strong>{{ $cycle->nome }}</strong>
                                    @if($cycle->descricao)
                                        <br><small class="text-muted">{{ Str::limit($cycle->descricao, 50) }}</small>
                                    @endif
                                </td>
                                <td>{{ $cycle->data_inicio->format('d/m/Y') }}</td>
                                <td>{{ $cycle->data_fim ? $cycle->data_fim->format('d/m/Y') : '-' }}</td>
                                <td>
                                    @if($cycle->status === 'ativo')
                                        <span class="badge bg-success">Ativo</span>
                                    @else
                                        <span class="badge bg-secondary">Encerrado</span>
                                    @endif
                                </td>
                                <td>{{ $cycle->members->count() }}</td>
                                <td>{{ $cycle->creator->name ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('discipleship.cycles.show', $cycle) }}" class="btn btn-sm btn-info" title="Visualizar">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    <a href="{{ route('discipleship.cycles.edit', $cycle) }}" class="btn btn-sm btn-primary" title="Editar">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <form action="{{ route('discipleship.cycles.destroy', $cycle) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este ciclo?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center text-muted py-5">
                <i class="bx bx-cycle" style="font-size: 3rem;"></i>
                <p class="mt-2">Nenhum ciclo encontrado.</p>
            </div>
        @endif
    </div>
</div>
@endsection
