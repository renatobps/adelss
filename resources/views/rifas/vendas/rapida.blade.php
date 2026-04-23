@extends('layouts.porto')

@section('title', 'Venda Rápida')
@section('page-title', 'Venda Rápida - ' . $rifa->nome)

@section('breadcrumbs')
    <li><a href="{{ route('rifas.index') }}">Rifas</a></li>
    <li><a href="{{ route('rifas.show', $rifa) }}">{{ $rifa->nome }}</a></li>
    <li><span>Venda rápida</span></li>
@endsection

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title">Registrar venda/reserva</h2>
    </header>
    <div class="card-body">
        <form method="POST" action="{{ route('rifas.vendas.rapida.store', $rifa) }}">
            @csrf

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Vendedor</label>
                    <select name="vendedor_id" class="form-control" required>
                        <option value="">Selecione</option>
                        @foreach($vendedores as $vendedor)
                            <option value="{{ $vendedor->id }}" @selected(old('vendedor_id') == $vendedor->id)>{{ $vendedor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Comprador</label>
                    <input type="text" name="comprador_nome" class="form-control" value="{{ old('comprador_nome') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="comprador_telefone" class="form-control" value="{{ old('comprador_telefone') }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Tipo da operação</label>
                    <select name="status" class="form-control">
                        <option value="vendido" @selected(old('status', 'vendido') === 'vendido')>Venda</option>
                        <option value="reservado" @selected(old('status') === 'reservado')>Reserva</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Números disponíveis/reservados</label>
                <select name="numero_ids[]" class="form-control" multiple size="12" required>
                    @foreach($numerosDisponiveis as $numero)
                        <option value="{{ $numero->id }}" @selected(collect(old('numero_ids'))->contains($numero->id))>
                            {{ $numero->numero }} ({{ ucfirst($numero->status) }})
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Use Ctrl/Command para selecionar múltiplos números.</small>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-success" type="submit">Confirmar operação</button>
                <a href="{{ route('rifas.show', $rifa) }}" class="btn btn-default">Voltar</a>
            </div>
        </form>
    </div>
</section>
@endsection
