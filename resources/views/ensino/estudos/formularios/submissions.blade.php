@extends('layouts.porto')

@section('title', 'Respostas do formulário')

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.estudos.index') }}">Ensino</a></li>
    <li><a href="{{ route('ensino.estudos.formularios.show', [$estudo, $formulario]) }}">{{ $formulario->title }}</a></li>
    <li><span>Respostas</span></li>
@endsection

@section('content')
<div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Respostas</h5>
            <small class="text-muted">{{ $formulario->title }}</small>
        </div>
        <a href="{{ route('ensino.estudos.formularios.show', [$estudo, $formulario]) }}" class="btn btn-secondary btn-sm">Voltar</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Enviado em</th>
                        <th>Nota (objetivas)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $submission)
                        <tr>
                            <td class="fw-semibold">{{ $submission->respondent_name }}</td>
                            <td>{{ $submission->submitted_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($submission->max_score)
                                    {{ $submission->score }}/{{ $submission->max_score }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('ensino.estudos.formularios.submissions.show', [$estudo, $formulario, $submission->id]) }}"
                                   class="btn btn-sm btn-info">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Nenhuma resposta recebida.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($submissions->hasPages())
            <div class="mt-3">{{ $submissions->links() }}</div>
        @endif
    </div>
</div>
@endsection
