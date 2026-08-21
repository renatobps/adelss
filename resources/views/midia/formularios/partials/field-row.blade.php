@php
    /** @var \App\Models\MediaFormField|null $field */
    $field = $field ?? null;
    $index = $index ?? '__INDEX__';
    $tipo = old("fields.{$index}.type", $field->type ?? \App\Models\MediaFormField::TYPE_TEXT);
    $temOpcoes = in_array($tipo, \App\Models\MediaFormField::TYPES_WITH_OPTIONS, true);
    $opcoesTexto = old("fields.{$index}.options_text", implode("\n", $field?->optionList() ?? []));
    $obrigatorio = (bool) old("fields.{$index}.is_required", $field->is_required ?? false);
@endphp

<div class="field-row card border mb-3" data-field-row>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <span class="badge bg-light text-dark">Campo <span data-field-position>{{ is_numeric($index) ? $index + 1 : 1 }}</span></span>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" data-move-up title="Mover para cima">
                    <i class="bx bx-chevron-up"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" data-move-down title="Mover para baixo">
                    <i class="bx bx-chevron-down"></i>
                </button>
                <button type="button" class="btn btn-outline-danger" data-remove-field title="Remover campo">
                    <i class="bx bx-trash"></i>
                </button>
            </div>
        </div>

        @if($field?->exists)
            <input type="hidden" name="fields[{{ $index }}][id]" value="{{ $field->id }}" data-field-id>
        @endif

        <div class="row g-2">
            <div class="col-12 col-md-6">
                <label class="form-label small">Título do campo <span class="text-danger">*</span></label>
                <input type="text" name="fields[{{ $index }}][label]" class="form-control form-control-sm"
                       value="{{ old("fields.{$index}.label", $field->label ?? '') }}"
                       placeholder="Ex.: Qual o seu nome completo?" required>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label small">Tipo de campo</label>
                <select name="fields[{{ $index }}][type]" class="form-select form-select-sm" data-field-type>
                    @foreach(\App\Models\MediaFormField::TYPES as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected($tipo === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <label class="form-label small">Texto de ajuda</label>
                <input type="text" name="fields[{{ $index }}][help_text]" class="form-control form-control-sm"
                       value="{{ old("fields.{$index}.help_text", $field->help_text ?? '') }}"
                       placeholder="Opcional. Aparece embaixo do campo, para orientar quem responde.">
            </div>

            <div class="col-12 {{ $temOpcoes ? '' : 'd-none' }}" data-options-wrapper>
                <label class="form-label small">Opções <span class="text-danger">*</span></label>
                <textarea name="fields[{{ $index }}][options_text]" class="form-control form-control-sm" rows="3"
                          placeholder="Uma opção por linha">{{ $opcoesTexto }}</textarea>
                <div class="form-text">Uma opção por linha, no mínimo duas.</div>
            </div>

            <div class="col-12">
                <input type="hidden" name="fields[{{ $index }}][is_required]" value="0">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="required-{{ $index }}"
                           name="fields[{{ $index }}][is_required]" value="1" @checked($obrigatorio)>
                    <label class="form-check-label small" for="required-{{ $index }}">
                        Resposta obrigatória
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
