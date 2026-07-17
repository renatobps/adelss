@extends('layouts.porto')

@section('title', 'Novo formulário')

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.estudos.index') }}">Ensino</a></li>
    <li><a href="{{ route('ensino.estudos.show', $estudo) }}">{{ $estudo->name }}</a></li>
    <li><a href="{{ route('ensino.estudos.formularios.index', $estudo) }}">Formulários</a></li>
    <li><span>Novo</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Criar formulário de perguntas</h5>
                <a href="{{ route('ensino.estudos.formularios.index', $estudo) }}" class="btn btn-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i>Voltar
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('ensino.estudos.formularios.store', $estudo) }}" id="form-create">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Título do formulário <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', 'Questionário - '.$estudo->name) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição (opcional)</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                        <label class="form-check-label" for="is_active">Formulário ativo (link público disponível)</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cole o texto com as perguntas já respondidas</label>
                        <textarea name="source_text" id="source_text" class="form-control @error('source_text') is-invalid @enderror"
                                  rows="14" placeholder="Exemplo:

1. Sobre a Bíblia
Questões Dissertativas
1. Quantos livros possui a Bíblia?
Resposta: A Bíblia possui 66 livros...
Verdadeiro ou Falso
2. A Bíblia possui 66 livros.
Verdadeiro *
Falso
Múltipla Escolha
3. A Bíblia possui aproximadamente quantos capítulos?
a) 929
b) 1.189 *
c) 1.500
d) 31.102">{{ old('source_text') }}</textarea>
                        @error('source_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">
                            Modelo: tema (<code>1. Sobre a Bíblia</code>) + tipo (<code>Questões Dissertativas</code> /
                            <code>Verdadeiro ou Falso</code> / <code>Múltipla Escolha</code>).
                            Marque a correta com <strong>*</strong> ou use <code>Resposta: ...</code>.
                        </div>
                    </div>

                    <div class="d-flex gap-2 mb-4">
                        <button type="button" class="btn btn-outline-primary" id="btn-preview">
                            <i class="bx bx-search-alt me-1"></i>Interpretar perguntas
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-check me-1"></i>Criar formulário
                        </button>
                    </div>

                    <div id="preview-area" class="d-none">
                        <hr>
                        <h6 class="mb-3">Perguntas detectadas <span class="badge bg-primary" id="preview-count">0</span></h6>
                        <div id="preview-list"></div>
                        <input type="hidden" name="questions_json" id="questions_json">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header"><h6 class="mb-0">Como formatar</h6></div>
            <div class="card-body small">
                <p><strong>Tema</strong> — <code>1. Sobre a Bíblia</code></p>
                <p><strong>Tipos</strong> — <code>Questões Dissertativas</code>, <code>Verdadeiro ou Falso</code>, <code>Múltipla Escolha</code> (pode trocar o tipo no mesmo tema).</p>
                <p><strong>Múltipla escolha</strong> — a) b) c) com <code>*</code> na correta.</p>
                <p class="mb-0"><strong>Dissertativa</strong> — use <code>Resposta: texto</code> (fica só no admin).</p>
            </div>
        </div>
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header"><h6 class="mb-0">Estudo</h6></div>
            <div class="card-body">
                <strong>{{ $estudo->name }}</strong>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const previewBtn = document.getElementById('btn-preview');
    const sourceText = document.getElementById('source_text');
    const previewArea = document.getElementById('preview-area');
    const previewList = document.getElementById('preview-list');
    const previewCount = document.getElementById('preview-count');
    const form = document.getElementById('form-create');
    let parsedQuestions = [];

    const typeLabels = {
        dissertative: 'Dissertativa',
        multiple_choice: 'Múltipla escolha',
        true_false: 'Verdadeiro/Falso'
    };

    function renderPreview(questions) {
        parsedQuestions = questions;
        previewCount.textContent = questions.length;
        previewArea.classList.toggle('d-none', questions.length === 0);

        let html = '';
        let lastTheme = null;
        questions.forEach((q, i) => {
            const theme = (q.theme || '').trim();
            if (theme && theme !== lastTheme) {
                html += `<div class="alert alert-success py-2 mb-2"><strong>Tema:</strong> ${escapeHtml(theme)}</div>`;
                lastTheme = theme;
            }

            let extras = '';
            if (q.type === 'multiple_choice' && Array.isArray(q.options)) {
                extras = '<ul class="mb-0">' + q.options.map(o => {
                    const mark = q.correct_answer === o.key ? ' <span class="text-success">✓</span>' : '';
                    return `<li><strong>${o.key.toUpperCase()})</strong> ${escapeHtml(o.text)}${mark}</li>`;
                }).join('') + '</ul>';
            }
            if (q.type === 'true_false') {
                const ans = q.correct_answer === 'true' ? 'Verdadeiro' : (q.correct_answer === 'false' ? 'Falso' : '—');
                extras = `<div class="text-muted">Resposta: ${ans}</div>`;
            }
            if (q.type === 'dissertative' && q.correct_answer) {
                extras = `<div class="text-muted small">Referência: ${escapeHtml(q.correct_answer)}</div>`;
            }

            html += `<div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between mb-1">
                    <strong>${i + 1}. ${escapeHtml(q.prompt)}</strong>
                    <span class="badge bg-secondary">${typeLabels[q.type] || q.type}</span>
                </div>
                ${theme ? `<div class="small text-success mb-1">${escapeHtml(theme)}</div>` : ''}
                ${extras}
            </div>`;
        });
        previewList.innerHTML = html;

        // injeta campos hidden para o backend
        document.querySelectorAll('.js-question-field').forEach(el => el.remove());
        questions.forEach((q, i) => {
            appendHidden(`questions[${i}][prompt]`, q.prompt);
            appendHidden(`questions[${i}][theme]`, q.theme || '');
            appendHidden(`questions[${i}][type]`, q.type);
            appendHidden(`questions[${i}][correct_answer]`, q.correct_answer || '');
            appendHidden(`questions[${i}][is_required]`, '1');
            if (Array.isArray(q.options)) {
                q.options.forEach((o, j) => {
                    appendHidden(`questions[${i}][options][${j}][key]`, o.key);
                    appendHidden(`questions[${i}][options][${j}][text]`, o.text);
                });
            }
        });
    }

    function appendHidden(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value ?? '';
        input.className = 'js-question-field';
        form.appendChild(input);
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    previewBtn.addEventListener('click', async () => {
        previewBtn.disabled = true;
        previewBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Interpretando...';
        try {
            const res = await fetch(@json(route('ensino.estudos.formularios.preview', $estudo)), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        || document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ source_text: sourceText.value })
            });
            const data = await res.json();
            if (!res.ok) {
                alert(data.message || 'Não foi possível interpretar o texto.');
                return;
            }
            if (!data.questions?.length) {
                alert('Nenhuma pergunta foi detectada. Verifique o formato do texto.');
                renderPreview([]);
                return;
            }
            renderPreview(data.questions);
        } catch (e) {
            alert('Erro ao interpretar o texto.');
        } finally {
            previewBtn.disabled = false;
            previewBtn.innerHTML = '<i class="bx bx-search-alt me-1"></i>Interpretar perguntas';
        }
    });

    form.addEventListener('submit', function (e) {
        if (parsedQuestions.length === 0 && sourceText.value.trim() !== '') {
            // deixa o backend interpretar pelo source_text
            return;
        }
        if (parsedQuestions.length === 0 && sourceText.value.trim() === '') {
            e.preventDefault();
            alert('Cole o texto das perguntas ou use "Interpretar perguntas" antes de criar.');
        }
    });
})();
</script>
@endpush
