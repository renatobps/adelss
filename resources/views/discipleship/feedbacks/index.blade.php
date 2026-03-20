@extends('layouts.porto')

@section('title', 'Feedbacks de Discipulado')

@section('page-title', 'Feedbacks de Discipulado')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><span>Feedbacks</span></li>
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
        <a href="{{ route('discipleship.feedbacks.create', request()->only('discipleship_member_id')) }}" class="btn btn-primary">
            <i class="bx bx-message-add me-1"></i>Novo Feedback
        </a>
    </div>
</div>

<div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-body">
        @if($feedbacks->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Membro</th>
                            <th>Autor</th>
                            <th>Visibilidade</th>
                            <th>Conteúdo</th>
                            <th>Data</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($feedbacks as $feedback)
                            <tr>
                                <td>{{ $feedback->discipleshipMember->member->name }}</td>
                                <td>{{ $feedback->autor->name }}</td>
                                <td>
                                    @if($feedback->visibilidade === 'admin')
                                        <span class="badge bg-danger">Admin</span>
                                    @elseif($feedback->visibilidade === 'pastor')
                                        <span class="badge bg-warning">Pastor</span>
                                    @else
                                        <span class="badge bg-info">Discipulador</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($feedback->conteudo, 50) }}</td>
                                <td>{{ $feedback->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('discipleship.feedbacks.edit', $feedback) }}" class="btn btn-sm btn-primary" title="Editar">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <form action="{{ route('discipleship.feedbacks.destroy', $feedback) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este feedback?');">
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
                {{ $feedbacks->links() }}
            </div>
        @else
            <div class="text-center text-muted py-5">
                <i class="bx bx-message" style="font-size: 3rem;"></i>
                <p class="mt-2">Nenhum feedback encontrado.</p>
            </div>
        @endif
    </div>
</div>
@endsection
