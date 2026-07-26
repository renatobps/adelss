@php
    $fields = $customFields ?? collect();
    $values = $values ?? [];
@endphp
@if($fields->count())
    <div class="col-md-12 mb-3 mt-3">
        <h5 class="border-bottom pb-2">Campos personalizados</h5>
    </div>
    @foreach($fields as $field)
        @php
            $inputName = 'custom_fields[' . $field->id . ']';
            $current = old('custom_fields.' . $field->id, $values[$field->id] ?? '');
        @endphp
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label" for="custom_field_{{ $field->id }}">
                    {{ $field->name }}
                    @if($field->is_required)<span class="text-danger">*</span>@endif
                </label>
                @if($field->type === 'select')
                    <select class="form-select" id="custom_field_{{ $field->id }}" name="{{ $inputName }}" @if($field->is_required) required @endif>
                        <option value="">Selecione...</option>
                        @foreach(($field->options ?? []) as $opt)
                            <option value="{{ $opt }}" @selected((string) $current === (string) $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                @elseif($field->type === 'number')
                    <input type="number" step="any" class="form-control" id="custom_field_{{ $field->id }}"
                           name="{{ $inputName }}" value="{{ $current }}" @if($field->is_required) required @endif>
                @elseif($field->type === 'date')
                    <input type="date" class="form-control" id="custom_field_{{ $field->id }}"
                           name="{{ $inputName }}" value="{{ $current }}" @if($field->is_required) required @endif>
                @else
                    <input type="text" class="form-control" id="custom_field_{{ $field->id }}"
                           name="{{ $inputName }}" value="{{ $current }}" @if($field->is_required) required @endif>
                @endif
            </div>
        </div>
    @endforeach
@endif
