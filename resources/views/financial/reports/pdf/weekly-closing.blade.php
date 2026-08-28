@php
    use App\Support\PdfText;
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Fechamento semanal — {{ $closing->period_start->format('d/m/Y') }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { margin: 0; font-size: 15px; color: #1e3a8a; }
        .header p { margin: 3px 0 0; font-size: 9px; color: #4b5563; }
        h2 { font-size: 11px; text-transform: uppercase; color: #1e3a8a; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }
        table.dados { width: 100%; border-collapse: collapse; }
        table.dados th, table.dados td { border: 1px solid #d1d5db; padding: 4px 6px; }
        table.dados th { background: #eef2ff; font-size: 8px; text-transform: uppercase; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        .saldo td { background: #1e3a8a; color: #fff; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td>
                    <h1>Fechamento semanal de caixa</h1>
                    <p>ADEL SÃO SEBASTIÃO</p>
                    <p>Período: {{ $closing->period_start->format('d/m/Y') }} a {{ $closing->period_end->format('d/m/Y') }}</p>
                    <p>Snapshot em {{ $closing->generated_at?->format('d/m/Y H:i') }}@if($generatedBy) · {{ PdfText::stripEmoji($generatedBy) }}@endif</p>
                </td>
                <td style="text-align: right; width: 90px;">
                    @if(!empty($logoPath) && is_file($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo" style="max-height: 48px;">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="dados" style="margin-bottom: 14px;">
        <tr><td>Total de entradas (recebidas)</td><td class="num">{{ $fmt($closing->total_receitas) }}</td></tr>
        <tr><td>Total de saídas (pagas)</td><td class="num">{{ $fmt($closing->total_despesas) }}</td></tr>
        <tr class="saldo"><td>Saldo</td><td class="num">{{ $fmt($closing->saldo) }}</td></tr>
    </table>

    <h2>Receitas por categoria</h2>
    <table class="dados">
        @foreach($live['receitas_por_categoria'] as $row)
            <tr>
                <td>{{ PdfText::stripEmoji($row['name']) }}</td>
                <td class="num">{{ $fmt($row['total']) }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Despesas (fornecedor)</h2>
    <table class="dados">
        <thead><tr><th>Fornecedor</th><th>Categoria</th><th>Valor</th></tr></thead>
        <tbody>
            @foreach($live['despesas'] as $tx)
                <tr>
                    <td>{{ PdfText::stripEmoji($tx->source_name) }}</td>
                    <td>{{ PdfText::stripEmoji($tx->category?->name) }}</td>
                    <td class="num">{{ $fmt($tx->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @include('financial.reports.pdf.partials.signatures')
</body>
</html>
