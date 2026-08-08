@php
    $kpis = [
        [
            'label' => 'Inscritos',
            'value' => $stats['em_vaga'],
            'hint' => $stats['cancelados'] > 0 ? $stats['cancelados'].' cancelada(s)' : null,
            'situacao' => 'todos',
            'color' => '#0088CC',
        ],
        [
            'label' => 'Confirmados',
            'value' => $stats['confirmados'],
            'hint' => null,
            'situacao' => 'confirmados',
            'color' => '#1FA855',
        ],
        [
            'label' => 'Pendentes',
            'value' => $stats['pendentes'],
            'hint' => null,
            'situacao' => 'pendentes',
            'color' => '#F5A623',
        ],
        [
            'label' => 'Presentes',
            'value' => $stats['presentes'],
            'hint' => $stats['presentes_percentual'].'% dos inscritos',
            'situacao' => 'presentes',
            'color' => '#1FA855',
        ],
    ];

    if ($event->is_paid) {
        $kpis[] = [
            'label' => 'Arrecadado',
            'value' => 'R$ '.number_format((float) $stats['arrecadado'], 2, ',', '.'),
            'hint' => null,
            'situacao' => null,
            'color' => '#1FA855',
        ];
    }
@endphp

<div class="er-kpis">
    @foreach($kpis as $kpi)
        @php($ativo = $kpi['situacao'] !== null && $filters['situacao'] === $kpi['situacao'])
        @if($kpi['situacao'])
            <a href="{{ $registrationUrl(['situacao' => $kpi['situacao'] === 'todos' ? null : $kpi['situacao'], 'page' => null]) }}"
               class="er-kpi {{ $ativo ? 'is-active' : '' }}" style="--er-kpi: {{ $kpi['color'] }};">
                <span class="er-kpi-label">{{ $kpi['label'] }}</span>
                <span class="er-kpi-value">{{ $kpi['value'] }}</span>
                <span class="er-kpi-hint">{!! $kpi['hint'] ? e($kpi['hint']) : '&nbsp;' !!}</span>
            </a>
        @else
            <div class="er-kpi" style="--er-kpi: {{ $kpi['color'] }};">
                <span class="er-kpi-label">{{ $kpi['label'] }}</span>
                <span class="er-kpi-value">{{ $kpi['value'] }}</span>
                <span class="er-kpi-hint">{!! $kpi['hint'] ? e($kpi['hint']) : '&nbsp;' !!}</span>
            </div>
        @endif
    @endforeach
</div>

@if($stats['vagas'])
    <div class="er-spots">
        <div class="d-flex justify-content-between align-items-baseline mb-1">
            <strong class="small">{{ $stats['em_vaga'] }} de {{ $stats['vagas'] }} vagas preenchidas</strong>
            <span class="er-muted">{{ $stats['vagas_percentual'] }}%</span>
        </div>
        <div class="progress" role="progressbar" aria-valuenow="{{ $stats['vagas_percentual'] }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar {{ $stats['vagas_percentual'] >= 100 ? 'bg-danger' : 'bg-primary' }}"
                 style="width: {{ $stats['vagas_percentual'] }}%"></div>
        </div>
    </div>
@endif
