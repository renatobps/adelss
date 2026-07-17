@php
    $prompt = $question['prompt'] ?? '';
    $theme = $question['theme'] ?? '';
    $type = $question['type'] ?? 'dissertative';
    $correct = $question['correct_answer'] ?? '';
    $optionsText = $question['options_text'] ?? '';
    $required = array_key_exists('is_required', $question) ? (bool) $question['is_required'] : true;
@endphp

<div class="question-block border rounded p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Pergunta</strong>
        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-question">
            <i class="bx bx-trash"></i>
        </button>
    </div>

    <div class="mb-2">
        <label class="form-label">Tema / seção</label>
        <input type="text" name="questions[{{ $index }}][theme]" class="form-control"
               value="{{ $theme }}" placeholder="Ex: Antigo Testamento">
    </div>

    <div class="mb-2">
        <label class="form-label">Enunciado</label>
        <textarea name="questions[{{ $index }}][prompt]" class="form-control" rows="2" required>{{ $prompt }}</textarea>
    </div>

    <div class="row g-2 mb-2">
        <div class="col-md-6">
            <label class="form-label">Tipo</label>
            <select name="questions[{{ $index }}][type]" class="form-select js-type">
                <option value="dissertative" @selected($type === 'dissertative')>Dissertativa</option>
                <option value="multiple_choice" @selected($type === 'multiple_choice')>Múltipla escolha</option>
                <option value="true_false" @selected($type === 'true_false')>Verdadeiro ou falso</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Resposta correta / referência</label>
            <input type="text" name="questions[{{ $index }}][correct_answer]" class="form-control"
                   value="{{ $correct }}"
                   placeholder="Ex: c | true | false | texto">
            <div class="form-text">Múltipla: letra (a, b...). V/F: true ou false. Dissertativa: só referência.</div>
        </div>
    </div>

    <div class="mb-2 js-options-wrap {{ $type === 'multiple_choice' ? '' : 'd-none' }}">
        <label class="form-label">Opções (uma por linha)</label>
        <textarea name="questions[{{ $index }}][options_text]" class="form-control" rows="4"
                  placeholder="São Paulo&#10;Rio de Janeiro&#10;Brasília">{{ $optionsText }}</textarea>
    </div>

    <div class="form-check">
        <input type="hidden" name="questions[{{ $index }}][is_required]" value="0">
        <input class="form-check-input" type="checkbox" name="questions[{{ $index }}][is_required]" value="1"
               id="required_{{ $index }}" @checked($required)>
        <label class="form-check-label" for="required_{{ $index }}">Obrigatória</label>
    </div>
</div>

