<div class="table-responsive er-table-wrap">
    <table class="table table-hover align-middle er-table">
        <thead>
            <tr>
                @if($canEditRegistrations)
                    <th style="width: 2.2rem;">
                        <input type="checkbox" class="form-check-input js-er-check-all" aria-label="Selecionar todos">
                    </th>
                @endif
                <th>Inscrição</th>
                <th>Nome</th>
                <th>Status</th>
                <th>Contato</th>
                @if($event->is_paid)
                    <th>Pagamento</th>
                    <th class="text-end">Valor</th>
                @endif
                <th class="text-center" title="Comprovante enviado">Compr.</th>
                <th>Data</th>
                <th style="width: 2.5rem;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($registrations as $r)
                <tr class="{{ $r->checked_in_at ? 'er-row--presente' : '' }} {{ $r->trashed() ? 'er-row--excluida' : '' }} {{ $r->status === \App\Models\EventRegistration::STATUS_CANCELADO ? 'er-row--cancelada' : '' }}"
                    data-registration="{{ $payloads[$r->id] }}">
                    @if($canEditRegistrations)
                        <td>
                            <input type="checkbox" class="form-check-input js-er-check" value="{{ $r->id }}"
                                   aria-label="Selecionar {{ $r->name }}">
                        </td>
                    @endif
                    <td>
                        <span class="fw-semibold">{{ $r->registration_number ?: '—' }}</span>
                        @if(isset($duplicateIds[$r->id]))
                            <span class="er-badge er-badge--dup ms-1" title="Mesmo contato de outra inscrição neste evento">Dup.</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="er-avatar" style="background: {{ $r->avatar_color }};">{{ $r->initials }}</span>
                            <span class="er-name">{{ $r->name }}</span>
                        </div>
                    </td>
                    <td>
                        @include('agenda.eventos.partials.registrations.status-badge', ['suffix' => 'row'])
                        @if($r->checked_in_at)
                            <span class="er-badge er-badge--presente ms-1"
                                  title="Check-in em {{ $r->checked_in_at->format('d/m/Y H:i') }}">Presente</span>
                        @endif
                    </td>
                    <td>
                        <div class="text-truncate" style="max-width: 15rem;">{{ $r->email ?: '—' }}</div>
                        <div class="er-muted d-flex align-items-center gap-1">
                            <span>{{ $r->phone ?: '—' }}</span>
                            @if($r->whatsapp_url)
                                <a href="{{ $r->whatsapp_url }}" target="_blank" rel="noopener" class="er-whats"
                                   title="Conversar no WhatsApp"><i class="bx bxl-whatsapp"></i></a>
                            @endif
                        </div>
                    </td>
                    @if($event->is_paid)
                        @php
                            $payment = $r->payment;
                            $payStatus = strtolower((string) ($payment->status ?? 'pendente'));
                            $payMethod = strtolower((string) ($payment->payment_method ?? ''));
                            $payClass = match ($payStatus) {
                                'approved' => 'confirmado',
                                'rejected', 'cancelled', 'refunded', 'charged_back' => 'cancelado',
                                default => 'pendente',
                            };
                        @endphp
                        <td>
                            <span class="er-badge er-badge--{{ $payClass }} text-uppercase">{{ $payStatus }}</span>
                            @if($payMethod === 'pix' && !empty($payment?->qr_code_text))
                                <button type="button" class="btn btn-link btn-sm p-0 d-block"
                                        data-bs-toggle="modal" data-bs-target="#pixModal{{ $r->id }}">QR Code PIX</button>
                            @endif
                        </td>
                        <td class="text-end">
                            {{ $payment ? 'R$ '.number_format((float) $payment->amount, 2, ',', '.') : '—' }}
                        </td>
                    @endif
                    <td class="text-center">
                        @if($r->receipt_sent_at)
                            <i class="bx bx-check-circle er-receipt-ok"
                               title="Comprovante enviado em {{ $r->receipt_sent_at->format('d/m/Y H:i') }}"></i>
                        @else
                            <i class="bx bx-circle er-receipt-off" title="Comprovante não enviado"></i>
                        @endif
                    </td>
                    <td class="er-muted">{{ $r->created_at?->format('d/m · H:i') }}</td>
                    <td>
                        @include('agenda.eventos.partials.registrations.actions-menu', ['suffix' => 'row'])
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        {{ $filters['q'] !== '' || $filters['situacao'] !== 'todos'
                            ? 'Nenhuma inscrição encontrada com esses filtros.'
                            : 'Nenhuma inscrição neste evento.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
