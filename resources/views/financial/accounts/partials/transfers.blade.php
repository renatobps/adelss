<section class="financial-transfers mt-4">
    <div class="financial-transfers__head">
        <div>
            <h2 class="financial-transfers__title">Transferências entre contas</h2>
            <p class="financial-transfers__hint mb-0">
                Últimos lançamentos. Cada transferência diminui o saldo da conta de origem e aumenta o da conta de destino.
            </p>
        </div>
    </div>

    @if($transfers->isEmpty())
        <p class="text-muted small mb-0">Nenhuma transferência registrada.</p>
    @else
        <div class="table-responsive">
            <table class="table table-sm financial-transfers__table mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>De</th>
                        <th>Para</th>
                        <th>Observação</th>
                        <th class="text-end">Valor</th>
                        @if($canEdit)
                            <th class="text-end">Ações</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->transfer_date->format('d/m/Y') }}</td>
                            <td>{{ $transfer->fromAccount?->name ?? '—' }}</td>
                            <td>{{ $transfer->toAccount?->name ?? '—' }}</td>
                            <td>{{ $transfer->notes ?: '—' }}</td>
                            <td class="text-end fw-semibold">{{ $fmt($transfer->amount) }}</td>
                            @if($canEdit)
                                <td class="text-end">
                                    <form action="{{ route('financial.transfers.destroy', $transfer) }}"
                                          method="POST"
                                          onsubmit="return confirm('Estornar esta transferência? Os saldos voltam ao que eram antes.');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Estornar">
                                            <i class="bx bx-undo"></i>
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
