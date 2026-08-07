@extends('layouts.porto')

@section('title', 'Inscrições — '.$event->title)

@section('page-title', 'Inscrições')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Visão Geral</a></li>
    <li><span>Agenda</span></li>
    <li><a href="{{ route('agenda.eventos.index') }}">Eventos</a></li>
    <li><span>{{ $event->title }}</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="card-title mb-0">Inscrições</h2>
                    <p class="text-muted small mb-0 mt-1">{{ $event->title }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('agenda.eventos.index') }}" class="btn btn-default btn-sm"><i class="bx bx-arrow-back"></i> Voltar</a>
                    @if($event->public_slug)
                        <a href="{{ route('events.public.show', $event->public_slug) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="bx bx-link-external"></i> Página do evento
                        </a>
                    @endif
                    @if($canEditRegistrations)
                        <a href="{{ route('agenda.eventos.check-in', $event) }}" class="btn btn-success btn-sm">
                            <i class="bx bx-qr-scan"></i> Check-in
                        </a>
                    @endif
                    <a href="{{ route('agenda.eventos.edit', $event) }}" class="btn btn-primary btn-sm"><i class="bx bx-edit"></i> Editar evento</a>
                </div>
            </header>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if(!$canEditRegistrations)
                    <div class="alert alert-info mb-3">
                        Você pode visualizar as inscrições. Para alterar o status, é necessária permissão de edição de eventos.
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Inscrição</th>
                                <th>Nome</th>
                                <th style="min-width: 11rem;">Status</th>
                                <th>E-mail</th>
                                <th>Telefone</th>
                                <th>Pagamento</th>
                                <th>Valor</th>
                                <th>Comprovante</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registrations as $r)
                                @php
                                    $st = $r->status ?? \App\Models\EventRegistration::STATUS_PENDENTE;
                                @endphp
                                <tr>
                                    <td>
                                        <strong class="d-block">{{ $r->registration_number ?: '—' }}</strong>
                                        @if($r->checked_in_at)
                                            <span class="badge bg-success mt-1" title="Check-in em {{ $r->checked_in_at->format('d/m/Y H:i') }}">
                                                Presente {{ $r->checked_in_at->format('H:i') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $r->name }}</td>
                                    <td>
                                        @if($canEditRegistrations)
                                            <form method="post" action="{{ route('agenda.eventos.registrations.status', [$event, $r]) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="{{ \App\Models\EventRegistration::STATUS_PENDENTE }}" @selected($st === \App\Models\EventRegistration::STATUS_PENDENTE)>Pendente</option>
                                                    <option value="{{ \App\Models\EventRegistration::STATUS_CONFIRMADO }}" @selected($st === \App\Models\EventRegistration::STATUS_CONFIRMADO)>Confirmado</option>
                                                    <option value="{{ \App\Models\EventRegistration::STATUS_CANCELADO }}" @selected($st === \App\Models\EventRegistration::STATUS_CANCELADO)>Cancelado</option>
                                                </select>
                                            </form>
                                        @else
                                            @php
                                                $label = match ($st) {
                                                    \App\Models\EventRegistration::STATUS_CONFIRMADO => 'Confirmado',
                                                    \App\Models\EventRegistration::STATUS_CANCELADO => 'Cancelado',
                                                    default => 'Pendente',
                                                };
                                                $cls = match ($st) {
                                                    \App\Models\EventRegistration::STATUS_CONFIRMADO => 'success',
                                                    \App\Models\EventRegistration::STATUS_CANCELADO => 'secondary',
                                                    default => 'warning',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $cls }}">{{ $label }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $r->email ?: '—' }}</td>
                                    <td>{{ $r->phone ?: '—' }}</td>
                                    <td>
                                        @if($event->is_paid)
                                            @php
                                                $payment = $r->payment;
                                                $payStatus = strtolower((string) ($r->payment->status ?? 'pendente'));
                                                $payMethod = strtolower((string) ($payment->payment_method ?? ''));
                                                $payMethodLabel = match ($payMethod) {
                                                    'pix' => 'PIX',
                                                    'credit_card', 'card', 'master', 'visa', 'elo', 'amex', 'hipercard' => 'Cartão',
                                                    default => $payMethod !== '' ? strtoupper($payMethod) : '—',
                                                };
                                                $payClass = match ($payStatus) {
                                                    'approved' => 'success',
                                                    'rejected', 'cancelled', 'refunded', 'charged_back' => 'danger',
                                                    default => 'warning',
                                                };
                                            @endphp
                                            <div class="d-flex flex-column gap-1">
                                                <span class="badge bg-{{ $payClass }} text-uppercase">{{ $payStatus }}</span>
                                                <small class="text-muted">Método: {{ $payMethodLabel }}</small>
                                                @if($payMethod === 'pix' && !empty($payment?->qr_code_text))
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-primary btn-xs js-open-pix-modal"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#pixModal{{ $r->id }}"
                                                    >
                                                        Ver QR Code PIX
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($event->is_paid && $r->payment)
                                            R$ {{ number_format((float) ($r->payment->amount ?? 0), 2, ',', '.') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            @if($r->receipt_sent_at)
                                                <small class="text-success" title="{{ $r->receipt_sent_at->format('d/m/Y H:i') }}">
                                                    <i class="bx bx-check"></i> Enviado {{ $r->receipt_sent_at->format('d/m H:i') }}
                                                </small>
                                            @else
                                                <small class="text-muted">Não enviado</small>
                                            @endif
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('agenda.eventos.registrations.receipt-pdf', [$event, $r]) }}" class="btn btn-outline-secondary btn-xs" title="Baixar comprovante em PDF">
                                                    <i class="bx bxs-file-pdf"></i> PDF
                                                </a>
                                                @if($canEditRegistrations)
                                                    <form method="post" action="{{ route('agenda.eventos.registrations.resend-receipt', [$event, $r]) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-success btn-xs" @disabled(empty($r->phone)) title="{{ empty($r->phone) ? 'Inscrito sem telefone' : 'Reenviar comprovante por WhatsApp' }}">
                                                            <i class="bx bxl-whatsapp"></i> {{ $r->receipt_sent_at ? 'Reenviar' : 'Enviar' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $r->created_at?->format('d/m/Y H:i:s') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-muted">Nenhuma inscrição neste evento.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @foreach($registrations as $r)
                    @if($event->is_paid && strtolower((string) ($r->payment->payment_method ?? '')) === 'pix' && !empty($r->payment->qr_code_text))
                        <div class="modal fade" id="pixModal{{ $r->id }}" tabindex="-1" aria-labelledby="pixModalLabel{{ $r->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="pixModalLabel{{ $r->id }}">PIX - {{ $r->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                    </div>
                                    <div class="modal-body">
                                        @if(!empty($r->payment->qr_code_base64))
                                            <div class="text-center mb-3">
                                                <img
                                                    src="data:image/png;base64,{{ $r->payment->qr_code_base64 }}"
                                                    alt="QR Code PIX"
                                                    style="max-width: 260px; width: 100%;"
                                                >
                                            </div>
                                        @endif
                                        <label class="form-label">Código copia e cola</label>
                                        <textarea
                                            id="pixCode{{ $r->id }}"
                                            class="form-control mb-2"
                                            rows="4"
                                            readonly
                                        >{{ $r->payment->qr_code_text }}</textarea>
                                        <button type="button" class="btn btn-outline-secondary btn-sm js-copy-pix-code" data-target="#pixCode{{ $r->id }}">
                                            Copiar código PIX
                                        </button>
                                    </div>
                                    <div class="modal-footer d-flex justify-content-between">
                                        <small class="text-muted">
                                            Telefone: {{ $r->phone ?: 'não informado' }}
                                        </small>
                                        <form method="post" action="{{ route('agenda.eventos.registrations.pix-whatsapp', [$event, $r]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm" @disabled(empty($r->phone))>
                                                <i class="bx bxl-whatsapp"></i> Enviar PIX no WhatsApp
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach

                <div class="mt-3">
                    {{ $registrations->links() }}
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-copy-pix-code').forEach(function (button) {
        button.addEventListener('click', async function () {
            var targetSelector = button.getAttribute('data-target');
            var field = targetSelector ? document.querySelector(targetSelector) : null;
            if (!field) return;

            try {
                await navigator.clipboard.writeText(field.value || '');
                button.textContent = 'Código copiado!';
                setTimeout(function () {
                    button.textContent = 'Copiar código PIX';
                }, 1500);
            } catch (e) {
                field.select();
                document.execCommand('copy');
                button.textContent = 'Código copiado!';
                setTimeout(function () {
                    button.textContent = 'Copiar código PIX';
                }, 1500);
            }
        });
    });
});
</script>
@endpush
