@php
    use App\Models\CampaignSponsor;
@endphp

<table class="table table-sm cs-table mb-0">
    <thead>
        <tr>
            <th>Patrocinador</th>
            <th class="d-none d-md-table-cell">Telefone</th>
            <th class="d-none d-md-table-cell" style="width: 150px;">Progresso</th>
            <th class="text-end" style="width: 110px;">Pago</th>
            <th class="text-end d-none d-md-table-cell" style="width: 110px;">Pendente</th>
            <th style="width: 116px;">Situação</th>
            <th style="width: 44px;"></th>
        </tr>
    </thead>
    <tbody>
        @foreach($sponsors as $sponsor)
            @php
                $s = $sponsor->summary();
                $situacao = $sponsor->situacaoDetalhada();
                $percent = $s['total'] > 0 ? round(($s['paid'] / $s['total']) * 100) : 0;
                $barColor = match ($situacao) {
                    'em_atraso' => 'bg-danger',
                    'quitado' => 'bg-success',
                    'em_dia' => 'bg-primary',
                    default => 'bg-secondary',
                };
            @endphp
            <tr class="cs-item--{{ $situacao }} js-open-sponsor"
                data-url="{{ route('financial.campaigns.sponsors.installments', $sponsor) }}"
                data-name="{{ $sponsor->name }}">
                <td>
                    <span class="fw-semibold">{{ $sponsor->name }}</span>
                    <span class="d-md-none d-block text-muted small">{{ $sponsor->phone ?: 'sem telefone' }}</span>
                </td>
                <td class="d-none d-md-table-cell text-muted small">{{ $sponsor->phone ?: '—' }}</td>
                <td class="d-none d-md-table-cell">
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height: 6px;" role="progressbar"
                             aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar {{ $barColor }}" style="width: {{ $percent }}%"></div>
                        </div>
                        <small class="text-muted">{{ $s['paid'] }}/{{ $s['total'] }}</small>
                    </div>
                </td>
                <td class="text-end text-success fw-semibold">R$ {{ number_format($s['paid_amount'], 2, ',', '.') }}</td>
                <td class="text-end d-none d-md-table-cell">R$ {{ number_format($s['pending_amount'], 2, ',', '.') }}</td>
                <td><span class="cs-badge cs-badge--{{ $situacao }}">{{ CampaignSponsor::SITUACOES[$situacao] }}</span></td>
                <td class="text-end">
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-link text-secondary p-0 px-1" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Ações">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button type="button" class="dropdown-item js-open-sponsor"
                                        data-url="{{ route('financial.campaigns.sponsors.installments', $sponsor) }}"
                                        data-name="{{ $sponsor->name }}">
                                    <i class="bx bx-list-ul me-1"></i>Ver parcelas
                                </button>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('financial.campaigns.sponsors.carne', $sponsor) }}">
                                    <i class="bx bx-printer me-1"></i>Gerar carnê (PDF)
                                </a>
                            </li>
                            @if($canEdit)
                                <li>
                                    <button type="button" class="dropdown-item js-edit-sponsor"
                                            data-action="{{ route('financial.campaigns.sponsors.update', $sponsor) }}"
                                            data-name="{{ $sponsor->name }}" data-phone="{{ $sponsor->phone }}" data-notes="{{ $sponsor->notes }}">
                                        <i class="bx bx-edit me-1"></i>Editar
                                    </button>
                                </li>
                            @endif
                            @if($canDelete)
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('financial.campaigns.sponsors.destroy', $sponsor) }}"
                                          onsubmit="return confirm('Remover o patrocinador {{ $sponsor->name }} e suas parcelas pendentes?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="bx bx-trash me-1"></i>Remover</button>
                                    </form>
                                </li>
                            @endif
                        </ul>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
