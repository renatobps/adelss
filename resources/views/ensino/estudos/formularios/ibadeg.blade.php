<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $formulario->title }} — IBADEG</title>
    <style>
        :root { --bg:#f4f1ea; --card:#fff; --ink:#2b2620; --muted:#6b6355; --accent:#7a3b2e; --ok:#2e7d4f; --err:#b3402f; --border:#e0d9c9; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Georgia,'Times New Roman',serif; background:var(--bg); color:var(--ink); line-height:1.5; }
        a { color:var(--accent); }
        header { background:var(--accent); color:white; padding:18px 24px; }
        header .ib-heading { max-width:900px; margin:auto; display:flex; align-items:center; justify-content:space-between; gap:16px; }
        header h1 { margin:0; font-size:1.25rem; }
        header a { color:white; white-space:nowrap; }
        main { max-width:900px; margin:auto; padding:24px 16px 70px; }
        .ib-intro,.ib-question,.ib-score { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:18px 20px; margin-bottom:16px; }
        .ib-intro h2 { margin:0 0 4px; font-size:1.4rem; }
        .ib-meta { color:var(--muted); font-size:.9rem; }
        .ib-links,.ib-actions { display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-top:14px; }
        .ib-name { display:block; margin-top:16px; }
        .ib-input { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:6px; font:inherit; background:white; color:var(--ink); }
        .ib-name .ib-input { max-width:480px; display:block; margin-top:5px; }
        .ib-instruction { margin:0 0 12px; font-size:1rem; font-weight:normal; }
        .ib-item { display:flex; align-items:baseline; gap:10px; padding:8px 0; border-top:1px dashed var(--border); }
        .ib-letter { font-weight:bold; color:var(--muted); flex-shrink:0; }
        .ib-item-text { flex:1; min-width:0; overflow-wrap:anywhere; }
        .ib-ce { display:flex; gap:6px; flex-shrink:0; }
        .ib-ce label { position:relative; cursor:pointer; }
        .ib-ce input { position:absolute; opacity:0; width:1px; height:1px; }
        .ib-ce span { display:block; min-width:36px; text-align:center; border:1px solid var(--border); border-radius:6px; padding:2px 10px; background:white; color:var(--muted); }
        .ib-ce input:checked + span { background:#e9f3ec; color:var(--ok); border-color:var(--ok); }
        .ib-ce .ib-e input:checked + span { background:#fbece8; color:var(--err); border-color:var(--err); }
        .ib-ce input:focus-visible + span { outline:2px solid var(--accent); outline-offset:3px; }
        .ib-ce input:disabled + span { cursor:default; }
        .ib-choice { display:flex; align-items:baseline; gap:8px; padding:7px 0; }
        .ib-choice input { accent-color:var(--accent); }
        .ib-choice.is-correct { color:var(--ok); }
        .ib-choice.is-incorrect { color:var(--err); }
        .ib-match-key { background:#f8f6f0; border-radius:6px; padding:10px 14px; margin-bottom:12px; }
        .ib-select { font:inherit; border:1px solid var(--border); border-radius:6px; background:white; padding:4px; max-width:110px; }
        .ib-fill { margin:8px 0; }
        .ib-status,.ib-review { display:inline-block; font-size:.78rem; border-radius:12px; padding:1px 7px; margin-left:5px; }
        .ib-status.ok { background:#e9f3ec; color:var(--ok); }
        .ib-status.error { background:#fbece8; color:var(--err); }
        .ib-review { background:#fff8e6; color:#805500; }
        .ib-verify { font:inherit; font-size:.78rem; background:#fff8e6; color:#805500; border:1px solid #e7c974; border-radius:8px; padding:0 7px; margin-left:5px; cursor:pointer; }
        .ib-verify:focus-visible,.ib-btn:focus-visible { outline:2px solid var(--accent); outline-offset:3px; }
        .ib-feedback { background:#f8f6f0; border-left:3px solid var(--accent); padding:8px 12px; margin:8px 0 0; }
        .ib-feedback p { margin:4px 0; }
        .ib-book-excerpt { margin:0 0 6px; white-space:pre-line; }
        .ib-book-source { display:block; color:var(--muted); font-size:.8rem; font-style:normal; }
        .ib-score { text-align:center; border-color:var(--ok); }
        .ib-score h2 { margin:0; }
        .ib-grade { font-size:2rem; font-weight:bold; color:var(--ok); margin:8px 0; }
        .ib-btn { display:inline-block; background:var(--accent); color:white; border:1px solid var(--accent); border-radius:7px; padding:10px 18px; cursor:pointer; font:inherit; text-decoration:none; }
        .ib-btn.secondary { background:white; color:var(--accent); }
        .ib-errors { border:1px solid var(--err); border-radius:8px; background:#fbece8; padding:12px 18px; margin-bottom:16px; }
        [hidden] { display:none !important; }
        @media(max-width:520px) {
            header { padding:14px 16px; }
            header .ib-heading { align-items:flex-start; }
            main { padding:16px 10px 45px; }
            .ib-intro,.ib-question { padding:14px; }
            .ib-item { gap:7px; }
            .ib-ce span { min-width:32px; padding:3px 8px; }
            .ib-select { max-width:85px; }
        }
    </style>
</head>
<body>
<header>
    <div class="ib-heading">
        <h1>IBADEG — Provas Interativas</h1>
        <a href="{{ route('ensino.estudos.show', $formulario->study) }}">Voltar ao curso</a>
    </div>
</header>
<main>
    <div class="ib-intro">
        <h2>{{ $formulario->title }}</h2>
        <div class="ib-meta">{{ count($groups) }} questões · Marque suas respostas e clique em “Corrigir Prova” ao final.</div>
        <div class="ib-links">
            @if($formulario->materialUrl())
                <a href="{{ $formulario->materialUrl() }}" target="_blank" rel="noopener">Ler apostila (PDF)</a>
            @endif
            @if($formulario->examPdfUrl())
                <a href="{{ $formulario->examPdfUrl() }}" target="_blank" rel="noopener">Prova original (PDF)</a>
            @endif
        </div>
        @if(!$submission)
            <p class="ib-meta">Cada item objetivo vale um ponto. As questões abertas ficam fora da nota automática e apresentam referências quando disponíveis.</p>
        @endif
    </div>

    @if($submission)
        <section class="ib-score" aria-label="Resultado da prova">
            <h2>Resultado da prova</h2>
            <p>{{ $submission->respondent_name }}, suas respostas foram registradas.</p>
            @if($submission->max_score > 0)
                <div class="ib-grade">Nota: {{ number_format(10 * $submission->score / $submission->max_score, 2, ',', '.') }} / 10</div>
                <p>{{ $submission->score }} de {{ $submission->max_score }} itens objetivos corretos ({{ number_format(100 * $submission->score / $submission->max_score, 1, ',', '.') }}%).</p>
            @else
                <p>Esta prova não possui itens com correção automática.</p>
            @endif
            <p class="ib-meta">Nos erros, clique em <strong>Verificar</strong> para ler o trecho correspondente do livro.</p>
            @if($submission->answers->contains(fn ($answer) => $answer->is_correct === null))
                <p class="ib-meta">Respostas abertas ficam fora da nota automática. Compare com a referência e solicite a revisão do professor.</p>
            @endif
        </section>
    @elseif($errors->any())
        <div class="ib-errors" role="alert">
            <strong>Confira os campos:</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @php $answersByQuestion = $submission?->answers->keyBy('question_id') ?? collect(); @endphp
    <form method="POST" action="{{ route('study.forms.public.submit', $formulario->public_slug) }}" id="ibadeg-form">
        @csrf
        @if(!$submission)
            <div class="ib-intro">
                <label class="ib-name" for="respondent_name">Seu nome
                    <input class="ib-input" type="text" id="respondent_name" name="respondent_name" value="{{ old('respondent_name') }}" maxlength="255" autocomplete="name" required>
                </label>
            </div>
        @endif

        @foreach($groups as $group)
            <section class="ib-question" aria-labelledby="instruction-{{ $loop->index }}">
                <h3 class="ib-instruction" id="instruction-{{ $loop->index }}">{{ $group['instruction'] }}</h3>
                @if($group['kind'] === 'match')
                    <div class="ib-match-key">
                        @foreach($group['items'][0]['question']->options ?? [] as $option)
                            <div>{{ $option['text'] }}</div>
                        @endforeach
                    </div>
                @endif

                @foreach($group['items'] as $item)
                    @php
                        $question = $item['question'];
                        $answer = $answersByQuestion->get($question->id);
                        $value = $submission ? ($answer?->answer_text ?? $answer?->selected_option) : old('answers.'.$question->id);
                    @endphp
                    @if($group['kind'] === 'ce')
                        <div class="ib-item">
                            <span class="ib-letter">{{ $item['label'] }}</span>
                            <div class="ib-item-text">
                                {{ $item['text'] }}
                                @include('ensino.estudos.formularios.ibadeg-feedback')
                            </div>
                            <div class="ib-ce" role="group" aria-label="{{ $item['label'].' '.$item['text'] }}">
                                @foreach($question->options as $option)
                                    <label class="{{ $option['key'] === 'E' ? 'ib-e' : 'ib-c' }}">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option['key'] }}" aria-label="{{ $option['text'] }}"
                                               @checked($value === $option['key']) @disabled($submission !== null) @required($question->is_required && !$submission)>
                                        <span>{{ $option['key'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @elseif($group['kind'] === 'match')
                        <div class="ib-item">
                            <select class="ib-select" name="answers[{{ $question->id }}]" aria-label="Associar: {{ $item['text'] }}"
                                    @disabled($submission !== null) @required($question->is_required && !$submission)>
                                <option value="">Selecione</option>
                                @foreach($question->options as $option)
                                    <option value="{{ $option['key'] }}" @selected((string) $value === (string) $option['key'])>{{ $option['key'] }}</option>
                                @endforeach
                            </select>
                            <div class="ib-item-text">{{ $item['text'] }}
                                @include('ensino.estudos.formularios.ibadeg-feedback')
                            </div>
                        </div>
                    @elseif($question->type === 'dissertative')
                        <div class="ib-fill">
                            @if($item['text'])<label for="answer-{{ $question->id }}"><strong>{{ $item['label'] }}</strong> {{ $item['text'] }}</label>@endif
                            <textarea class="ib-input" id="answer-{{ $question->id }}" name="answers[{{ $question->id }}]" rows="2" aria-label="{{ $item['text'] ?: $group['instruction'] }}"
                                      @disabled($submission !== null) @required($question->is_required && !$submission)>{{ $value }}</textarea>
                            @include('ensino.estudos.formularios.ibadeg-feedback')
                        </div>
                    @else
                        @php $options = $question->type === 'true_false' ? [['key' => 'true', 'text' => 'Verdadeiro'], ['key' => 'false', 'text' => 'Falso']] : ($question->options ?? []); @endphp
                        @foreach($options as $option)
                            <label class="ib-choice {{ $submission && $option['key'] === $question->correct_answer ? 'is-correct' : ($submission && $value === $option['key'] ? 'is-incorrect' : '') }}">
                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option['key'] }}"
                                       @checked($value === $option['key']) @disabled($submission !== null) @required($question->is_required && !$submission)>
                                <span>{{ $option['text'] }}</span>
                            </label>
                        @endforeach
                        @include('ensino.estudos.formularios.ibadeg-feedback')
                    @endif
                @endforeach
            </section>
        @endforeach

        <div class="ib-actions">
            @if($submission)
                <a class="ib-btn" href="{{ $formulario->publicUrl() }}">Tentar novamente</a>
            @else
                <button class="ib-btn" type="submit">Corrigir Prova</button>
            @endif
            <a class="ib-btn secondary" href="{{ route('ensino.estudos.show', $formulario->study) }}">Voltar ao curso</a>
        </div>
    </form>
</main>
@if($submission)
<script>
document.querySelectorAll('[data-verify-target]').forEach(button => {
    button.addEventListener('click', () => {
        const feedback = document.getElementById(button.dataset.verifyTarget);
        feedback.hidden = !feedback.hidden;
        button.setAttribute('aria-expanded', String(!feedback.hidden));
    });
});
</script>
@endif
</body>
</html>
