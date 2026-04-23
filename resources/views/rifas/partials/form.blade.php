<div class="row">
    <div class="col-md-8 mb-3">
        <label class="form-label">Nome / Descrição da rifa</label>
        <input type="text" name="nome" class="form-control" value="{{ old('nome', ($rifa ?? null)?->nome) }}" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-control" required>
            @foreach(['ativa' => 'Ativa', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada'] as $valor => $label)
                <option value="{{ $valor }}" @selected(old('status', ($rifa ?? null)?->status ?? 'ativa') === $valor)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Quantidade total de números</label>
        <input type="number" name="quantidade_numeros" class="form-control"
               value="{{ old('quantidade_numeros', ($rifa ?? null)?->quantidade_numeros ?? 1000) }}"
               min="1" @if(isset($rifa) && $rifa) readonly @endif required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Valor por número</label>
        <input type="number" step="0.01" name="valor_numero" class="form-control"
               value="{{ old('valor_numero', ($rifa ?? null)?->valor_numero) }}" min="0.01" required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Números por cartela</label>
        <input type="number" name="numeros_por_cartela" class="form-control"
               value="{{ old('numeros_por_cartela', ($rifa ?? null)?->numeros_por_cartela ?? 10) }}"
               min="1" @if(isset($rifa) && $rifa) readonly @endif required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Data do sorteio</label>
        <input type="date" name="data_sorteio" class="form-control"
               value="{{ old('data_sorteio', isset($rifa) && $rifa && $rifa->data_sorteio ? $rifa->data_sorteio->format('Y-m-d') : '') }}">
    </div>
</div>
