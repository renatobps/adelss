@extends('layouts.porto')

@section('title', 'Indicadores de Discipulado')

@section('page-title', 'Indicadores de Discipulado')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><span>Indicadores</span></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row mb-3">
    <div class="col-md-12 text-end">
        <a href="{{ route('discipleship.indicators.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i>Novo Indicador
        </a>
    </div>
</div>

<div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-body">
        @if($indicators->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Ordem</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($indicators as $indicator)
                            <tr>
                                <td>
                                    <strong>{{ $indicator->nome }}</strong>
                                </td>
                                <td>
                                    @if($indicator->tipo === 'espiritual')
                                        <span class="badge bg-primary">Espiritual</span>
                                    @else
                                        <span class="badge bg-info">Material</span>
                                    @endif
                                </td>
                                <td>
                                    @if($indicator->ativo)
                                        <span class="badge bg-success">Ativo</span>
                                    @else
                                        <span class="badge bg-secondary">Inativo</span>
                                    @endif
                                </td>
                                <td>{{ $indicator->order }}</td>
                                <td class="text-end">
                                    <a href="{{ route('discipleship.indicators.edit', $indicator) }}" class="btn btn-sm btn-primary" title="Editar">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <form action="{{ route('discipleship.indicators.destroy', $indicator) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este indicador?');">
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
                <i class="bx bx-bar-chart" style="font-size: 3rem;"></i>
                <p class="mt-2">Nenhum indicador cadastrado.</p>
            </div>
        @endif
    </div>
</div>
@endsection
