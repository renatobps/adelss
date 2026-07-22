<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Culto</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #1e3a8a; }
        h2 { font-size: 14px; margin: 18px 0 8px; color: #1e40af; border-bottom: 1px solid #bfdbfe; padding-bottom: 4px; }
        .meta { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #1d4ed8; color: #fff; }
        .kpi { width: 100%; margin: 12px 0 18px; }
        .kpi td { border: 1px solid #bfdbfe; background: #eff6ff; text-align: center; padding: 10px; }
        .kpi strong { display: block; font-size: 16px; color: #1e3a8a; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
@php $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.'); @endphp

<h1>{{ $churchName }}</h1>
<div class="meta">
    Relatório de Culto — {{ $report->service_type_label }}<br>
    {{ $report->report_date->locale('pt_BR')->translatedFormat('d/m/Y') }}
    @if($report->start_time) · {{ substr((string) $report->start_time, 0, 5) }} @endif
    · Pregador: {{ $report->preacher_name }}<br>
    Gerado em {{ $generatedAt->format('d/m/Y H:i') }}
</div>

<table class="kpi">
    <tr>
        <td><strong>{{ $report->totalPresent() }}</strong><span class="muted">Presentes</span></td>
        <td><strong>{{ $report->presentMembersCount() }}</strong><span class="muted">Membros</span></td>
        <td><strong>{{ $report->resolvedVisitorsCount() }}</strong><span class="muted">Visitantes</span></td>
        <td><strong>{{ $fmt($financial['total']) }}</strong><span class="muted">Arrecadação</span></td>
    </tr>
</table>

<h2>Financeiro</h2>
<table>
    <tr><th>Indicador</th><th>Valor</th></tr>
    <tr><td>Ofertas</td><td>{{ $fmt($financial['ofertas']) }}</td></tr>
    <tr><td>Dízimos</td><td>{{ $fmt($financial['dizimos']) }}</td></tr>
    <tr><td><strong>Total</strong></td><td><strong>{{ $fmt($financial['total']) }}</strong></td></tr>
</table>

@if($report->message_theme || $report->campaign_series || $report->description || $report->highlights)
<h2>Conteúdo</h2>
<table>
    @if($report->message_theme)
        <tr><th>Tema</th><td>{{ $report->message_theme }}</td></tr>
    @endif
    @if($report->campaign_series)
        <tr><th>Série/Campanha</th><td>{{ $report->campaign_series }}</td></tr>
    @endif
    @if($report->description)
        <tr><th>Descrição</th><td>{{ $report->description }}</td></tr>
    @endif
    @if($report->highlights)
        <tr><th>Destaques</th><td>{{ $report->highlights }}</td></tr>
    @endif
</table>
@endif

@php $presents = $report->attendances->where('present', true); @endphp
@if($presents->isNotEmpty())
<h2>Presentes ({{ $presents->count() }})</h2>
<table>
    <tr><th>#</th><th>Nome</th></tr>
    @foreach($presents as $i => $att)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $att->member?->name ?? '—' }}</td>
        </tr>
    @endforeach
</table>
@endif

@if($report->visitors->isNotEmpty())
<h2>Visitantes ({{ $report->visitors->count() }})</h2>
<table>
    <tr><th>Nome</th><th>Telefone</th></tr>
    @foreach($report->visitors as $visitor)
        <tr>
            <td>{{ $visitor->name }}</td>
            <td>{{ $visitor->phone ?: '—' }}</td>
        </tr>
    @endforeach
</table>
@endif

@if($report->spiritualDecisions->isNotEmpty())
<h2>Manifestações Espirituais</h2>
<table>
    <tr><th>Tipo</th><th>Pessoa</th><th>Obs.</th></tr>
    @foreach($report->spiritualDecisions as $decision)
        <tr>
            <td>{{ \App\Models\ServiceReportSpiritualDecision::TYPES[$decision->type] ?? $decision->type }}</td>
            <td>{{ $decision->person_name ?: '—' }}</td>
            <td>{{ $decision->notes ?: '—' }}</td>
        </tr>
    @endforeach
</table>
@endif
</body>
</html>
