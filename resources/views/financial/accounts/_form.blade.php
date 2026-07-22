@php
    $account = $account ?? null;
    $selectedType = old('type', $account?->type ?? 'caixa');
    $selectedColor = old('color', $account?->color ?? ($colors[0] ?? '#ef4444'));
    $isActive = old('is_active', $account?->is_active ?? true);
    if ($isActive === '0' || $isActive === 0) {
        $isActive = false;
    } else {
        $isActive = (bool) $isActive;
    }
@endphp

<div class="mb-3">
    <label for="{{ $prefix }}name" class="form-label">Nome da Conta <span class="text-danger">*</span></label>
    <input type="text"
           class="form-control"
           id="{{ $prefix }}name"
           name="name"
           value="{{ old('name', $account?->name) }}"
           placeholder="Ex: Caixa Sede"
           required>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label for="{{ $prefix }}type" class="form-label">Tipo <span class="text-danger">*</span></label>
        <select class="form-select" id="{{ $prefix }}type" name="type" required>
            @foreach($types as $value => $label)
                <option value="{{ $value }}" {{ $selectedType === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label for="{{ $prefix }}initial_balance" class="form-label">Saldo Inicial (R$) <span class="text-danger">*</span></label>
        <input type="number"
               step="0.01"
               class="form-control"
               id="{{ $prefix }}initial_balance"
               name="initial_balance"
               value="{{ old('initial_balance', $account?->initial_balance ?? '0') }}"
               required>
    </div>
</div>

<div class="mb-3">
    <label for="{{ $prefix }}bank_name" class="form-label">Banco</label>
    <input type="text"
           class="form-control"
           id="{{ $prefix }}bank_name"
           name="bank_name"
           value="{{ old('bank_name', $account?->bank_name) }}"
           placeholder="Ex: Mercado Pago">
</div>

<div class="mb-3">
    <label class="form-label d-block">Cor de Identificação <span class="text-danger">*</span></label>
    <div class="financial-account-color-grid">
        @foreach($colors as $color)
            <label class="financial-account-color-option" title="{{ $color }}">
                <input type="radio"
                       name="color"
                       value="{{ $color }}"
                       {{ $selectedColor === $color ? 'checked' : '' }}
                       required>
                <span style="background: {{ $color }};"></span>
            </label>
        @endforeach
    </div>
</div>

<div class="financial-account-active-box mb-1">
    <div>
        <strong>Conta Ativa</strong>
        <p>Desativar preserva todo o histórico — a conta apenas some das listas de novas transações. Você pode reativá-la a qualquer momento na aba "Inativas".</p>
    </div>
    <div class="form-check form-switch m-0">
        <input type="hidden" name="is_active" value="0">
        <input class="form-check-input"
               type="checkbox"
               role="switch"
               id="{{ $prefix }}is_active"
               name="is_active"
               value="1"
               {{ $isActive ? 'checked' : '' }}>
    </div>
</div>
