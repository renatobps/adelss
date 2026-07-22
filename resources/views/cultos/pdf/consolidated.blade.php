<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Consolidado de Cultos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 2px; color: #1e3a8a; }
        h2 { font-size: 13px; margin: 16px 0 8px; color: #1e40af; }
        .meta { color: #6b7280; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 7px; }
        th { background: #1d4ed8; color: #fff; text-align: left; }
        td.num, th.num { text-align: right; }
        .summary th { width: 70%; }
    </style>
</head>
<body>
@php $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.'); @endphp

<h1>{{ $churchName }}</h1>
<div class="meta">
    Relatório Consolidado de Cultos<br>
    {{ $periodLabel }} • Gerado em {{ $generatedAt->format('d/m/Y') }} às {{ $generatedAt->format('H:i') }}
</div>

<h2>Resumo Executivo</h2>
<table class="summary">
    <thead>
        <tr><th>Indicador</th><th class="num">Valor</th></tr>
    </thead>
    <tbody>
        <tr><td>Total de Cultos</td><td class="num">{{ $summary['cultos'] }}</td></tr>
        <tr><td>Total Presentes (Membros + Visitantes)</td><td class="num">{{ $summary['present'] }}</td></tr>
        <tr><td>Membros Presentes</td><td class="num">{{ $summary['members'] }}</td></tr>
        <tr><td>Total de Visitantes</td><td class="num">{{ $summary['visitors'] }}</td></tr>
        <tr><td>Média de Presentes por Culto</td><td class="num">{{ $summary['avg_present'] }}</td></tr>
        <tr><td>Total de Ofertas</td><td class="num">{{ $fmt($summary['ofertas']) }}</td></tr>
        <tr><td>Total de Dízimos</td><td class="num">{{ $fmt($summary['dizimos']) }}</td></tr>
        <tr><td><strong>Arrecadação Total</strong></td><td class="num"><strong>{{ $fmt($summary['total']) }}</strong></td></tr>
    </tbody>
</table>

<h2>Detalhamento por Culto</h2>
<table>
    <thead>
        <tr>
            <th>Data</th>
            <th>Tipo</th>
            <th>Pregador</th>
            <th class="num">Presentes</th>
            <th class="num">Membros</th>
            <th class="num">Visitantes</th>
            <th class="num">Ofertas</th>
            <th class="num">Dízimos</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['date']->format('d/m/y') }}</td>
                <td>{{ $row['type'] }}</td>
                <td>{{ $row['preacher'] }}</td>
                <td class="num">{{ $row['present'] }}</td>
                <td class="num">{{ $row['members'] }}</td>
                <td class="num">{{ $row['visitors'] }}</td>
                <td class="num">{{ $fmt($row['ofertas']) }}</td>
                <td class="num">{{ $fmt($row['dizimos']) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8">Nenhum relatório encontrado para o filtro selecionado.</td>
            </tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
