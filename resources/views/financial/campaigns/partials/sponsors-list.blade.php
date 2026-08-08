@php
    use App\Models\CampaignSponsor;
@endphp

<div class="accordion accordion-flush" id="sponsorsAccordion">
    @foreach($sponsors as $sponsor)
        @php
            $s = $sponsor->summary();
            $situacao = $sponsor->situacaoDetalhada();
            $percent = $s['total'] > 0 ? round(($s['paid'] / $s['total']) * 100) : 0;
            $parts = preg_split('/\s+/', trim($sponsor->name)) ?: [];
            $initials = mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
            $barColor = match ($situacao) {
                'em_atraso' => 'bg-danger',
                'quitado' => 'bg-success',
                'em_dia' => 'bg-primary',
                default => 'bg-secondary',
            };
        @endphp
        <div class="accordion-item cs-item cs-item--{{ $situacao }}">
            <div class="cs-head">
                <button class="cs-toggle collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#sponsor-{{ $sponsor->id }}" aria-expanded="false"
                        aria-controls="sponsor-{{ $sponsor->id }}">
                    <span class="cs-avatar">{{ $initials }}</span>
                    <span class="cs-identity">
                        <span class="cs-name">{{ $sponsor->name }}</span>
                        <span class="cs-phone">{{ $sponsor->phone ?: 'sem telefone' }}</span>
                    </span>
                    <span class="cs-progress">
                        <span class="progress" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                            <span class="progress-bar {{ $barColor }}" style="width: {{ $percent }}%"></span>
                        </span>
                    </span>
                    <span class="cs-count">{{ $s['paid'] }}/{{ $s['total'] }}</span>
                    <span class="cs-amount">R$ {{ number_format($s['paid_amount'], 2, ',', '.') }}</span>
                    <span class="cs-status">
                        <span class="cs-badge cs-badge--{{ $situacao }}">{{ CampaignSponsor::SITUACOES[$situacao] }}</span>
                        @include('financial.campaigns.partials.sponsor-reminder-badge')
                    </span>
                </button>
                <div class="cs-menu dropdown">
                    <button type="button" class="btn btn-sm btn-link text-secondary" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Ações">
                        <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
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
                        @include('financial.campaigns.partials.sponsor-reminder-actions')
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
            </div>
            <div id="sponsor-{{ $sponsor->id }}" class="accordion-collapse collapse" data-bs-parent="#sponsorsAccordion">
                <div class="cs-body js-installments" data-url="{{ route('financial.campaigns.sponsors.installments', $sponsor) }}">
                    <div class="text-center text-muted py-3">
                        <span class="spinner-border spinner-border-sm me-2"></span>Carregando parcelas...
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
