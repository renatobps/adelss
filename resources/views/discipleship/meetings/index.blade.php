@extends('layouts.porto')

@section('title', 'Encontros de Discipulado')

@section('page-title', 'Encontros de Discipulado')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><span>Encontros</span></li>
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
        @if($memberId)
            <a href="{{ route('discipleship.meetings.index') }}" class="btn btn-sm btn-secondary">
                <i class="bx bx-x me-1"></i>Limpar Filtro
            </a>
        @endif
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('discipleship.meetings.create', request()->only('discipleship_member_id')) }}" class="btn btn-primary">
            <i class="bx bx-calendar-plus me-1"></i>Novo Encontro
        </a>
    </div>
</div>

<div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-body">
        @if($meetings->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Membro</th>
                            <th>Ciclo</th>
                            <th>Tipo</th>
                            <th>Assuntos</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meetings as $meeting)
                            <tr>
                                <td>{{ $meeting->data->format('d/m/Y') }}</td>
                                <td>{{ $meeting->discipleshipMember->member->name }}</td>
                                <td>{{ $meeting->discipleshipMember->cycle->nome }}</td>
                                <td>
                                    @if($meeting->tipo === 'presencial')
                                        <span class="badge bg-primary">Presencial</span>
                                    @else
                                        <span class="badge bg-info">Online</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($meeting->assuntos_tratados, 50) ?: '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('discipleship.meetings.show', $meeting) }}" class="btn btn-sm btn-info" title="Visualizar">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    <a href="{{ route('discipleship.meetings.edit', $meeting) }}" class="btn btn-sm btn-primary" title="Editar">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <form action="{{ route('discipleship.meetings.destroy', $meeting) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este encontro?');">
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
                {{ $meetings->links() }}
            </div>
        @else
            <div class="text-center text-muted py-5">
                <i class="bx bx-calendar" style="font-size: 3rem;"></i>
                <p class="mt-2">Nenhum encontro encontrado.</p>
            </div>
        @endif
    </div>
</div>
@endsection
