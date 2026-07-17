@extends('layouts.porto')

@section('title', $formulario->title)

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.estudos.index') }}">Ensino</a></li>
    <li><a href="{{ route('ensino.estudos.show', $estudo) }}">{{ $estudo->name }}</a></li>
    <li><a href="{{ route('ensino.estudos.formularios.index', $estudo) }}">Formulários</a></li>
    <li><span>{{ $formulario->title }}</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canManage = $isAdmin || ($user && ($user->hasPermission('ensino.estudos.edit') || $user->hasPermission('ensino.estudos.manage')));
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $formulario->title }}</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('ensino.estudos.formularios.pdf', [$estudo, $formulario]) }}" class="btn btn-outline-danger btn-sm" target="_blank">
                        <i class="bx bx-file me-1"></i>PDF
                    </a>
                    <a href="{{ route('ensino.estudos.formularios.pdf', [$estudo, $formulario, 'gabarito' => 1]) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
                        <i class="bx bx-check-shield me-1"></i>PDF Gabarito
                    </a>
                    @if($canManage)
                    <a href="{{ route('ensino.estudos.formularios.edit', [$estudo, $formulario]) }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-edit me-1"></i>Editar
                    </a>
                    @endif
                    <a href="{{ route('ensino.estudos.formularios.submissions', [$estudo, $formulario]) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bx bx-list-ul me-1"></i>Respostas ({{ $formulario->submissions_count }})
                    </a>
                    <a href="{{ route('ensino.estudos.formularios.index', $estudo) }}" class="btn btn-secondary btn-sm">Voltar</a>
                </div>
            </div>
            <div class="card-body">
                @if($formulario->description)
                    <p class="text-muted">{{ $formulario->description }}</p>
                @endif

                @php $lastTheme = null; @endphp
                @foreach($formulario->questions as $i => $question)
                    @if($question->theme && $question->theme !== $lastTheme)
                        <div class="alert alert-success py-2 mb-3">
                            <strong>Tema:</strong> {{ $question->theme }}
                        </div>
                        @php $lastTheme = $question->theme; @endphp
                    @endif
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>{{ $i + 1 }}. {{ $question->prompt }}</strong>
                            <span class="badge bg-secondary">{{ $question->typeLabel() }}</span>
                        </div>
                        @if($question->theme)
                            <div class="small text-success mb-2">{{ $question->theme }}</div>
                        @endif

                        @if($question->type === 'multiple_choice')
                            <ul class="mb-0">
                                @foreach($question->options ?? [] as $opt)
                                    <li>
                                        <strong>{{ strtoupper($opt['key']) }})</strong> {{ $opt['text'] }}
                                        @if($question->correct_answer === $opt['key'])
                                            <span class="text-success">✓</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @elseif($question->type === 'true_false')
                            <div class="text-muted">
                                Gabarito:
                                @if($question->correct_answer === 'true') Verdadeiro
                                @elseif($question->correct_answer === 'false') Falso
                                @else — @endif
                            </div>
                        @elseif($question->correct_answer)
                            <div class="text-muted small">Referência: {{ $question->correct_answer }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header"><h6 class="mb-0">Link público</h6></div>
            <div class="card-body">
                <div class="input-group mb-2">
                    <input type="text" class="form-control form-control-sm" readonly value="{{ $formulario->publicUrl() }}" id="public-url">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-copy-url">
                        <i class="bx bx-copy"></i>
                    </button>
                </div>
                <a href="{{ $formulario->publicUrl() }}" target="_blank" class="btn btn-outline-primary btn-sm w-100 mb-2">
                    <i class="bx bx-link-external me-1"></i>Abrir formulário
                </a>
                <a href="{{ route('ensino.estudos.formularios.pdf', [$estudo, $formulario]) }}" target="_blank" class="btn btn-outline-danger btn-sm w-100">
                    <i class="bx bx-download me-1"></i>Baixar PDF
                </a>
                <div class="mt-3">
                    <span class="badge {{ $formulario->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $formulario->is_active ? 'Ativo' : 'Inativo' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header"><h6 class="mb-0">Últimas respostas</h6></div>
            <div class="card-body">
                @forelse($formulario->submissions as $submission)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <a href="{{ route('ensino.estudos.formularios.submissions.show', [$estudo, $formulario, $submission->id]) }}">
                                {{ $submission->respondent_name }}
                            </a>
                            <div class="small text-muted">{{ $submission->submitted_at?->format('d/m/Y H:i') }}</div>
                        </div>
                        @if($submission->max_score)
                            <span class="badge bg-info">{{ $submission->score }}/{{ $submission->max_score }}</span>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">Nenhuma resposta ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('btn-copy-url')?.addEventListener('click', async () => {
    const url = document.getElementById('public-url').value;
    try {
        await navigator.clipboard.writeText(url);
        alert('Link copiado!');
    } catch (e) {
        prompt('Copie o link:', url);
    }
});
</script>
@endpush
