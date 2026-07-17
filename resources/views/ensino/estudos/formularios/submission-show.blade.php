@extends('layouts.porto')

@section('title', 'Resposta de '.$submission->respondent_name)

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.estudos.formularios.submissions', [$estudo, $formulario]) }}">Respostas</a></li>
    <li><span>{{ $submission->respondent_name }}</span></li>
@endsection

@section('content')
<div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">{{ $submission->respondent_name }}</h5>
            <small class="text-muted">
                Enviado em {{ $submission->submitted_at?->format('d/m/Y H:i') }}
                @if($submission->max_score)
                    · Nota: {{ $submission->score }}/{{ $submission->max_score }}
                @endif
            </small>
        </div>
        <a href="{{ route('ensino.estudos.formularios.submissions', [$estudo, $formulario]) }}" class="btn btn-secondary btn-sm">Voltar</a>
    </div>
    <div class="card-body">
        @foreach($submission->answers as $answer)
            @php $question = $answer->question; @endphp
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <strong>{{ $question?->prompt }}</strong>
                    @if($answer->is_correct === true)
                        <span class="badge bg-success">Correta</span>
                    @elseif($answer->is_correct === false)
                        <span class="badge bg-danger">Incorreta</span>
                    @else
                        <span class="badge bg-secondary">Dissertativa</span>
                    @endif
                </div>

                @if($question?->type === 'dissertative')
                    <div class="bg-light rounded p-2">{{ $answer->answer_text ?: '—' }}</div>
                    @if($question->correct_answer)
                        <div class="small text-muted mt-2">Referência: {{ $question->correct_answer }}</div>
                    @endif
                @elseif($question?->type === 'true_false')
                    <div>
                        Resposta:
                        <strong>
                            {{ $answer->selected_option === 'true' ? 'Verdadeiro' : ($answer->selected_option === 'false' ? 'Falso' : '—') }}
                        </strong>
                    </div>
                @else
                    @php
                        $optText = collect($question?->options ?? [])
                            ->firstWhere('key', $answer->selected_option)['text'] ?? $answer->selected_option;
                    @endphp
                    <div>Resposta: <strong>{{ $optText ?: '—' }}</strong></div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
