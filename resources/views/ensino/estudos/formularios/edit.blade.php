@extends('layouts.porto')

@section('title', 'Editar formulário')

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.estudos.index') }}">Ensino</a></li>
    <li><a href="{{ route('ensino.estudos.show', $estudo) }}">{{ $estudo->name }}</a></li>
    <li><a href="{{ route('ensino.estudos.formularios.show', [$estudo, $formulario]) }}">{{ $formulario->title }}</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-10">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Editar formulário</h5>
                <a href="{{ route('ensino.estudos.formularios.show', [$estudo, $formulario]) }}" class="btn btn-secondary btn-sm">Voltar</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('ensino.estudos.formularios.update', [$estudo, $formulario]) }}" id="form-edit">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $formulario->title) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $formulario->description) }}</textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                               {{ old('is_active', $formulario->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Formulário ativo</label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Perguntas</h6>
                        <button type="button" class="btn btn-outline-success btn-sm" id="btn-add-question">
                            <i class="bx bx-plus me-1"></i>Adicionar pergunta
                        </button>
                    </div>

                    <div id="questions-container">
                        @foreach(old('questions', $formulario->questions->map(fn ($q) => [
                            'prompt' => $q->prompt,
                            'theme' => $q->theme,
                            'type' => $q->type,
                            'correct_answer' => $q->correct_answer,
                            'options_text' => collect($q->options ?? [])->pluck('text')->implode("\n"),
                            'is_required' => $q->is_required,
                        ])->all()) as $i => $question)
                            @include('ensino.estudos.formularios._question-fields', ['index' => $i, 'question' => $question])
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-check me-1"></i>Salvar alterações
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="question-template">
    @include('ensino.estudos.formularios._question-fields', [
        'index' => '__INDEX__',
        'question' => [
            'prompt' => '',
            'theme' => '',
            'type' => 'dissertative',
            'correct_answer' => '',
            'options_text' => '',
            'is_required' => true,
        ],
    ])
</template>
@endsection

@push('scripts')
<script>
(function () {
    const container = document.getElementById('questions-container');
    const template = document.getElementById('question-template').innerHTML;
    let nextIndex = container.querySelectorAll('.question-block').length;

    document.getElementById('btn-add-question').addEventListener('click', () => {
        const html = template.replaceAll('__INDEX__', String(nextIndex));
        container.insertAdjacentHTML('beforeend', html);
        nextIndex++;
        bindQuestionBlocks();
    });

    container.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-remove-question');
        if (!btn) return;
        if (container.querySelectorAll('.question-block').length <= 1) {
            alert('O formulário precisa ter ao menos uma pergunta.');
            return;
        }
        btn.closest('.question-block').remove();
    });

    function bindQuestionBlocks() {
        container.querySelectorAll('.question-block').forEach(block => {
            const typeSelect = block.querySelector('.js-type');
            const optionsWrap = block.querySelector('.js-options-wrap');
            const sync = () => {
                const type = typeSelect.value;
                optionsWrap.classList.toggle('d-none', type !== 'multiple_choice');
            };
            typeSelect.removeEventListener('change', sync);
            typeSelect.addEventListener('change', sync);
            sync();
        });
    }

    bindQuestionBlocks();
})();
</script>
@endpush
