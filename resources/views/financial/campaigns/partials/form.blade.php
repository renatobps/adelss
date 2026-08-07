@php($c = $campaign ?? null)

<div class="row">
    <div class="col-md-8 mb-3">
        <label class="form-label">Nome da campanha *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $c?->name) }}" required maxlength="255"
               placeholder="Ex: Festa das Crianças 2026">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Departamento responsável</label>
        <select name="department_id" class="form-select">
            <option value="">— Nenhum —</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $c?->department_id) == $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Descrição</label>
    <textarea name="description" class="form-control" rows="2" placeholder="Objetivo da campanha (opcional)">{{ old('description', $c?->description) }}</textarea>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Valor da parcela (R$) *</label>
        <input type="number" name="installment_amount" class="form-control" step="0.01" min="0.01" required
               value="{{ old('installment_amount', $c?->installment_amount) }}" placeholder="0,00">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Qtd. de parcelas *</label>
        <input type="number" name="installments_count" class="form-control" min="1" max="120" required
               value="{{ old('installments_count', $c?->installments_count ?? 1) }}">
        <small class="text-muted">Geradas ao adicionar cada patrocinador.</small>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Vencimento da 1ª parcela</label>
        <input type="date" name="first_due_date" class="form-control"
               value="{{ old('first_due_date', $c?->first_due_date?->format('Y-m-d')) }}">
        <small class="text-muted">As demais somam 1 mês cada.</small>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Meta de arrecadação (R$)</label>
        <input type="number" name="goal_amount" class="form-control" step="0.01" min="0"
               value="{{ old('goal_amount', $c?->goal_amount) }}" placeholder="Opcional">
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Início da campanha</label>
        <input type="date" name="start_date" class="form-control"
               value="{{ old('start_date', $c?->start_date?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Fim da campanha</label>
        <input type="date" name="end_date" class="form-control"
               value="{{ old('end_date', $c?->end_date?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Status *</label>
        <select name="status" class="form-select" required>
            @foreach(['ativa' => 'Ativa', 'encerrada' => 'Encerrada', 'cancelada' => 'Cancelada'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $c?->status ?? 'ativa') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Mensagem do recibo</label>
    <textarea name="receipt_message" class="form-control" rows="2" maxlength="1000"
              placeholder="Texto ou versículo impresso no recibo. Ex: “Cada um contribua segundo propôs no seu coração...” (2 Co 9:7)">{{ old('receipt_message', $c?->receipt_message) }}</textarea>
</div>
