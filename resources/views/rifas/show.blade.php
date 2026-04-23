@extends('layouts.porto')

@section('title', 'Detalhes da Rifa')
@section('page-title', $rifa->nome)

@section('breadcrumbs')
    <li><a href="{{ route('rifas.index') }}">Rifas</a></li>
    <li><span>{{ $rifa->nome }}</span></li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-8">
        <h4>{{ $rifa->nome }}</h4>
        <p class="mb-0">Sorteio: {{ optional($rifa->data_sorteio)->format('d/m/Y') ?: 'Não definido' }}</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('rifas.vendas.rapida.create', $rifa) }}" class="btn btn-success">Venda rápida</a>
        <a href="{{ route('rifas.cartelas.index', $rifa) }}" class="btn btn-default">Ver cartelas</a>
        @can('update', $rifa)
            <a href="{{ route('rifas.edit', $rifa) }}" class="btn btn-primary">Editar</a>
        @endcan
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <section class="card card-featured-left card-featured-primary">
            <div class="card-body">
                <h5>Total de números</h5>
                <h2>{{ $resumo['total'] }}</h2>
            </div>
        </section>
    </div>
    <div class="col-md-3">
        <section class="card card-featured-left card-featured-success">
            <div class="card-body">
                <h5>Vendidos</h5>
                <h2>{{ $resumo['vendidos'] }}</h2>
            </div>
        </section>
    </div>
    <div class="col-md-3">
        <section class="card card-featured-left card-featured-warning">
            <div class="card-body">
                <h5>Reservados</h5>
                <h2>{{ $resumo['reservados'] }}</h2>
            </div>
        </section>
    </div>
    <div class="col-md-3">
        <section class="card card-featured-left card-featured-info">
            <div class="card-body">
                <h5>Arrecadado</h5>
                <h2>R$ {{ number_format($resumo['arrecadado'], 2, ',', '.') }}</h2>
            </div>
        </section>
    </div>
</div>

<section class="card">
    <header class="card-header">
        <h2 class="card-title">Filtros de números</h2>
    </header>
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="status" class="form-control">
                    <option value="">Todos os status</option>
                    <option value="disponivel" @selected(request('status') === 'disponivel')>Disponível</option>
                    <option value="reservado" @selected(request('status') === 'reservado')>Reservado</option>
                    <option value="vendido" @selected(request('status') === 'vendido')>Vendido</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="vendedor_id" class="form-control">
                    <option value="">Todos os vendedores</option>
                    @foreach($vendedores as $vendedor)
                        <option value="{{ $vendedor->id }}" @selected((string) request('vendedor_id') === (string) $vendedor->id)>
                            {{ $vendedor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="comprador" value="{{ request('comprador') }}" class="form-control" placeholder="Comprador">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Aplicar</button>
                <a href="{{ route('rifas.show', $rifa) }}" class="btn btn-default">Limpar</a>
            </div>
        </form>
    </div>
</section>

<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Números da rifa</h2>
        @can('update', $rifa)
        <form action="{{ route('rifas.sortear', $rifa) }}" method="POST" id="sortear-form">
            @csrf
            <button class="btn btn-warning btn-sm" type="button" id="btn-sortear-ganhador">Sortear ganhador</button>
        </form>
        @endcan
    </header>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Status</th>
                        <th>Comprador</th>
                        <th>Telefone</th>
                        <th>Vendedor</th>
                        <th>Data venda</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($numeros as $numero)
                        <tr>
                            <td><strong>{{ $numero->numero }}</strong></td>
                            <td>
                                <span class="badge bg-{{ $numero->status === 'vendido' ? 'success' : ($numero->status === 'reservado' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($numero->status) }}
                                </span>
                            </td>
                            <td>{{ $numero->comprador_nome ?: '-' }}</td>
                            <td>{{ $numero->comprador_telefone ?: '-' }}</td>
                            <td>{{ $numero->vendedor?->name ?: '-' }}</td>
                            <td>{{ optional($numero->data_venda)->format('d/m/Y H:i') ?: '-' }}</td>
                            <td class="text-end">
                                @if($numero->status !== 'disponivel')
                                <button class="btn btn-xs btn-default" type="button" data-bs-toggle="collapse" data-bs-target="#editar-{{ $numero->id }}">
                                    Editar comprador
                                </button>
                                <form action="{{ route('rifas.numeros.cancelar', $numero) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button class="btn btn-xs btn-danger" type="submit">Cancelar venda</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @if($numero->status !== 'disponivel')
                            <tr class="collapse" id="editar-{{ $numero->id }}">
                                <td colspan="7">
                                    <form method="POST" action="{{ route('rifas.numeros.comprador.update', $numero) }}" class="row g-2">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-md-5">
                                            <input type="text" name="comprador_nome" class="form-control" value="{{ $numero->comprador_nome }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="comprador_telefone" class="form-control" value="{{ $numero->comprador_telefone }}" placeholder="Telefone">
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-primary btn-sm" type="submit">Salvar</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Nenhum número encontrado para os filtros informados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $numeros->links() }}
    </div>
</section>

@can('update', $rifa)
<div class="modal fade" id="modalSorteioRifa" tabindex="-1" aria-labelledby="modalSorteioRifaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSorteioRifaLabel">Sorteio de Ganhador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body text-center">
                <div id="sorteio-loading">
                    <p class="mb-2">Preparando sorteio...</p>
                    <div id="sorteio-countdown" class="display-3 fw-bold text-warning">5</div>
                    <p class="text-muted mb-0">Aguarde o resultado</p>
                </div>

                <div id="sorteio-resultado" class="d-none">
                    <h4 class="mb-3 text-success">Ganhador sorteado!</h4>
                    <p class="mb-1"><strong>Número:</strong> <span id="resultado-numero">-</span></p>
                    <p class="mb-1"><strong>Comprador:</strong> <span id="resultado-comprador">-</span></p>
                    <p class="mb-0"><strong>Vendedor:</strong> <span id="resultado-vendedor">-</span></p>
                </div>

                <div id="sorteio-erro" class="alert alert-danger d-none mt-3 mb-0"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

@can('update', $rifa)
@push('scripts')
<script>
    (function () {
        const form = document.getElementById('sortear-form');
        const sortearButton = document.getElementById('btn-sortear-ganhador');
        const modalElement = document.getElementById('modalSorteioRifa');

        if (!form || !sortearButton || !modalElement || typeof bootstrap === 'undefined') {
            return;
        }

        const modal = new bootstrap.Modal(modalElement);
        const loadingContainer = document.getElementById('sorteio-loading');
        const countdownElement = document.getElementById('sorteio-countdown');
        const resultadoContainer = document.getElementById('sorteio-resultado');
        const erroContainer = document.getElementById('sorteio-erro');
        const resultadoNumero = document.getElementById('resultado-numero');
        const resultadoComprador = document.getElementById('resultado-comprador');
        const resultadoVendedor = document.getElementById('resultado-vendedor');
        const csrfToken = form.querySelector('input[name="_token"]')?.value;

        let countdownTimer = null;

        function resetModal() {
            loadingContainer.classList.remove('d-none');
            resultadoContainer.classList.add('d-none');
            erroContainer.classList.add('d-none');
            erroContainer.textContent = '';
            countdownElement.textContent = '5';
            resultadoNumero.textContent = '-';
            resultadoComprador.textContent = '-';
            resultadoVendedor.textContent = '-';
        }

        async function executarSorteio() {
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: '{}'
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Erro ao realizar sorteio.');
                }

                loadingContainer.classList.add('d-none');
                resultadoContainer.classList.remove('d-none');
                resultadoNumero.textContent = payload.data?.numero || '-';
                resultadoComprador.textContent = payload.data?.comprador || '-';
                resultadoVendedor.textContent = payload.data?.vendedor || '-';
            } catch (error) {
                loadingContainer.classList.add('d-none');
                erroContainer.classList.remove('d-none');
                erroContainer.textContent = error.message || 'Erro ao realizar sorteio.';
            } finally {
                sortearButton.disabled = false;
            }
        }

        function iniciarContagem() {
            let contagem = 5;
            countdownElement.textContent = String(contagem);

            countdownTimer = setInterval(() => {
                contagem -= 1;

                if (contagem <= 0) {
                    clearInterval(countdownTimer);
                    countdownTimer = null;
                    executarSorteio();
                    return;
                }

                countdownElement.textContent = String(contagem);
            }, 1000);
        }

        sortearButton.addEventListener('click', function () {
            sortearButton.disabled = true;
            resetModal();
            modal.show();
            iniciarContagem();
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            if (countdownTimer) {
                clearInterval(countdownTimer);
                countdownTimer = null;
            }

            sortearButton.disabled = false;
        });
    })();
</script>
@endpush
@endcan
