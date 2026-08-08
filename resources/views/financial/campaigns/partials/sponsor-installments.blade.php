@php
    use App\Models\CampaignInstallment;

    // Partial autônoma: é renderizada tanto pelo AJAX do accordion quanto pelo
    // painel lateral da visão em tabela, então resolve as permissões sozinha.
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $can = fn (string $action) => $isAdmin
        || $user->hasPermission('financial.campanhas.manage')
        || $user->hasPermission("financial.campanhas.{$action}");
    $canEdit = $can('edit');
    $canDelete = $can('delete');
    $canPay = $can('pagar');
    $canReverse = $can('estornar');

    $installments = $sponsor->installments;
    $totalCount = $installments->count();
@endphp

<div class="d-flex justify-content-end gap-2 mb-2 flex-wrap">
    <a href="{{ route('financial.campaigns.sponsors.carne', $sponsor) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bx bx-printer me-1"></i>Gerar carnê (PDF)
    </a>
    @if($canEdit)
        <button type="button" class="btn btn-outline-secondary btn-sm js-edit-sponsor"
                data-action="{{ route('financial.campaigns.sponsors.update', $sponsor) }}"
                data-name="{{ $sponsor->name }}" data-phone="{{ $sponsor->phone }}" data-notes="{{ $sponsor->notes }}">
            <i class="bx bx-edit me-1"></i>Editar
        </button>
    @endif
    @if($canDelete)
        <form method="POST" action="{{ route('financial.campaigns.sponsors.destroy', $sponsor) }}"
              onsubmit="return confirm('Remover o patrocinador {{ $sponsor->name }} e suas parcelas pendentes?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bx bx-trash me-1"></i>Remover</button>
        </form>
    @endif
</div>

<div class="table-responsive">
    <table class="table table-sm align-middle mb-2">
        <thead>
            <tr>
                @if($canPay)
                    <th style="width: 30px;"></th>
                @endif
                <th>Parcela</th>
                <th>Valor</th>
                <th>Vencimento</th>
                <th>Status</th>
                <th>Pagamento</th>
                <th>Recibo</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($installments as $installment)
                <tr>
                    @if($canPay)
                        <td>
                            @if($installment->status === CampaignInstallment::STATUS_PENDENTE)
                                <input type="checkbox" class="form-check-input js-batch-check"
                                       data-sponsor="{{ $sponsor->id }}" value="{{ $installment->id }}">
                            @endif
                        </td>
                    @endif
                    <td>{{ $installment->installment_number }}/{{ $totalCount }}</td>
                    <td>R$ {{ number_format((float) $installment->amount, 2, ',', '.') }}</td>
                    <td>{{ $installment->due_date?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        @if($installment->isPaid())
                            <span class="badge bg-success">Pago</span>
                        @elseif($installment->isOverdue())
                            <span class="badge bg-danger">Em atraso</span>
                        @elseif($installment->status === CampaignInstallment::STATUS_CANCELADO)
                            <span class="badge bg-secondary">Cancelado</span>
                        @else
                            <span class="badge bg-warning text-dark">Pendente</span>
                        @endif
                    </td>
                    <td>
                        @if($installment->isPaid())
                            <small>{{ $installment->paid_at?->format('d/m/Y H:i') }}<br>{{ $installment->paymentMethodLabel() }}</small>
                        @else
                            <small class="text-muted">—</small>
                        @endif
                    </td>
                    <td>
                        @if($installment->receipt_number)
                            <small>{{ $installment->receipt_number }}</small>
                            @if($installment->receipt_sent_at)
                                <i class="bx bx-check-double text-success" title="Comprovante enviado em {{ $installment->receipt_sent_at->format('d/m/Y H:i') }}"></i>
                            @endif
                        @else
                            <small class="text-muted">—</small>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                            @if(!$installment->isPaid() && $installment->status !== CampaignInstallment::STATUS_CANCELADO && $canPay)
                                <button type="button" class="btn btn-success btn-sm js-pay-installment"
                                        data-action="{{ route('financial.campaigns.installments.pay', $installment) }}"
                                        data-label="Parcela {{ $installment->installment_number }}/{{ $totalCount }} — {{ $sponsor->name }} — R$ {{ number_format((float) $installment->amount, 2, ',', '.') }}">
                                    <i class="bx bx-check"></i> Pagar
                                </button>
                            @endif
                            @if($installment->isPaid())
                                <a href="{{ route('financial.campaigns.installments.receipt', $installment) }}"
                                   class="btn btn-outline-secondary btn-sm" title="Baixar recibo (PDF)">
                                    <i class="bx bx-download"></i>
                                </a>
                                @if($canPay)
                                    <form method="POST" action="{{ route('financial.campaigns.installments.resend', $installment) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm" title="Reenviar comprovante por WhatsApp">
                                            <i class="bx bxl-whatsapp"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($canReverse)
                                    <form method="POST" action="{{ route('financial.campaigns.installments.reverse', $installment) }}"
                                          onsubmit="return confirm('Estornar o pagamento da parcela {{ $installment->installment_number }}? Ela voltará para pendente e o recibo {{ $installment->receipt_number }} não será reutilizado.');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Estornar pagamento">
                                            <i class="bx bx-undo"></i>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($canPay && $installments->where('status', CampaignInstallment::STATUS_PENDENTE)->isNotEmpty())
    <button type="button" class="btn btn-outline-success btn-sm js-batch-pay" data-sponsor="{{ $sponsor->id }}" disabled>
        <i class="bx bx-check-double me-1"></i>Marcar selecionadas como pagas
    </button>
@endif
