<fieldset class="border rounded p-3 mb-3">
    <legend class="float-none w-auto px-2" style="font-size:1rem;">Pagamento por PIX</legend>
    <div class="row">
        <div class="col-md-6 mb-2">
            <label for="rifa-pix-key" class="form-label">Chave PIX</label>
            <input type="text" id="rifa-pix-key" name="pix_chave" class="form-control" maxlength="255" value="{{ old('pix_chave', ($rifa ?? null)?->pix_chave) }}" placeholder="CPF, CNPJ, e-mail, telefone ou chave aleatória">
        </div>
        <div class="col-md-6 mb-2">
            <label for="rifa-pix-receiver" class="form-label">Nome do recebedor</label>
            <input type="text" id="rifa-pix-receiver" name="pix_recebedor" class="form-control" maxlength="120" value="{{ old('pix_recebedor', ($rifa ?? null)?->pix_recebedor) }}" placeholder="Nome do titular da conta">
        </div>
    </div>
    <small class="text-muted">Informe o nome como aparece no banco. Esses dados serão exibidos nas cartelas e no envio por WhatsApp. Para cadastrar o PIX, preencha os dois campos.</small>
</fieldset>
<div class="mb-3">
    <label for="rifa-department" class="form-label">Departamento responsável</label>
    <select id="rifa-department" name="department_id" class="form-control">
        <option value="">Sem departamento — selecionar membros abaixo</option>
        @foreach($departments as $department)
            <option value="{{ $department->id }}" @selected((string) old('department_id', ($rifa ?? null)?->department_id) === (string) $department->id)>{{ $department->name }}</option>
        @endforeach
    </select>
    <small class="text-muted">Você pode usar somente o departamento, somente membros selecionados abaixo, ou as duas opções. Membros presentes nas duas listas não são duplicados na distribuição.</small>
    @if(isset($rifa))
        <p class="small text-muted mt-1">Para uma rifa existente, salve o departamento e use “Distribuir cartelas disponíveis”. O departamento não pode ser trocado depois de haver cartelas atribuídas.</p>
    @endif
</div>
@php
    $vendedoresMarcados = collect(old('vendedor_ids', session()->hasOldInput('atualizar_vendedores') ? [] : (isset($rifa) ? $rifa->vendedoresSelecionados->pluck('id')->all() : [])))->map(fn ($id) => (string) $id)->all();
@endphp
<div class="mb-3">
    <label for="rifa-member-search" class="form-label">Adicionar vendedores da lista de membros</label>
    <input type="hidden" name="atualizar_vendedores" value="1">
    <input type="search" id="rifa-member-search" class="form-control mb-2" placeholder="Buscar membro pelo nome" aria-controls="rifa-member-list">
    <div id="rifa-member-list" class="border rounded p-3" style="max-height:260px;overflow-y:auto;">
        @forelse($membrosDisponiveis as $membro)
            <div class="form-check mb-2 js-rifa-member" data-name="{{ $membro->name }}">
                <input class="form-check-input" type="checkbox" name="vendedor_ids[]" id="rifa-member-{{ $membro->id }}" value="{{ $membro->id }}" @checked(in_array((string) $membro->id, $vendedoresMarcados, true))>
                <label class="form-check-label" for="rifa-member-{{ $membro->id }}">{{ $membro->name }}
                    <small class="text-muted">{{ $membro->phone ? ' — '.$membro->phone : ' — sem telefone para WhatsApp' }}{{ $membro->status !== 'ativo' ? ' (inativo: não recebe novas cartelas)' : '' }}</small>
                </label>
            </div>
        @empty
            <p class="text-muted mb-0">Não há membros ativos cadastrados.</p>
        @endforelse
    </div>
    <p id="rifa-member-count" class="small text-muted mt-2" aria-live="polite"></p>
    <small class="text-muted">Ao criar uma rifa ativa, as cartelas serão distribuídas entre os membros ativos do departamento e os vendedores selecionados. Se nenhuma opção for escolhida, não haverá distribuição automática. Nas rifas existentes, salve e use “Distribuir cartelas disponíveis”; as cartelas já atribuídas serão preservadas.</small>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var search = document.getElementById('rifa-member-search');
    var rows = document.querySelectorAll('.js-rifa-member');
    var counter = document.getElementById('rifa-member-count');
    var normalize = function (value) { return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); };
    var update = function () {
        var term = normalize(search.value.trim());
        var selected = 0;
        rows.forEach(function (row) {
            row.hidden = !normalize(row.getAttribute('data-name')).includes(term);
            if (row.querySelector('input').checked) selected++;
        });
        counter.textContent = selected + ' membro(s) selecionado(s) individualmente.';
    };
    search.addEventListener('input', update);
    rows.forEach(function (row) { row.querySelector('input').addEventListener('change', update); });
    update();
});
</script>
@endpush
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
