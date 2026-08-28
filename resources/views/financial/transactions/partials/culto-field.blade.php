@php
    $cultos = $cultos ?? collect();
    $selectId = $selectId ?? 'culto_id';
    $wrapId = $wrapId ?? 'cultoFieldWrap';
@endphp
<div class="{{ $wrapClass ?? 'col-md-6 mb-3' }} d-none" id="{{ $wrapId }}">
    <label class="form-label" for="{{ $selectId }}">Culto <span class="text-danger">*</span></label>
    <select class="form-select js-culto-select" id="{{ $selectId }}" name="culto_id">
        <option value="">Selecione o culto da agenda</option>
        @foreach($cultos as $culto)
            <option value="{{ $culto->id }}" data-date="{{ $culto->start_date?->format('Y-m-d') }}" @selected((string) old('culto_id') === (string) $culto->id)>
                {{ $culto->display_name }}
            </option>
        @endforeach
    </select>
    <small class="text-muted">Lista os cultos cadastrados na Agenda. Cadastre um novo culto lá se não aparecer.</small>
    @error('culto_id')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
