<div class="sf-success-card">
    <div class="sf-success-icon">✓</div>
    <h2 class="sf-section-title">Resultado da prova</h2>
    <p>{{ $submission->respondent_name }}, suas respostas foram registradas.</p>
    @if($submission->max_score > 0)
        <p style="font-size:2rem;font-weight:700;margin:.5rem 0">Nota: {{ number_format(10 * $submission->score / $submission->max_score, 2, ',', '.') }} / 10</p>
        <p>{{ $submission->score }} de {{ $submission->max_score }} itens objetivos corretos ({{ number_format(100 * $submission->score / $submission->max_score, 1, ',', '.') }}%).</p>
    @else
        <p>Esta prova não possui itens com correção automática.</p>
    @endif
    @if($submission->answers->contains(fn ($answer) => $answer->is_correct === null))
        <p>As respostas abertas e os itens sem gabarito automático ficam fora da nota. Compare com a referência e solicite a revisão do professor.</p>
    @endif
    <a class="sf-btn sf-btn-primary" href="{{ $formulario->publicUrl() }}">Refazer prova</a>
    @if($formulario->study)
        <a class="sf-btn sf-btn-ghost" href="{{ route('ensino.estudos.show', $formulario->study) }}">Voltar ao curso</a>
    @endif
</div>

<h2 class="sf-section-title" style="margin-top:2rem">Suas respostas</h2>
@foreach($submission->answers as $answer)
    @php $question = $answer->question; @endphp
    @continue(!$question)
    <div class="sf-question" style="border-left:4px solid {{ $answer->is_correct === true ? 'var(--accent-deep)' : ($answer->is_correct === false ? 'var(--danger)' : 'var(--line)') }};padding-left:1rem">
        <p class="sf-q-text">{{ $question->prompt }}</p>
        <p><strong>{{ $answer->is_correct === true ? 'Correta' : ($answer->is_correct === false ? 'Incorreta' : 'Revisão do professor') }}</strong></p>
        <p>Sua resposta: {{ $question->answerLabel($answer->answer_text ?? $answer->selected_option) }}</p>
        @if($answer->is_correct === false || ($answer->is_correct === null && filled($question->correct_answer)))
            <a href="#resposta-correta-{{ $answer->id }}">{{ $answer->is_correct === false ? 'Ver resposta certa' : 'Ver resposta de referência' }}</a>
        @endif
        @if($question->review_required)
            <p class="sf-desc">Gabarito marcado para revisão no material original. Confirme com o professor.</p>
        @endif
    </div>
@endforeach

@php $corrections = $submission->answers->filter(fn ($answer) => $answer->is_correct !== true && filled($answer->question?->correct_answer)); @endphp
@if($corrections->isNotEmpty())
    <h2 class="sf-section-title" style="margin-top:2rem">Respostas e referências</h2>
    @foreach($corrections as $answer)
        <section class="sf-question" id="resposta-correta-{{ $answer->id }}" tabindex="-1" style="scroll-margin-top:2rem">
            <p class="sf-q-text">{{ $answer->question->prompt }}</p>
            <p><strong>{{ $answer->is_correct === false ? 'Resposta correta' : 'Resposta de referência' }}:</strong> {{ $answer->question->answerLabel($answer->question->correct_answer) }}</p>
            @if($answer->question->review_required)
                <p>Gabarito original pendente de revisão pelo professor.</p>
            @endif
            @if($formulario->materialUrl())
                <a href="{{ $formulario->materialUrl() }}" target="_blank" rel="noopener">Consultar apostila da disciplina (PDF)</a>
            @endif
        </section>
    @endforeach
@endif
