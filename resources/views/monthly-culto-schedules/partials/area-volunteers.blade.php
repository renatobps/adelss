@if($volunteers->count() > 0 || $guestPreletorName)
    <div class="volunteers-list">
        @if($guestPreletorName)
            <div class="volunteer-item mb-3 pb-3 {{ $volunteers->count() > 0 ? 'border-bottom' : '' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1 flex-wrap gap-2">
                            <strong>{{ $guestPreletorName }}</strong>
                            <span class="badge badge-info badge-sm">
                                <i class="bx bx-user-plus me-1"></i>Convidado
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @foreach($volunteers as $volunteer)
            @php
                $pivotId = $volunteer->pivot->id ?? null;
                $status = $volunteer->pivot->status ?? 'pendente';
            @endphp
            <div class="volunteer-item mb-3 pb-3 border-bottom volunteer-row"
                 data-pivot-id="{{ $pivotId }}"
                 data-service-area-id="{{ $area->id }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <strong class="me-2">{{ $firstName($volunteer->member->name ?? null) }}</strong>
                            @if($status == 'confirmado')
                                <span class="badge badge-success badge-sm">
                                    <i class="bx bx-check-circle me-1"></i>Confirmado
                                </span>
                            @elseif($status == 'cancelado')
                                <span class="badge badge-danger badge-sm">
                                    <i class="bx bx-x-circle me-1"></i>Cancelado
                                </span>
                            @else
                                <span class="badge badge-warning badge-sm">
                                    <i class="bx bx-time me-1"></i>Pendente
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="volunteer-actions">
                        <div class="btn-group btn-group-sm" role="group">
                            @if($status == 'pendente')
                                <button type="button" class="btn btn-sm btn-success confirm-volunteer"
                                        data-pivot-id="{{ $pivotId }}"
                                        title="Confirmar">
                                    <i class="bx bx-check"></i>
                                </button>
                            @endif
                            <button type="button" class="btn btn-sm btn-default substitute-volunteer"
                                    data-pivot-id="{{ $pivotId }}"
                                    data-service-area-id="{{ $area->id }}"
                                    title="Substituir">
                                <i class="bx bx-refresh"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-info notify-volunteer"
                                    data-pivot-id="{{ $pivotId }}"
                                    data-volunteer-name="{{ $volunteer->member->name ?? 'Sem nome' }}"
                                    data-service-area-name="{{ $area->name }}"
                                    title="Notificar WhatsApp">
                                <i class="bx bxl-whatsapp"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger remove-volunteer"
                                    data-pivot-id="{{ $pivotId }}"
                                    title="Remover">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-3">
        <p class="text-muted mb-0">Nenhum voluntário atribuído</p>
        <button type="button"
                class="btn btn-sm btn-primary mt-2 add-volunteer-manual"
                data-service-area-id="{{ $area->id }}">
            <i class="bx bx-plus me-1"></i>Adicionar Manualmente
        </button>
    </div>
@endif
