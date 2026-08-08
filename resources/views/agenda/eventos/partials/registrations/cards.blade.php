<div class="er-cards">
    @forelse($registrations as $r)
        <article class="er-card {{ $r->checked_in_at ? 'er-card--presente' : '' }} {{ $r->trashed() ? 'er-card--excluida' : '' }}"
                 data-registration="{{ $payloads[$r->id] }}">
            <div class="er-card-head">
                @if($canEditRegistrations)
                    <input type="checkbox" class="form-check-input mt-1 js-er-check" value="{{ $r->id }}"
                           aria-label="Selecionar {{ $r->name }}">
                @endif
                <span class="er-avatar" style="background: {{ $r->avatar_color }};">{{ $r->initials }}</span>
                <div class="er-card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div style="min-width: 0;">
                            <div class="er-name text-truncate">{{ $r->name }}</div>
                            <div class="er-muted">
                                {{ $r->registration_number ?: '—' }}
                                @if(isset($duplicateIds[$r->id]))
                                    <span class="er-badge er-badge--dup ms-1">Dup.</span>
                                @endif
                            </div>
                        </div>
                        @include('agenda.eventos.partials.registrations.actions-menu', ['suffix' => 'card'])
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                        @include('agenda.eventos.partials.registrations.status-badge', ['suffix' => 'card'])
                        @if($r->checked_in_at)
                            <span class="er-badge er-badge--presente">Presente {{ $r->checked_in_at->format('H:i') }}</span>
                        @endif
                        @if($event->is_paid && $r->payment)
                            <span class="er-badge er-badge--{{ strtolower((string) $r->payment->status) === 'approved' ? 'confirmado' : 'pendente' }} text-uppercase">
                                {{ $r->payment->status }}
                            </span>
                        @endif
                    </div>

                    <div class="er-card-contact mt-1">
                        <span class="text-truncate">{{ $r->email ?: '—' }}</span>
                    </div>
                    <div class="er-card-contact">
                        <span>{{ $r->phone ?: '—' }}</span>
                        @if($r->whatsapp_url)
                            <a href="{{ $r->whatsapp_url }}" target="_blank" rel="noopener" class="er-whats"
                               title="Conversar no WhatsApp"><i class="bx bxl-whatsapp"></i></a>
                        @endif
                    </div>

                    <div class="er-muted mt-1 d-flex align-items-center gap-2">
                        <span>Inscrito {{ $r->created_at?->format('d/m · H:i') }}</span>
                        @if($r->receipt_sent_at)
                            <i class="bx bx-check-circle er-receipt-ok"
                               title="Comprovante enviado em {{ $r->receipt_sent_at->format('d/m/Y H:i') }}"></i>
                        @else
                            <i class="bx bx-circle er-receipt-off" title="Comprovante não enviado"></i>
                        @endif
                    </div>
                </div>
            </div>
        </article>
    @empty
        <p class="text-muted text-center py-4 mb-0">
            {{ $filters['q'] !== '' || $filters['situacao'] !== 'todos'
                ? 'Nenhuma inscrição encontrada com esses filtros.'
                : 'Nenhuma inscrição neste evento.' }}
        </p>
    @endforelse
</div>
