@php
    $selectedTypes = is_array(request('type'))
        ? request('type')
        : (request('type') ? [request('type')] : ($defaultTypes ?? []));
    $selectedStatus = is_array(request('status'))
        ? request('status')
        : (request('status') ? [request('status')] : ($defaultStatus ?? []));
@endphp

<div class="fr-filters__wide">
    <span class="form-label d-block">Tipo</span>
    <div class="fr-pills">
        <label class="fr-pill">
            <input type="checkbox" name="type[]" value="receita" @checked(in_array('receita', $selectedTypes, true))>
            <span>Receita</span>
        </label>
        <label class="fr-pill">
            <input type="checkbox" name="type[]" value="despesa" @checked(in_array('despesa', $selectedTypes, true))>
            <span>Despesa</span>
        </label>
    </div>
</div>
<div class="fr-filters__wide">
    <span class="form-label d-block">Status</span>
    <div class="fr-pills">
        <label class="fr-pill">
            <input type="checkbox" name="status[]" value="recebido" @checked(in_array('recebido', $selectedStatus, true))>
            <span>Recebido</span>
        </label>
        <label class="fr-pill">
            <input type="checkbox" name="status[]" value="pago" @checked(in_array('pago', $selectedStatus, true))>
            <span>Pago</span>
        </label>
        <label class="fr-pill">
            <input type="checkbox" name="status[]" value="a_receber" @checked(in_array('a_receber', $selectedStatus, true))>
            <span>A receber</span>
        </label>
        <label class="fr-pill">
            <input type="checkbox" name="status[]" value="a_pagar" @checked(in_array('a_pagar', $selectedStatus, true))>
            <span>A pagar</span>
        </label>
    </div>
</div>
