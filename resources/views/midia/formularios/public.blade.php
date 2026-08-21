@extends('layouts.public-form')

@section('title', $form->title)

@section('content')
@if(session('form_submitted'))
    <div class="pf-card">
        <div class="pf-state">
            <div class="pf-state__icon" style="color: var(--success);">&check;</div>
            <h1 class="pf-title">Resposta enviada</h1>
            <p class="pf-description">
                {{ filled($form->success_message) ? $form->success_message : 'Obrigado por responder. Recebemos os seus dados.' }}
            </p>
        </div>
    </div>
@elseif(!$form->is_accepting_responses)
    <div class="pf-card">
        <div class="pf-state">
            <div class="pf-state__icon" style="color: var(--text-secondary);">&#9432;</div>
            <h1 class="pf-title">{{ $form->title }}</h1>
            <p class="pf-description">Este formulário não está mais recebendo respostas.</p>
        </div>
    </div>
@else
    <div class="pf-card">
        <div class="pf-header">
            <h1 class="pf-title">{{ $form->title }}</h1>
            @if(filled($form->description))
                <p class="pf-description">{{ $form->description }}</p>
            @endif
        </div>

        @if(session('error'))
            <div class="alert alert-danger m-3 mb-0">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger m-3 mb-0">
                <strong>Confira os campos abaixo:</strong>
                <ul class="mb-0 ps-3 mt-1">
                    @foreach($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('formularios.public.submit', $form->public_slug) }}" novalidate>
            @csrf

            <div class="pf-body">
                @if($form->requires_identification)
                    <div class="pf-field">
                        <label class="pf-label" for="respondentName">
                            Seu nome <span class="pf-required">*</span>
                        </label>
                        <input type="text" id="respondentName" name="respondent_name" required maxlength="255"
                               class="form-control @error('respondent_name') is-invalid @enderror"
                               value="{{ old('respondent_name') }}">
                        @error('respondent_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="pf-field">
                        <label class="pf-label" for="respondentPhone">Telefone</label>
                        <input type="tel" id="respondentPhone" name="respondent_phone" maxlength="40"
                               class="form-control @error('respondent_phone') is-invalid @enderror"
                               value="{{ old('respondent_phone') }}" placeholder="(00) 00000-0000">
                        @error('respondent_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif

                @foreach($form->fields as $campo)
                    @php
                        $nome = 'answers['.$campo->id.']';
                        $chaveErro = 'answers.'.$campo->id;
                        $anterior = old('answers.'.$campo->id);
                        $invalido = $errors->has($chaveErro) || $errors->has($chaveErro.'.*');
                    @endphp

                    <div class="pf-field">
                        <label class="pf-label" for="field-{{ $campo->id }}">
                            {{ $campo->label }}
                            @if($campo->is_required)<span class="pf-required">*</span>@endif
                        </label>

                        @switch($campo->type)
                            @case(\App\Models\MediaFormField::TYPE_TEXTAREA)
                                <textarea id="field-{{ $campo->id }}" name="{{ $nome }}" rows="4" maxlength="5000"
                                          class="form-control @if($invalido) is-invalid @endif"
                                          @if($campo->is_required) required @endif>{{ $anterior }}</textarea>
                                @break

                            @case(\App\Models\MediaFormField::TYPE_NUMBER)
                                <input type="number" step="any" id="field-{{ $campo->id }}" name="{{ $nome }}"
                                       class="form-control @if($invalido) is-invalid @endif"
                                       value="{{ $anterior }}" @if($campo->is_required) required @endif>
                                @break

                            @case(\App\Models\MediaFormField::TYPE_DATE)
                                <input type="date" id="field-{{ $campo->id }}" name="{{ $nome }}"
                                       class="form-control @if($invalido) is-invalid @endif"
                                       value="{{ $anterior }}" @if($campo->is_required) required @endif>
                                @break

                            @case(\App\Models\MediaFormField::TYPE_EMAIL)
                                <input type="email" id="field-{{ $campo->id }}" name="{{ $nome }}" maxlength="255"
                                       class="form-control @if($invalido) is-invalid @endif"
                                       value="{{ $anterior }}" @if($campo->is_required) required @endif>
                                @break

                            @case(\App\Models\MediaFormField::TYPE_PHONE)
                                <input type="tel" id="field-{{ $campo->id }}" name="{{ $nome }}" maxlength="40"
                                       class="form-control @if($invalido) is-invalid @endif"
                                       value="{{ $anterior }}" @if($campo->is_required) required @endif
                                       placeholder="(00) 00000-0000">
                                @break

                            @case(\App\Models\MediaFormField::TYPE_SELECT)
                                <select id="field-{{ $campo->id }}" name="{{ $nome }}"
                                        class="form-select @if($invalido) is-invalid @endif"
                                        @if($campo->is_required) required @endif>
                                    <option value="">Selecione…</option>
                                    @foreach($campo->optionList() as $opcao)
                                        <option value="{{ $opcao }}" @selected($anterior === $opcao)>{{ $opcao }}</option>
                                    @endforeach
                                </select>
                                @break

                            @case(\App\Models\MediaFormField::TYPE_RADIO)
                                <div @if($invalido) class="is-invalid" @endif>
                                    @foreach($campo->optionList() as $i => $opcao)
                                        <label class="pf-option" for="field-{{ $campo->id }}-{{ $i }}">
                                            <input type="radio" id="field-{{ $campo->id }}-{{ $i }}" name="{{ $nome }}"
                                                   value="{{ $opcao }}" @checked($anterior === $opcao)
                                                   @if($campo->is_required) required @endif>
                                            <span>{{ $opcao }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @break

                            @case(\App\Models\MediaFormField::TYPE_CHECKBOX)
                                @php $marcados = is_array($anterior) ? $anterior : []; @endphp
                                <div @if($invalido) class="is-invalid" @endif>
                                    @foreach($campo->optionList() as $i => $opcao)
                                        <label class="pf-option" for="field-{{ $campo->id }}-{{ $i }}">
                                            <input type="checkbox" id="field-{{ $campo->id }}-{{ $i }}"
                                                   name="answers[{{ $campo->id }}][]" value="{{ $opcao }}"
                                                   @checked(in_array($opcao, $marcados, true))>
                                            <span>{{ $opcao }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @break

                            @default
                                <input type="text" id="field-{{ $campo->id }}" name="{{ $nome }}" maxlength="500"
                                       class="form-control @if($invalido) is-invalid @endif"
                                       value="{{ $anterior }}" @if($campo->is_required) required @endif>
                        @endswitch

                        @if(filled($campo->help_text))
                            <span class="pf-help">{{ $campo->help_text }}</span>
                        @endif

                        @error($chaveErro)
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="pf-footer">
                <button type="submit" class="btn btn-primary pf-submit">Enviar resposta</button>
            </div>
        </form>
    </div>
@endif
@endsection
