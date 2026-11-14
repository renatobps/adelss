@can('update', $rifa)
<section class="card mb-3">
    <header class="card-header"><h2 class="card-title">Distribuição e novos números</h2></header>
    <div class="card-body">
        <p>Departamento: <strong>{{ $rifa->department?->name ?? 'Não selecionado' }}</strong></p>
        @if($rifa->status === 'ativa')
            @if($rifa->department_id)
                <form method="POST" action="{{ route('rifas.cartelas.distribuir', $rifa) }}" class="mb-3">
                    @csrf
                    <button class="btn btn-outline-primary" type="submit">Distribuir cartelas disponíveis</button>
                    <p class="small text-muted mt-2">Atribui somente cartelas sem vendedor e com todos os números disponíveis. As cartelas já distribuídas e as vendas existentes serão preservadas.</p>
                </form>
            @else
                <p class="text-muted">Escolha um departamento em “Editar” para distribuir cartelas aos membros.</p>
            @endif
            <form method="POST" action="{{ route('rifas.numeros.adicionar', $rifa) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-sm-5"><label for="rifa-more-numbers" class="form-label">Quantidade de novos números</label><input id="rifa-more-numbers" name="quantidade" type="number" min="1" max="100000" required class="form-control" value="{{ old('quantidade') }}"></div>
                <div class="col-sm-7"><button type="submit" class="btn btn-primary">Adicionar números e gerar cartelas</button></div>
                <p class="small text-muted">A numeração continua a sequência atual, até o limite de 100.000. As cartelas anteriores não mudam. Com departamento definido, as novas cartelas também serão distribuídas.</p>
            </form>
        @else
            <p class="text-muted mb-0">A distribuição e a inclusão de números estão disponíveis apenas para rifas ativas.</p>
        @endif
    </div>
</section>
@endcan
