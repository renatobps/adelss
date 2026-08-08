<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório do PGI</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #2e353e; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #0088cc; }
        h2 { font-size: 13px; margin: 16px 0 6px; color: #2e353e; border-bottom: 1px solid #eef0f2; padding-bottom: 4px; }
        .meta { color: #6c757d; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #dfe3e8; padding: 5px 7px; text-align: left; }
        th { background: #0088cc; color: #fff; font-weight: 600; }
        .kpi td { border: 1px solid #cfe9f7; background: #f2fafe; text-align: center; padding: 9px; }
        .kpi strong { display: block; font-size: 15px; color: #0088cc; }
        .muted { color: #6c757d; }
        .center { text-align: center; }
        .warn { background: #fff8e6; }
    </style>
</head>
<body>
@php
    $money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
    $registeredMeetings = $meetings->filter(fn ($meeting) => $meeting->hasAttendanceRegistered());
@endphp

<h1>{{ $churchName }}</h1>
<div class="meta">
    Relatório do PGI <strong>{{ $pgi->name }}</strong><br>
    {{ $pgi->day_of_week ? ucfirst($pgi->day_of_week) : 'Dia não definido' }}
    @if($pgi->time_schedule) · {{ $pgi->time_schedule }} @endif
    @if($pgi->profile) · Perfil {{ $pgi->profile }} @endif
    @if($pgi->fullAddress()) <br>{{ $pgi->fullAddress() }} @endif
    <br>Gerado em {{ $generatedAt->format('d/m/Y H:i') }}
</div>

<table class="kpi">
    <tr>
        <td><strong>{{ $kpis['members'] }}</strong><span class="muted">Membros</span></td>
        <td>
            <strong>{{ $kpis['average_attendance'] !== null ? number_format($kpis['average_attendance'], 1, ',', '.') : '—' }}</strong>
            <span class="muted">Média de presença</span>
        </td>
        <td><strong>{{ $meetings->count() }}</strong><span class="muted">Reuniões registradas</span></td>
        <td><strong>{{ $kpis['visitors_this_month'] }}</strong><span class="muted">Visitantes no mês</span></td>
    </tr>
</table>

<h2>Liderança</h2>
<table>
    <tr><th style="width: 35%;">Função</th><th>Nome</th></tr>
    <tr><td>Líder 1</td><td>{{ $pgi->leader1->name ?? 'Não definido' }}</td></tr>
    <tr><td>Líder 2</td><td>{{ $pgi->leader2->name ?? 'Não definido' }}</td></tr>
    <tr><td>Líder em treinamento 1</td><td>{{ $pgi->leaderTraining1->name ?? 'Não definido' }}</td></tr>
    <tr><td>Líder em treinamento 2</td><td>{{ $pgi->leaderTraining2->name ?? 'Não definido' }}</td></tr>
</table>

<h2>Membros ({{ $pgi->members->count() }})</h2>
@if($pgi->members->isEmpty())
    <p class="muted">Nenhum membro vinculado a este PGI.</p>
@else
<table>
    <tr>
        <th style="width: 6%;">#</th>
        <th>Nome</th>
        <th style="width: 22%;">Telefone</th>
        <th style="width: 22%;">Frequência</th>
    </tr>
    @foreach($pgi->members->sortBy('name')->values() as $index => $member)
        @php $freq = $frequency[$member->id] ?? null; @endphp
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $member->name }}</td>
            <td>{{ $member->phone ?: '—' }}</td>
            <td>{{ $freq ? $freq['present'] . '/' . $freq['total'] . ' reuniões' : '—' }}</td>
        </tr>
    @endforeach
</table>
@endif

@if($absentees->isNotEmpty())
<h2>Precisam de atenção pastoral</h2>
<p class="muted">Ausentes nas últimas {{ $absenceThreshold }} reuniões com chamada registrada.</p>
<table>
    <tr><th>Nome</th><th style="width: 30%;">Telefone</th></tr>
    @foreach($absentees as $member)
        <tr class="warn">
            <td>{{ $member->name }}</td>
            <td>{{ $member->phone ?: '—' }}</td>
        </tr>
    @endforeach
</table>
@endif

<h2>Histórico de reuniões</h2>
@if($meetings->isEmpty())
    <p class="muted">Nenhuma reunião registrada.</p>
@else
<table>
    <tr>
        <th style="width: 16%;">Data</th>
        <th>Tema</th>
        <th class="center" style="width: 14%;">Presentes</th>
        <th class="center" style="width: 14%;">Visitantes</th>
        <th class="center" style="width: 16%;">Oferta</th>
    </tr>
    @foreach($meetings as $meeting)
        <tr>
            <td>{{ $meeting->meeting_date->format('d/m/Y') }}</td>
            <td>
                {{ $meeting->subject ?: '—' }}
                @unless($meeting->hasAttendanceRegistered())
                    <span class="muted">(chamada pendente)</span>
                @endunless
            </td>
            <td class="center">{{ $meeting->hasAttendanceRegistered() ? $meeting->participants_count . '/' . $pgi->members->count() : '—' }}</td>
            <td class="center">{{ $meeting->hasAttendanceRegistered() ? $meeting->visitors_count : '—' }}</td>
            <td class="center">{{ $money($meeting->total_value) }}</td>
        </tr>
    @endforeach
</table>

@if($registeredMeetings->isNotEmpty())
    <p class="muted" style="margin-top: 8px;">
        Média de presença nas {{ $registeredMeetings->count() }} reuniões com chamada registrada:
        <strong>{{ number_format($registeredMeetings->avg('participants_count'), 1, ',', '.') }}</strong> participantes ·
        Total arrecadado: <strong>{{ $money($meetings->sum('total_value')) }}</strong>
    </p>
@endif
@endif

@if($pgi->notes)
<h2>Anotações</h2>
<p>{{ $pgi->notes }}</p>
@endif

</body>
</html>
