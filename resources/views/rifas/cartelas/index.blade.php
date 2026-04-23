@extends('layouts.porto')

@section('title', 'Cartelas da Rifa')
@section('page-title', 'Cartelas - ' . $rifa->nome)

@section('breadcrumbs')
    <li><a href="{{ route('rifas.index') }}">Rifas</a></li>
    <li><a href="{{ route('rifas.show', $rifa) }}">{{ $rifa->nome }}</a></li>
    <li><span>Cartelas</span></li>
@endsection

@section('content')
<div class="mb-3 d-flex justify-content-end">
    <a href="{{ route('rifas.cartelas.imprimir', $rifa) }}" class="btn btn-primary me-2" target="_blank">Imprimir cartelas</a>
    <a href="{{ route('rifas.show', $rifa) }}" class="btn btn-default">Voltar para números</a>
</div>

<div class="row">
    @foreach($cartelas as $cartela)
        <div class="col-md-4 mb-3">
            <section class="card">
                <header class="card-header">
                    <h2 class="card-title">{{ $cartela->identificador }}</h2>
                </header>
                <div class="card-body">
                    <div class="d-grid" style="grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 8px;">
                        @foreach($cartela->numeros as $numero)
                            @if(auth()->user()->can('sell', $rifa))
                                <button
                                    type="button"
                                    class="text-center p-2 border rounded bg-{{ $numero->status === 'vendido' ? 'success' : ($numero->status === 'reservado' ? 'warning' : 'light') }} js-numero-cartela"
                                    data-numero-id="{{ $numero->id }}"
                                    data-numero-label="{{ $numero->numero }}"
                                    data-numero-status="{{ $numero->status }}"
                                    data-cartela-id="{{ $cartela->id }}"
                                    data-cartela-label="{{ $cartela->identificador }}"
                                    data-comprador-nome="{{ $numero->comprador_nome }}"
                                    data-comprador-telefone="{{ $numero->comprador_telefone }}"
                                    data-vendedor-id="{{ $numero->vendedor_id }}"
                                    style="cursor: pointer;"
                                >
                                    <div><strong>{{ $numero->numero }}</strong></div>
                                    <small>{{ ucfirst($numero->status) }}</small>
                                </button>
                            @else
                                <div class="text-center p-2 border rounded bg-{{ $numero->status === 'vendido' ? 'success' : ($numero->status === 'reservado' ? 'warning' : 'light') }}">
                                    <div><strong>{{ $numero->numero }}</strong></div>
                                    <small>{{ ucfirst($numero->status) }}</small>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    @endforeach
</div>

{{ $cartelas->links() }}

@can('sell', $rifa)
<div class="modal fade" id="modalVendaCartela" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('rifas.vendas.rapida.store', $rifa) }}">
                @csrf
                <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar compra de número(s)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        Número clicado: <strong id="numeroClicadoLabel">-</strong>
                        <span class="ms-2">| Cartela: <strong id="cartelaClicadaLabel">-</strong></span>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Vendedor</label>
                            <select name="vendedor_id" class="form-control" required>
                                <option value="">Selecione</option>
                                @foreach($vendedores as $vendedor)
                                    <option value="{{ $vendedor->id }}">{{ $vendedor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Comprador</label>
                            <input type="text" name="comprador_nome" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="comprador_telefone" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo</label>
                            <select name="status" class="form-control">
                                <option value="vendido">Venda</option>
                                <option value="reservado">Reserva</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Quais números comprar</label>
                        <select id="numeroIdsSelect" name="numero_ids[]" class="form-control" multiple size="12" required>
                            <option value="" disabled>Clique em um número da cartela para carregar</option>
                        </select>
                        <small class="text-muted">Use Ctrl/Command para selecionar múltiplos números.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarVendaCartela" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formEditarVendaCartela" method="POST" action="{{ route('rifas.numeros.comprador.lote.update', $rifa) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="numero_id" id="editarNumeroId" value="">
                <div class="modal-header">
                    <h5 class="modal-title">Editar venda do número <span id="numeroEditarLabel">-</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label d-block mb-2">Aplicar alterações em</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input js-edit-scope" type="radio" name="escopo" id="escopoUm" value="um" checked>
                            <label class="form-check-label" for="escopoUm">Somente número clicado</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input js-edit-scope" type="radio" name="escopo" id="escopoSelecionados" value="selecionados">
                            <label class="form-check-label" for="escopoSelecionados">Números selecionados</label>
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="boxNumerosEditar">
                        <label class="form-label">Números para editar em lote</label>
                        <select id="editarNumeroIds" name="numero_ids[]" class="form-control" multiple size="8">
                            @foreach($numerosEditaveis as $numeroEditavel)
                                <option value="{{ $numeroEditavel->id }}">
                                    {{ $numeroEditavel->numero }} ({{ ucfirst($numeroEditavel->status) }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Use Ctrl/Command para selecionar vários números.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Vendedor</label>
                        <select id="editarVendedorId" name="vendedor_id" class="form-control">
                            <option value="">Selecione</option>
                            @foreach($vendedores as $vendedor)
                                <option value="{{ $vendedor->id }}">{{ $vendedor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comprador</label>
                        <input id="editarCompradorNome" type="text" name="comprador_nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input id="editarCompradorTelefone" type="text" name="comprador_telefone" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
@can('sell', $rifa)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('modalVendaCartela');
        if (!modalElement) return;

        const modal = new bootstrap.Modal(modalElement);
        const modalEditarElement = document.getElementById('modalEditarVendaCartela');
        const modalEditar = modalEditarElement ? new bootstrap.Modal(modalEditarElement) : null;
        const numeroLabel = document.getElementById('numeroClicadoLabel');
        const cartelaLabel = document.getElementById('cartelaClicadaLabel');
        const numeroSelect = document.getElementById('numeroIdsSelect');
        const numeroButtons = document.querySelectorAll('.js-numero-cartela');
        const formEditar = document.getElementById('formEditarVendaCartela');
        const numeroEditarLabel = document.getElementById('numeroEditarLabel');
        const editarNumeroId = document.getElementById('editarNumeroId');
        const editarCompradorNome = document.getElementById('editarCompradorNome');
        const editarCompradorTelefone = document.getElementById('editarCompradorTelefone');
        const editarVendedorId = document.getElementById('editarVendedorId');
        const editarNumeroIds = document.getElementById('editarNumeroIds');
        const scopeRadios = document.querySelectorAll('.js-edit-scope');
        const boxNumerosEditar = document.getElementById('boxNumerosEditar');

        function atualizarVisibilidadeEscopo() {
            const scopeSelecionado = document.querySelector('.js-edit-scope:checked');
            const isSelecionados = scopeSelecionado && scopeSelecionado.value === 'selecionados';
            if (boxNumerosEditar) {
                boxNumerosEditar.classList.toggle('d-none', !isSelecionados);
            }
            if (editarNumeroIds) {
                editarNumeroIds.required = !!isSelecionados;
            }
        }

        scopeRadios.forEach(function (radio) {
            radio.addEventListener('change', atualizarVisibilidadeEscopo);
        });
        atualizarVisibilidadeEscopo();

        function preencherNumerosDaCartela(cartelaId, numeroIdSelecionado) {
            if (!numeroSelect) return;

            numeroSelect.innerHTML = '';

            const botoesDaCartela = document.querySelectorAll(
                '.js-numero-cartela[data-cartela-id="' + cartelaId + '"]'
            );

            let totalOpcoes = 0;
            botoesDaCartela.forEach(function (btn) {
                const status = btn.getAttribute('data-numero-status');
                if (status !== 'disponivel') {
                    return;
                }

                const id = btn.getAttribute('data-numero-id');
                const label = btn.getAttribute('data-numero-label');
                const option = document.createElement('option');
                option.value = id;
                option.textContent = label + ' (' + status + ')';
                option.selected = (id === numeroIdSelecionado);
                numeroSelect.appendChild(option);
                totalOpcoes++;
            });

            if (totalOpcoes === 0) {
                const option = document.createElement('option');
                option.disabled = true;
                option.textContent = 'Não há números disponíveis nesta cartela.';
                numeroSelect.appendChild(option);
            }
        }

        numeroButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const numeroId = this.getAttribute('data-numero-id');
                const numeroTexto = this.getAttribute('data-numero-label');
                const numeroStatus = this.getAttribute('data-numero-status');
                const cartelaId = this.getAttribute('data-cartela-id');
                const cartelaTexto = this.getAttribute('data-cartela-label');
                const compradorNome = this.getAttribute('data-comprador-nome') || '';
                const compradorTelefone = this.getAttribute('data-comprador-telefone') || '';
                const vendedorId = this.getAttribute('data-vendedor-id') || '';

                if (numeroStatus === 'vendido' || numeroStatus === 'reservado') {
                    if (numeroEditarLabel) numeroEditarLabel.textContent = numeroTexto || '-';
                    if (editarNumeroId) editarNumeroId.value = numeroId;
                    if (editarCompradorNome) editarCompradorNome.value = compradorNome;
                    if (editarCompradorTelefone) editarCompradorTelefone.value = compradorTelefone;
                    if (editarVendedorId) editarVendedorId.value = vendedorId;
                    if (editarNumeroIds) {
                        Array.from(editarNumeroIds.options).forEach(function (opt) {
                            opt.selected = (opt.value === numeroId);
                        });
                    }
                    if (formEditar && document.getElementById('escopoUm')) {
                        document.getElementById('escopoUm').checked = true;
                        atualizarVisibilidadeEscopo();
                    }
                    if (modalEditar) modalEditar.show();
                    return;
                }

                if (numeroLabel) {
                    numeroLabel.textContent = numeroTexto || '-';
                }
                if (cartelaLabel) {
                    cartelaLabel.textContent = cartelaTexto || '-';
                }

                preencherNumerosDaCartela(cartelaId, numeroId);

                modal.show();
            });
        });
    });
</script>
@endcan
@endpush
