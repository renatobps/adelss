@extends('layouts.porto')

@section('title', 'Inscrições — '.$event->title)

@section('page-title', 'Inscrições')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Visão Geral</a></li>
    <li><span>Agenda</span></li>
    <li><a href="{{ route('agenda.eventos.index') }}">Eventos</a></li>
    <li><span>{{ $event->title }}</span></li>
@endsection

@push('styles')
    @include('agenda.eventos.partials.registrations.styles')
@endpush

@php
    $registrationUrl = function (array $overrides = []) use ($event) {
        $params = array_filter(
            array_merge(request()->query(), $overrides),
            fn ($value) => $value !== null && $value !== ''
        );

        return route('agenda.eventos.registrations', array_merge(['event' => $event->id], $params));
    };

    $fieldNames = $event->registrationFields->pluck('name', 'id');

    $payloads = [];
    foreach ($registrations as $r) {
        $respostas = [];
        foreach ((array) $r->custom_answers as $fieldId => $answer) {
            $respostas[] = [
                'campo' => $fieldNames[$fieldId] ?? ('Campo #'.$fieldId),
                'resposta' => is_array($answer) ? implode(', ', $answer) : (string) $answer,
            ];
        }

        $payloads[$r->id] = base64_encode(json_encode([
            'id' => $r->id,
            'numero' => $r->registration_number ?: '—',
            'nome' => $r->name,
            'email' => $r->email,
            'telefone' => $r->phone,
            'endereco' => $r->address,
            'status' => $r->status,
            'status_label' => $r->status_label,
            'criada_em' => $r->created_at?->format('d/m/Y H:i'),
            'checkin' => $r->checked_in_at?->format('d/m/Y H:i'),
            'checkin_por' => $r->checkedInBy?->name,
            'comprovante' => $r->receipt_sent_at?->format('d/m/Y H:i'),
            'pagamento' => $event->is_paid ? strtoupper((string) ($r->payment->status ?? 'pendente')) : null,
            'valor' => $event->is_paid && $r->payment
                ? 'R$ '.number_format((float) $r->payment->amount, 2, ',', '.')
                : null,
            'pagamento_confirmado' => $r->hasConfirmedPayment(),
            'excluida_em' => $r->deleted_at?->format('d/m/Y H:i'),
            'respostas' => $respostas,
            'urls' => [
                'status' => route('agenda.eventos.registrations.status', [$event, $r->id]),
                'atualizar' => route('agenda.eventos.registrations.update', [$event, $r->id]),
                'excluir' => route('agenda.eventos.registrations.destroy', [$event, $r->id]),
                'pdf' => route('agenda.eventos.registrations.receipt-pdf', [$event, $r->id]),
            ],
        ]));
    }
@endphp

@section('content')
<div class="row er-wrap">
    <div class="col-12">
        <section class="card">
            <header class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="card-title mb-0">Inscrições</h2>
                    <p class="text-muted small mb-0 mt-1">{{ $event->title }}</p>
                    @if($event->registration_enabled)
                        <span class="badge bg-success mt-1">Inscrições abertas</span>
                    @else
                        <span class="badge bg-danger mt-1">Inscrições encerradas</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @include('agenda.eventos.partials.toggle-registrations')
                    <a href="{{ route('agenda.eventos.index') }}" class="btn btn-default btn-sm"><i class="bx bx-arrow-back"></i> Voltar</a>
                    @if($event->public_slug)
                        <a href="{{ route('events.public.show', $event->public_slug) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="bx bx-link-external"></i> Página do evento
                        </a>
                    @endif
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-download"></i> Exportar
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('agenda.eventos.registrations.export', array_merge(['event' => $event->id], request()->query())) }}">
                                    <i class="bx bx-spreadsheet me-2"></i> Excel (CSV)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('agenda.eventos.registrations.export-pdf', array_merge(['event' => $event->id], request()->query())) }}">
                                    <i class="bx bxs-file-pdf me-2"></i> PDF (credenciamento)
                                </a>
                            </li>
                        </ul>
                    </div>
                    @if($canEditRegistrations)
                        <button type="button" class="btn btn-success btn-sm" id="erWhatsappOpen"
                                @disabled($whatsappContacts->isEmpty())
                                title="{{ $whatsappContacts->isEmpty() ? 'Nenhum inscrito com telefone cadastrado' : 'Enviar WhatsApp aos inscritos' }}">
                            <i class="bx bxl-whatsapp"></i> WhatsApp
                        </button>
                        <a href="{{ route('agenda.eventos.check-in', $event) }}" class="btn btn-success btn-sm">
                            <i class="bx bx-qr-scan"></i> Check-in
                        </a>
                    @endif
                    <a href="{{ route('agenda.eventos.edit', $event) }}" class="btn btn-primary btn-sm"><i class="bx bx-edit"></i> Editar evento</a>
                </div>
            </header>
            <div class="card-body">
                @if(!$event->registration_enabled)
                    <div class="alert alert-warning">
                        As inscrições estão encerradas. Novos cadastros pela página pública estão bloqueados.
                        Use <strong>Reabrir inscrições</strong> se quiser voltar a aceitar.
                    </div>
                @endif
                @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $class)
                    @if(session($key))
                        <div class="alert alert-{{ $class }}">{{ session($key) }}</div>
                    @endif
                @endforeach

                @if(session('envio_erros') && count(session('envio_erros')))
                    <div class="alert alert-warning">
                        <div class="fw-semibold mb-1">Detalhes das falhas:</div>
                        <ul class="mb-0 ps-3">
                            @foreach(session('envio_erros') as $motivo)
                                <li>{{ $motivo }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!$canEditRegistrations)
                    <div class="alert alert-info">
                        Você pode visualizar as inscrições. Para alterar status ou dados, é necessária permissão de edição.
                    </div>
                @endif

                @if($stats['duplicadas'] > 0 && $filters['situacao'] !== 'duplicadas')
                    <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <strong>{{ $stats['duplicadas'] }} inscrições possivelmente duplicadas detectadas</strong>
                            <div class="small">
                                {{ $duplicateGroupCount }} contato(s) com mais de uma inscrição. Podem ser legítimas —
                                uma mãe inscrevendo dois filhos com o próprio contato, por exemplo. Revise antes de remover.
                            </div>
                        </div>
                        <a href="{{ $registrationUrl(['situacao' => 'duplicadas', 'page' => null]) }}" class="btn btn-warning btn-sm">
                            Revisar duplicadas
                        </a>
                    </div>
                @endif

                @include('agenda.eventos.partials.registrations.kpis')
                @include('agenda.eventos.partials.registrations.toolbar')

                <div id="erListing">
                    @include('agenda.eventos.partials.registrations.table')
                    @include('agenda.eventos.partials.registrations.cards')
                </div>

                <div class="mt-3 er-pagination">
                    {{ $registrations->links() }}
                </div>
            </div>
        </section>
    </div>
</div>

@if($event->is_paid)
    @foreach($registrations as $r)
        @if(strtolower((string) ($r->payment->payment_method ?? '')) === 'pix' && !empty($r->payment->qr_code_text))
            <div class="modal fade" id="pixModal{{ $r->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">PIX — {{ $r->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            @if(!empty($r->payment->qr_code_base64))
                                <div class="text-center mb-3">
                                    <img src="data:image/png;base64,{{ $r->payment->qr_code_base64 }}" alt="QR Code PIX"
                                         style="max-width: 260px; width: 100%;">
                                </div>
                            @endif
                            <label class="form-label" for="pixCode{{ $r->id }}">Código copia e cola</label>
                            <textarea id="pixCode{{ $r->id }}" class="form-control mb-2" rows="4" readonly>{{ $r->payment->qr_code_text }}</textarea>
                            <button type="button" class="btn btn-outline-secondary btn-sm js-copy-pix-code" data-target="#pixCode{{ $r->id }}">
                                Copiar código PIX
                            </button>
                        </div>
                        <div class="modal-footer d-flex justify-content-between">
                            <small class="text-muted">Telefone: {{ $r->phone ?: 'não informado' }}</small>
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
@endif

@include('agenda.eventos.partials.registrations.modals')

@if($canEditRegistrations)
    @include('agenda.eventos.partials.registrations.bulk-bar')
    <a href="{{ route('agenda.eventos.check-in', $event) }}" class="er-fab" title="Abrir check-in">
        <i class="bx bx-qr-scan"></i>
    </a>
@endif
@endsection

@push('scripts')
    @include('agenda.eventos.partials.registrations.scripts')
@endpush
