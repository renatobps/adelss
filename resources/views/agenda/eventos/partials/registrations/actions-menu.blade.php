@php
    $menuId = 'er-actions-'.$r->id.'-'.($suffix ?? 'main');
@endphp
<div class="dropdown">
    <button type="button" class="er-actions-btn" id="{{ $menuId }}" data-bs-toggle="dropdown"
            data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Ações">
        <i class="bx bx-dots-vertical-rounded"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="{{ $menuId }}">
        <li>
            <button type="button" class="dropdown-item js-er-action" data-er-action="detalhes">
                <i class="bx bx-show me-2"></i> Ver detalhes
            </button>
        </li>

        @if($r->trashed())
            @if($canDeleteRegistrations)
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post" action="{{ route('agenda.eventos.registrations.restore', [$event, $r->id]) }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-success">
                            <i class="bx bx-undo me-2"></i> Restaurar inscrição
                        </button>
                    </form>
                </li>
            @endif
        @else
            <li>
                <a class="dropdown-item" href="{{ route('agenda.eventos.registrations.receipt-pdf', [$event, $r]) }}">
                    <i class="bx bxs-file-pdf me-2"></i> Baixar PDF
                </a>
            </li>

            @if($canEditRegistrations)
                <li>
                    <button type="button" class="dropdown-item js-er-action" data-er-action="whatsapp"
                            @disabled(empty($r->phone))
                            title="{{ empty($r->phone) ? 'Inscrito sem telefone cadastrado' : '' }}">
                        <i class="bx bxl-whatsapp me-2"></i> Enviar WhatsApp
                    </button>
                </li>
                <li>
                    <form method="post" action="{{ route('agenda.eventos.registrations.resend-receipt', [$event, $r]) }}">
                        @csrf
                        <button type="submit" class="dropdown-item" @disabled(empty($r->phone))
                                title="{{ empty($r->phone) ? 'Inscrito sem telefone cadastrado' : '' }}">
                            <i class="bx bxl-whatsapp me-2"></i> {{ $r->receipt_sent_at ? 'Reenviar' : 'Enviar' }} comprovante
                        </button>
                    </form>
                </li>
                <li>
                    <button type="button" class="dropdown-item js-er-action" data-er-action="editar">
                        <i class="bx bx-edit me-2"></i> Editar dados
                    </button>
                </li>
                @if($r->status !== \App\Models\EventRegistration::STATUS_CANCELADO)
                    <li>
                        <button type="button" class="dropdown-item js-er-action" data-er-action="status" data-er-status="cancelado">
                            <i class="bx bx-x-circle me-2"></i> Cancelar inscrição
                        </button>
                    </li>
                @endif
            @endif

            @if($canDeleteRegistrations)
                <li><hr class="dropdown-divider"></li>
                <li>
                    <button type="button" class="dropdown-item text-danger js-er-action" data-er-action="excluir">
                        <i class="bx bx-trash me-2"></i> Excluir inscrição
                    </button>
                </li>
            @endif
        @endif
    </ul>
</div>
