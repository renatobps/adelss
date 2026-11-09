@if($submission && $answer)
    @if($answer->is_correct === true)
        <span class="ib-status ok">correto</span>
    @elseif($answer->is_correct === false)
        <span class="ib-status error">incorreto</span>
    @else
        <span class="ib-status">Revisão do professor</span>
    @endif
    @if($question->review_required)
        <span class="ib-review">gabarito a revisar</span>
    @endif
    @if($answer->is_correct !== true || $question->review_required)
        <button class="ib-verify" type="button" data-verify-target="verification-{{ $question->id }}" aria-controls="verification-{{ $question->id }}" aria-expanded="false">Verificar</button>
        <div class="ib-feedback" id="verification-{{ $question->id }}" hidden>
            @php $bookExcerpt = $bookExcerpts[$question->id] ?? null; @endphp
            @if($bookExcerpt)
                <blockquote class="ib-book-excerpt">{{ $bookExcerpt['text'] }}</blockquote>
                <cite class="ib-book-source">{{ $bookExcerpt['book'] }} · página {{ $bookExcerpt['page'] }} do PDF</cite>
            @else
                <p>Ainda não há um trecho do livro associado com segurança a este item.</p>
            @endif
        </div>
    @endif
@endif
