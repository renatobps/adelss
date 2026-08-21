@extends('layouts.porto')

@php $editando = $form->exists; @endphp

@section('title', ($editando ? 'Editar' : 'Novo').' formulário — Mídia')
@section('page-title', $editando ? 'Editar formulário' : 'Novo formulário')

@section('breadcrumbs')
    <li><a href="{{ route('midia.index') }}">Mídia</a></li>
    <li><a href="{{ route('midia.formularios.index') }}">Formulários</a></li>
    <li><span>{{ $editando ? 'Editar' : 'Novo' }}</span></li>
@endsection

@section('content')
@include('midia.formularios.partials.alerts')

@if($editando)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small fw-bold mb-1">Link público</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bx bx-link"></i></span>
                <input type="text" class="form-control" readonly value="{{ $form->publicUrl() }}" id="publicLink">
                <button type="button" class="btn btn-outline-secondary" id="copyPublicLink">Copiar</button>
                <a href="{{ $form->publicUrl() }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                    <i class="bx bx-external-link"></i> Abrir
                </a>
            </div>
            <div class="form-text">Qualquer pessoa com este link pode responder, sem precisar de login.</div>
        </div>
    </div>
@endif

<form method="POST" action="{{ $editando ? route('midia.formularios.update', $form) : route('midia.formularios.store') }}">
    @csrf
    @if($editando)
        @method('PUT')
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent">
            <h5 class="mb-0">Dados do formulário</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title', $form->title) }}" required maxlength="255"
                           placeholder="Ex.: Inscrição para o retiro de jovens">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                              maxlength="2000" placeholder="Explique para que serve este formulário. Aparece no topo da página pública.">{{ old('description', $form->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Mensagem de agradecimento</label>
                    <input type="text" name="success_message" class="form-control" maxlength="1000"
                           value="{{ old('success_message', $form->success_message) }}"
                           placeholder="Exibida depois do envio. Em branco, usamos uma mensagem padrão.">
                </div>

                <div class="col-12 col-md-4">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="form-check-input" id="isActive" name="is_active" value="1"
                               @checked(old('is_active', $form->is_active))>
                        <label class="form-check-label" for="isActive">Formulário ativo</label>
                    </div>
                    <div class="form-text">Desativado, o link público deixa de abrir.</div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_accepting_responses" value="0">
                        <input type="checkbox" class="form-check-input" id="isAccepting" name="is_accepting_responses" value="1"
                               @checked(old('is_accepting_responses', $form->is_accepting_responses))>
                        <label class="form-check-label" for="isAccepting">Recebendo respostas</label>
                    </div>
                    <div class="form-text">Desligue para encerrar sem tirar a página do ar.</div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="form-check form-switch">
                        <input type="hidden" name="requires_identification" value="0">
                        <input type="checkbox" class="form-check-input" id="requiresId" name="requires_identification" value="1"
                               @checked(old('requires_identification', $form->requires_identification))>
                        <label class="form-check-label" for="requiresId">Pedir nome e telefone</label>
                    </div>
                    <div class="form-text">Desligado, as respostas ficam anônimas.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-0">Campos</h5>
                <small class="text-muted">Os dados que você quer coletar, na ordem em que aparecem.</small>
            </div>
            <button type="button" class="btn btn-sm btn-primary" id="addField">
                <i class="bx bx-plus"></i> Adicionar campo
            </button>
        </div>
        <div class="card-body">
            <div id="fieldsContainer">
                @php $antigos = old('fields'); @endphp

                @if(is_array($antigos))
                    @foreach(array_keys($antigos) as $indice)
                        @include('midia.formularios.partials.field-row', ['index' => $indice, 'field' => null])
                    @endforeach
                @elseif($fields->count() > 0)
                    @foreach($fields as $indice => $campo)
                        @include('midia.formularios.partials.field-row', ['index' => $indice, 'field' => $campo])
                    @endforeach
                @else
                    @include('midia.formularios.partials.field-row', ['index' => 0, 'field' => null])
                @endif
            </div>

            <p class="text-muted small mb-0" id="noFieldsHint" hidden>
                Nenhum campo. Adicione pelo menos um para poder salvar.
            </p>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save"></i> {{ $editando ? 'Salvar alterações' : 'Criar formulário' }}
        </button>
        <a href="{{ route('midia.formularios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<template id="fieldTemplate">
    @include('midia.formularios.partials.field-row', ['index' => '__INDEX__', 'field' => null])
</template>
@endsection

@push('scripts')
<script>
(function () {
    var container = document.getElementById('fieldsContainer');
    var template = document.getElementById('fieldTemplate');
    var hint = document.getElementById('noFieldsHint');
    var tiposComOpcoes = @json(array_values(\App\Models\MediaFormField::TYPES_WITH_OPTIONS));

    function linhas() {
        return Array.from(container.querySelectorAll('[data-field-row]'));
    }

    /**
     * O PHP monta o array pelos índices dos inputs, não pela ordem no DOM.
     * Sem renumerar, mover ou remover um campo não mudaria nada no que é salvo.
     */
    function renumerar() {
        linhas().forEach(function (linha, posicao) {
            linha.querySelectorAll('[name^="fields["]').forEach(function (campo) {
                campo.name = campo.name.replace(/^fields\[[^\]]*\]/, 'fields[' + posicao + ']');
            });

            var obrigatorio = linha.querySelector('.form-check-input[type="checkbox"]');
            if (obrigatorio) {
                var id = 'required-' + posicao;
                obrigatorio.id = id;
                var rotulo = linha.querySelector('.form-check-label');
                if (rotulo) rotulo.setAttribute('for', id);
            }

            var numero = linha.querySelector('[data-field-position]');
            if (numero) numero.textContent = posicao + 1;
        });

        if (hint) hint.hidden = linhas().length > 0;
    }

    function alternarOpcoes(linha) {
        var seletor = linha.querySelector('[data-field-type]');
        var opcoes = linha.querySelector('[data-options-wrapper]');
        if (!seletor || !opcoes) return;

        opcoes.classList.toggle('d-none', tiposComOpcoes.indexOf(seletor.value) === -1);
    }

    document.getElementById('addField').addEventListener('click', function () {
        var html = template.innerHTML.replace(/__INDEX__/g, String(linhas().length));
        var envelope = document.createElement('div');
        envelope.innerHTML = html.trim();

        var nova = envelope.querySelector('[data-field-row]');
        container.appendChild(nova);
        alternarOpcoes(nova);
        renumerar();
        nova.querySelector('input[type="text"]').focus();
    });

    container.addEventListener('change', function (evento) {
        if (evento.target.matches('[data-field-type]')) {
            alternarOpcoes(evento.target.closest('[data-field-row]'));
        }
    });

    container.addEventListener('click', function (evento) {
        var linha = evento.target.closest('[data-field-row]');
        if (!linha) return;

        if (evento.target.closest('[data-remove-field]')) {
            var existente = linha.querySelector('[data-field-id]');
            var aviso = existente
                ? 'Remover este campo? Ele sai do formulário, mas as respostas já coletadas continuam visíveis na tela de respostas.'
                : 'Remover este campo?';

            if (!confirm(aviso)) return;

            linha.remove();
            renumerar();
            return;
        }

        if (evento.target.closest('[data-move-up]')) {
            var anterior = linha.previousElementSibling;
            if (anterior) {
                container.insertBefore(linha, anterior);
                renumerar();
            }
            return;
        }

        if (evento.target.closest('[data-move-down]')) {
            var proximo = linha.nextElementSibling;
            if (proximo) {
                container.insertBefore(proximo, linha);
                renumerar();
            }
        }
    });

    linhas().forEach(alternarOpcoes);
    renumerar();

    var copiar = document.getElementById('copyPublicLink');
    if (copiar) {
        copiar.addEventListener('click', function () {
            var campo = document.getElementById('publicLink');
            campo.select();

            var concluir = function () {
                copiar.textContent = 'Copiado';
                setTimeout(function () { copiar.textContent = 'Copiar'; }, 1500);
            };

            if (navigator.clipboard) {
                navigator.clipboard.writeText(campo.value).then(concluir);
            } else {
                document.execCommand('copy');
                concluir();
            }
        });
    }
})();
</script>
@endpush
