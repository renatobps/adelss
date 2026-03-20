@extends('layouts.porto')

@section('title', 'Propósitos de Discipulado')

@section('page-title', 'Propósitos de Discipulado')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><span>Propósitos</span></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row mb-3">
    <div class="col-md-6">
        <form method="GET" action="{{ route('discipleship.goals.index') }}" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" style="width: auto;">
                <option value="em_andamento" {{ $status === 'em_andamento' ? 'selected' : '' }}>Em Andamento</option>
                <option value="concluido" {{ $status === 'concluido' ? 'selected' : '' }}>Concluídos</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        </form>
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('discipleship.goals.create', request()->only('discipleship_member_id')) }}" class="btn btn-primary">
            <i class="bx bx-target-lock me-1"></i>Novo Propósito
        </a>
    </div>
</div>

<div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-body">
        @if($goals->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Membro</th>
                            <th>Descrição</th>
                            <th>Tipo</th>
                            <th>Prazo</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($goals as $goal)
                            <tr>
                                <td>{{ $goal->discipleshipMember->member->name }}</td>
                                <td>{{ Str::limit($goal->descricao, 50) }}</td>
                                <td>
                                    @if($goal->tipo === 'espiritual')
                                        <span class="badge bg-primary">Espiritual</span>
                                    @else
                                        <span class="badge bg-info">Material</span>
                                    @endif
                                </td>
                                <td>{{ $goal->prazo ? $goal->prazo->format('d/m/Y') : '-' }}</td>
                                <td>
                                    @if($goal->status === 'concluido')
                                        <span class="badge bg-success">Concluído</span>
                                    @elseif($goal->status === 'pausado')
                                        <span class="badge bg-warning">Pausado</span>
                                    @else
                                        <span class="badge bg-primary">Em Andamento</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('discipleship.goals.show', $goal) }}" class="btn btn-sm btn-info" title="Visualizar">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    <a href="{{ route('discipleship.goals.edit', $goal) }}" class="btn btn-sm btn-primary" title="Editar">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <form action="{{ route('discipleship.goals.destroy', $goal) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este propósito?');">
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
            
            <div class="mt-3">
                {{ $goals->links() }}
            </div>
        @else
            <div class="text-center text-muted py-5">
                <i class="bx bx-target-lock" style="font-size: 3rem;"></i>
                <p class="mt-2">Nenhum propósito encontrado.</p>
            </div>
        @endif
    </div>
</div>
@endsection
