<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $formulario->title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg0: #0f2a24;
            --bg1: #163a32;
            --accent: #2bb673;
            --accent-deep: #1a8f57;
            --sand: #f4f7f5;
            --card: #ffffff;
            --ink: #14201c;
            --muted: #5d7269;
            --line: rgba(20, 32, 28, 0.08);
            --danger: #d64545;
            --shadow: 0 18px 50px rgba(10, 28, 22, 0.18);
            --radius: 22px;
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            font-family: 'Manrope', system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 500px at 10% -10%, rgba(43, 182, 115, 0.28), transparent 55%),
                radial-gradient(900px 420px at 100% 0%, rgba(20, 90, 70, 0.35), transparent 50%),
                linear-gradient(165deg, var(--bg0), var(--bg1) 45%, #1c4a40 100%);
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sf-shell {
            width: min(720px, 100%);
            margin: 0 auto;
            padding: 1rem 1rem calc(6.5rem + var(--safe-bottom));
            flex: 1;
        }

        .sf-hero {
            color: #e8fff4;
            padding: 1.25rem 0.25rem 1.5rem;
            animation: fadeUp .45s ease both;
        }

        .sf-kicker {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .72rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            font-weight: 700;
            color: #9fe8c4;
            margin-bottom: .55rem;
        }

        .sf-title {
            font-family: 'Fraunces', Georgia, serif;
            font-size: clamp(1.7rem, 5vw, 2.35rem);
            line-height: 1.15;
            margin: 0 0 .5rem;
            font-weight: 700;
        }

        .sf-study {
            margin: 0;
            color: rgba(232, 255, 244, 0.78);
            font-size: .95rem;
        }

        .sf-progress-wrap {
            margin-top: 1.25rem;
        }

        .sf-progress-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: .8rem;
            color: rgba(232, 255, 244, 0.8);
            margin-bottom: .45rem;
        }

        .sf-progress {
            height: 8px;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            overflow: hidden;
        }

        .sf-progress > span {
            display: block;
            height: 100%;
            width: 0;
            border-radius: inherit;
            background: linear-gradient(90deg, #7de2b0, var(--accent));
            transition: width .35s ease;
        }

        .sf-steps-dots {
            display: flex;
            gap: .35rem;
            margin-top: .7rem;
            flex-wrap: wrap;
        }

        .sf-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,.25);
            transition: .2s ease;
        }

        .sf-dot.is-active { background: #fff; transform: scale(1.25); }
        .sf-dot.is-done { background: var(--accent); }

        .sf-card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.25rem 1.15rem 1.35rem;
            animation: fadeUp .45s ease both;
            animation-delay: .05s;
        }

        .sf-section-label {
            font-size: .72rem;
            letter-spacing: .1em;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--accent-deep);
            margin-bottom: .35rem;
        }

        .sf-section-title {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 1.35rem;
            margin: 0 0 .35rem;
            color: var(--ink);
        }

        .sf-section-type {
            display: inline-flex;
            align-items: center;
            padding: .28rem .65rem;
            border-radius: 999px;
            background: #e8f8ef;
            color: var(--accent-deep);
            font-size: .78rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .sf-desc {
            color: var(--muted);
            margin: 0 0 1.1rem;
            line-height: 1.5;
        }

        .sf-field label {
            display: block;
            font-weight: 700;
            margin-bottom: .45rem;
            font-size: .95rem;
        }

        .sf-input, .sf-textarea {
            width: 100%;
            border: 1.5px solid var(--line);
            border-radius: 14px;
            padding: .9rem 1rem;
            font: inherit;
            color: var(--ink);
            background: #fbfcfb;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .sf-input:focus, .sf-textarea:focus {
            outline: none;
            border-color: rgba(43, 182, 115, .65);
            box-shadow: 0 0 0 4px rgba(43, 182, 115, .15);
            background: #fff;
        }

        .sf-textarea { min-height: 130px; resize: vertical; }

        .sf-question {
            padding: 1rem 0;
            border-top: 1px solid var(--line);
        }

        .sf-question:first-of-type { border-top: 0; padding-top: 0; }

        .sf-q-num {
            font-size: .75rem;
            font-weight: 800;
            color: var(--accent-deep);
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: .35rem;
        }

        .sf-q-text {
            font-size: 1.08rem;
            font-weight: 700;
            line-height: 1.35;
            margin: 0 0 .9rem;
        }

        .sf-options {
            display: grid;
            gap: .55rem;
        }

        .sf-option {
            position: relative;
        }

        .sf-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .sf-option label {
            display: flex;
            align-items: center;
            gap: .75rem;
            width: 100%;
            min-height: 52px;
            padding: .85rem 1rem;
            border-radius: 14px;
            border: 1.5px solid var(--line);
            background: #fbfcfb;
            cursor: pointer;
            transition: .18s ease;
            font-weight: 600;
            line-height: 1.3;
        }

        .sf-option label::before {
            content: '';
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #b7c7bf;
            flex: 0 0 20px;
            background: #fff;
            transition: .18s ease;
        }

        .sf-option input:checked + label {
            border-color: rgba(43, 182, 115, .7);
            background: #eefaf3;
            box-shadow: 0 0 0 3px rgba(43, 182, 115, .12);
        }

        .sf-option input:checked + label::before {
            border-color: var(--accent);
            background:
                radial-gradient(circle at center, var(--accent) 0 45%, #fff 48% 100%);
        }

        .sf-option label:active { transform: scale(.99); }

        .sf-alert {
            border-radius: 14px;
            padding: .9rem 1rem;
            margin-bottom: 1rem;
            font-size: .92rem;
        }

        .sf-alert-ok {
            background: #e8f8ef;
            color: #146c43;
            border: 1px solid #bfe9d1;
        }

        .sf-alert-err {
            background: #fff1f1;
            color: #9b2c2c;
            border: 1px solid #f5c2c2;
        }

        .sf-success-card {
            text-align: center;
            padding: 1.5rem .5rem;
        }

        .sf-success-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: grid;
            place-items: center;
            background: linear-gradient(145deg, #7de2b0, var(--accent));
            color: #fff;
            font-size: 2rem;
            box-shadow: 0 10px 24px rgba(43, 182, 115, .35);
        }

        .sf-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            padding: .85rem 1rem calc(.85rem + var(--safe-bottom));
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(12px);
            border-top: 1px solid rgba(20,32,28,.06);
            z-index: 20;
        }

        .sf-nav-inner {
            width: min(720px, 100%);
            margin: 0 auto;
            display: flex;
            gap: .65rem;
        }

        .sf-btn {
            appearance: none;
            border: 0;
            border-radius: 14px;
            font: inherit;
            font-weight: 750;
            padding: .95rem 1.1rem;
            cursor: pointer;
            transition: .18s ease;
            min-height: 50px;
        }

        .sf-btn:active { transform: scale(.98); }

        .sf-btn-ghost {
            flex: 0 0 auto;
            background: #eef2f0;
            color: var(--ink);
            min-width: 110px;
        }

        .sf-btn-primary {
            flex: 1;
            background: linear-gradient(135deg, var(--accent), var(--accent-deep));
            color: #fff;
            box-shadow: 0 10px 22px rgba(26, 143, 87, .28);
        }

        .sf-btn-primary:disabled {
            opacity: .55;
            cursor: not-allowed;
            box-shadow: none;
        }

        .sf-panel { display: none; }
        .sf-panel.is-active { display: block; animation: fadeUp .35s ease both; }

        .sf-hidden { display: none !important; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (min-width: 768px) {
            .sf-shell { padding-top: 1.75rem; }
            .sf-card { padding: 1.75rem 1.75rem 1.85rem; }
            .sf-hero { padding-top: 1.75rem; }
        }
    </style>
</head>
<body>
@php
    $totalSections = count($sections);
    $totalSteps = $totalSections + 1; // + nome
    $questionNumber = 0;
@endphp

<div class="sf-shell">
    <header class="sf-hero">
        <div class="sf-kicker">Questionário</div>
        <h1 class="sf-title">{{ $formulario->title }}</h1>
        @if($formulario->study)
            <p class="sf-study">Estudo: {{ $formulario->study->name }}</p>
        @endif

        @unless(session('success'))
        <div class="sf-progress-wrap" id="progress-wrap">
            <div class="sf-progress-meta">
                <span id="step-label">Identificação</span>
                <span><span id="step-current">1</span>/<span id="step-total">{{ $totalSteps }}</span></span>
            </div>
            <div class="sf-progress"><span id="progress-bar"></span></div>
            <div class="sf-steps-dots" id="steps-dots" aria-hidden="true">
                @for($i = 0; $i < $totalSteps; $i++)
                    <span class="sf-dot {{ $i === 0 ? 'is-active' : '' }}" data-dot="{{ $i }}"></span>
                @endfor
            </div>
        </div>
        @endunless
    </header>

    <main class="sf-card">
        @if(session('success'))
            <div class="sf-success-card">
                <div class="sf-success-icon">✓</div>
                <h2 class="sf-section-title">Respostas enviadas</h2>
                <p class="sf-desc">{{ session('success') }}</p>
            </div>
        @else
            @if($errors->any())
                <div class="sf-alert sf-alert-err">
                    <strong>Confira os campos:</strong>
                    <ul style="margin:.4rem 0 0 1.1rem;padding:0;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('study.forms.public.submit', $formulario->public_slug) }}" id="quiz-form" novalidate>
                @csrf

                {{-- Passo 0: identificação --}}
                <section class="sf-panel is-active" data-step="0" data-label="Identificação">
                    <div class="sf-section-label">Começar</div>
                    <h2 class="sf-section-title">Quem está respondendo?</h2>
                    @if($formulario->description)
                        <p class="sf-desc">{{ $formulario->description }}</p>
                    @else
                        <p class="sf-desc">Preencha seu nome e avance pelas guias do questionário. São {{ $formulario->questions->count() }} perguntas em {{ $totalSections }} {{ $totalSections === 1 ? 'parte' : 'partes' }}.</p>
                    @endif

                    <div class="sf-field">
                        <label for="respondent_name">Seu nome <span style="color:var(--danger)">*</span></label>
                        <input type="text" class="sf-input" id="respondent_name" name="respondent_name"
                               value="{{ old('respondent_name') }}" maxlength="255"
                               placeholder="Digite seu nome completo" autocomplete="name">
                    </div>
                </section>

                {{-- Guias por tema + tipo --}}
                @foreach($sections as $sIndex => $section)
                    <section class="sf-panel" data-step="{{ $sIndex + 1 }}"
                             data-label="{{ $section['theme'] ?: $section['type_label'] }}">
                        <div class="sf-section-label">Parte {{ $sIndex + 1 }} de {{ $totalSections }}</div>
                        @if($section['theme'])
                            <h2 class="sf-section-title">{{ $section['theme'] }}</h2>
                        @else
                            <h2 class="sf-section-title">{{ $section['type_label'] }}</h2>
                        @endif
                        <span class="sf-section-type">{{ $section['type_label'] }}</span>

                        @foreach($section['questions'] as $question)
                            @php $questionNumber++; @endphp
                            <div class="sf-question" data-question-id="{{ $question->id }}" data-required="{{ $question->is_required ? '1' : '0' }}" data-type="{{ $question->type }}">
                                <div class="sf-q-num">Pergunta {{ $questionNumber }}</div>
                                <p class="sf-q-text">
                                    {{ $question->prompt }}
                                    @if($question->is_required)<span style="color:var(--danger)">*</span>@endif
                                </p>

                                @if($question->type === 'dissertative')
                                    <textarea class="sf-textarea" name="answers[{{ $question->id }}]"
                                              placeholder="Digite sua resposta">{{ old('answers.'.$question->id) }}</textarea>
                                @elseif($question->type === 'true_false')
                                    <div class="sf-options">
                                        <div class="sf-option">
                                            <input type="radio" name="answers[{{ $question->id }}]" value="true"
                                                   id="q{{ $question->id }}_true"
                                                   @checked(old('answers.'.$question->id) === 'true')>
                                            <label for="q{{ $question->id }}_true">Verdadeiro</label>
                                        </div>
                                        <div class="sf-option">
                                            <input type="radio" name="answers[{{ $question->id }}]" value="false"
                                                   id="q{{ $question->id }}_false"
                                                   @checked(old('answers.'.$question->id) === 'false')>
                                            <label for="q{{ $question->id }}_false">Falso</label>
                                        </div>
                                    </div>
                                @else
                                    <div class="sf-options">
                                        @foreach($question->options ?? [] as $opt)
                                            <div class="sf-option">
                                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $opt['key'] }}"
                                                       id="q{{ $question->id }}_{{ $opt['key'] }}"
                                                       @checked(old('answers.'.$question->id) === $opt['key'])>
                                                <label for="q{{ $question->id }}_{{ $opt['key'] }}">{{ $opt['text'] }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endforeach
            </form>
        @endif
    </main>
</div>

@unless(session('success'))
<nav class="sf-nav" id="quiz-nav">
    <div class="sf-nav-inner">
        <button type="button" class="sf-btn sf-btn-ghost sf-hidden" id="btn-prev">Voltar</button>
        <button type="button" class="sf-btn sf-btn-primary" id="btn-next">Continuar</button>
    </div>
</nav>
@endunless

@unless(session('success'))
<script>
(function () {
    const panels = Array.from(document.querySelectorAll('.sf-panel'));
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const form = document.getElementById('quiz-form');
    const progressBar = document.getElementById('progress-bar');
    const stepCurrent = document.getElementById('step-current');
    const stepLabel = document.getElementById('step-label');
    const dots = Array.from(document.querySelectorAll('.sf-dot'));
    let step = 0;
    const total = panels.length;

    function updateUI() {
        panels.forEach((panel, i) => panel.classList.toggle('is-active', i === step));
        btnPrev.classList.toggle('sf-hidden', step === 0);
        btnNext.textContent = step === total - 1 ? 'Enviar respostas' : 'Continuar';

        const pct = ((step + 1) / total) * 100;
        progressBar.style.width = pct + '%';
        stepCurrent.textContent = String(step + 1);
        stepLabel.textContent = panels[step]?.dataset.label || 'Parte';

        dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === step);
            dot.classList.toggle('is-done', i < step);
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateStep() {
        const panel = panels[step];
        if (!panel) return true;

        if (step === 0) {
            const name = document.getElementById('respondent_name');
            if (!name.value.trim()) {
                name.focus();
                name.style.borderColor = 'var(--danger)';
                alert('Informe o seu nome para continuar.');
                return false;
            }
            name.style.borderColor = '';
            return true;
        }

        const questions = panel.querySelectorAll('.sf-question[data-required="1"]');
        for (const q of questions) {
            const type = q.dataset.type;
            const id = q.dataset.questionId;
            if (type === 'dissertative') {
                const ta = q.querySelector('textarea');
                if (!ta || !ta.value.trim()) {
                    ta?.focus();
                    alert('Responda todas as perguntas obrigatórias desta guia.');
                    return false;
                }
            } else {
                const checked = q.querySelector('input[type="radio"]:checked');
                if (!checked) {
                    alert('Responda todas as perguntas obrigatórias desta guia.');
                    q.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            }
        }
        return true;
    }

    btnNext.addEventListener('click', () => {
        if (!validateStep()) return;
        if (step < total - 1) {
            step++;
            updateUI();
            return;
        }
        form.submit();
    });

    btnPrev.addEventListener('click', () => {
        if (step > 0) {
            step--;
            updateUI();
        }
    });

    // Se houver erros de validação do servidor, tenta ir para a primeira guia com problema
    @if($errors->any())
        step = 0;
        const firstErrorKey = @json(collect($errors->keys())->first());
        if (firstErrorKey && firstErrorKey.startsWith('answers.')) {
            const qid = firstErrorKey.split('.')[1];
            const panel = panels.findIndex(p => p.querySelector(`[data-question-id="${qid}"]`));
            if (panel >= 0) step = panel;
        }
    @endif

    updateUI();
})();
</script>
@endunless
</body>
</html>
