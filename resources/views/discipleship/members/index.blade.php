@extends('layouts.porto')

@section('title', 'Membros em Discipulado')

@section('page-title', 'Membros em Discipulado')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><span>Membros</span></li>
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
        <form method="GET" action="{{ route('discipleship.members.index') }}" class="d-flex gap-2">
            <select name="cycle_id" class="form-select form-select-sm" style="width: auto;">
                <option value="">Todos os ciclos</option>
                @foreach($cycles as $cycle)
                    <option value="{{ $cycle->id }}" {{ request('cycle_id') == $cycle->id ? 'selected' : '' }}>
                        {{ $cycle->nome }}
                    </option>
                @endforeach
            </select>
            <select name="status" class="form-select form-select-sm" style="width: auto;">
                <option value="ativo" {{ $status === 'ativo' ? 'selected' : '' }}>Ativos</option>
                <option value="concluido" {{ $status === 'concluido' ? 'selected' : '' }}>Concluídos</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        </form>
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('discipleship.members.create', request()->only('cycle_id')) }}" class="btn btn-primary">
            <i class="bx bx-user-plus me-1"></i>Vincular Membro
        </a>
    </div>
</div>

<div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-body">
        @if($members->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Membro</th>
                            <th>Ciclo</th>
                            <th>Discipulador</th>
                            <th>Status</th>
                            <th>Data Início</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $member)
                            <tr>
                                <td>
                                    <strong>{{ $member->member->name }}</strong>
                                </td>
                                <td>{{ $member->cycle->nome }}</td>
                                <td>{{ $member->discipulador->name ?? '-' }}</td>
                                <td>
                                    @if($member->status === 'ativo')
                                        <span class="badge bg-success">Ativo</span>
                                    @elseif($member->status === 'concluido')
                                        <span class="badge bg-info">Concluído</span>
                                    @else
                                        <span class="badge bg-warning">Pausado</span>
                                    @endif
                                </td>
                                <td>{{ $member->data_inicio->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('discipleship.members.show', $member) }}" class="btn btn-sm btn-info" title="Visualizar">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    <a href="{{ route('discipleship.members.edit', $member) }}" class="btn btn-sm btn-primary" title="Editar">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <form action="{{ route('discipleship.members.destroy', $member) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover este vínculo?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Remover">
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
                {{ $members->links() }}
            </div>
        @else
            <div class="text-center text-muted py-5">
                <i class="bx bx-user" style="font-size: 3rem;"></i>
                <p class="mt-2">Nenhum membro encontrado.</p>
            </div>
        @endif
    </div>
</div>
@endsection
