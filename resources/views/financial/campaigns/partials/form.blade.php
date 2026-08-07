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

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Chave PIX</label>
        <input type="text" name="pix_key" class="form-control" maxlength="255"
               value="{{ old('pix_key', $c?->pix_key) }}"
               placeholder="CPF, CNPJ, telefone, e-mail ou chave aleatória">
        <small class="text-muted">Impressa no carnê, nas parcelas pendentes, para facilitar o pagamento.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Nome do recebedor (PIX)</label>
        <input type="text" name="pix_recipient" class="form-control" maxlength="255"
               value="{{ old('pix_recipient', $c?->pix_recipient) }}"
               placeholder="Nome que aparece ao pagar (confirmação do destinatário)">
    </div>
</div>

<div class="row">
    <div class="col-md-8 mb-3">
        <label class="form-label">Mensagem do recibo</label>
        <textarea name="receipt_message" class="form-control" rows="2" maxlength="1000"
                  placeholder="Texto ou versículo impresso no recibo. Ex: “Cada um contribua segundo propôs no seu coração...” (2 Co 9:7)">{{ old('receipt_message', $c?->receipt_message) }}</textarea>
        <small class="text-muted">Emojis são exibidos no WhatsApp, mas removidos automaticamente do PDF impresso.</small>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Cor do carnê</label>
        @php($accentValue = old('accent_color', $c?->accent_color))
        <div class="form-check form-switch mb-1">
            <input class="form-check-input" type="checkbox" id="accentToggle" @checked((bool) $accentValue)>
            <label class="form-check-label small" for="accentToggle">Usar cor personalizada</label>
        </div>
        <input type="color" name="accent_color" id="accentColor" class="form-control form-control-color"
               value="{{ $accentValue ?: '#0088CC' }}" @disabled(!$accentValue) title="Cor de destaque do carnê em PDF">
        <small class="text-muted">Sem cor personalizada, usa a cor do departamento ou o azul padrão.</small>
    </div>
</div>

<hr class="my-3">

<h6 class="mb-1"><i class="bx bxl-whatsapp me-1"></i>Mensagem programada aos patrocinadores</h6>
<p class="text-muted small mb-3">
    Enviada por WhatsApp, uma única vez, na data escolhida — a todos os patrocinadores da campanha com telefone cadastrado.
    Útil para avisar o dia do pagamento.
</p>
<div class="row">
    <div class="col-md-8 mb-3">
        <label class="form-label">Mensagem a enviar</label>
        <textarea name="reminder_message" class="form-control" rows="3" maxlength="1000"
                  placeholder="Ex: Olá {nome}! Amanhã é o dia do pagamento da parcela de {valor_parcela} da campanha {campanha}. Deus abençoe!">{{ old('reminder_message', $c?->reminder_message) }}</textarea>
        <small class="text-muted">Você pode usar <code>{nome}</code>, <code>{campanha}</code> e <code>{valor_parcela}</code> — serão substituídos automaticamente.</small>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Data do envio</label>
        <input type="date" name="reminder_send_date" class="form-control"
               value="{{ old('reminder_send_date', $c?->reminder_send_date?->format('Y-m-d')) }}">
        @if($c?->reminder_sent_at)
            <small class="text-success d-block mt-1">
                <i class="bx bx-check-double"></i> Enviada em {{ $c->reminder_sent_at->format('d/m/Y H:i') }}.
                Para reenviar, escolha uma nova data.
            </small>
        @else
            <small class="text-muted">O envio ocorre por volta das 09:10 do dia escolhido.</small>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('accentToggle');
    const color = document.getElementById('accentColor');
    toggle?.addEventListener('change', function () {
        color.disabled = !this.checked;
    });
});
</script>
@endpush
