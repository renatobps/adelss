@php
    use App\Support\PdfText;
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dízimos e ofertas — {{ $culto->display_name }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { margin: 0; font-size: 15px; color: #1e3a8a; }
        .header p { margin: 3px 0 0; font-size: 9px; color: #4b5563; }
        table.dados { width: 100%; border-collapse: collapse; }
        table.dados th, table.dados td { border: 1px solid #d1d5db; padding: 4px 6px; }
        table.dados th { background: #eef2ff; font-size: 8px; text-transform: uppercase; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        .resumo td { padding: 6px 8px; border: 1px solid #d1d5db; }
        .total-row td { background: #f1f5f9; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td>
                    <h1>Dízimos e ofertas por culto</h1>
                    <p>ADEL SÃO SEBASTIÃO</p>
                    <p>{{ PdfText::stripEmoji($culto->display_name) }}</p>
                    <p>Gerado em {{ $generatedAt->format('d/m/Y H:i') }}@if($generatedBy) · {{ PdfText::stripEmoji($generatedBy) }}@endif</p>
                </td>
                <td style="text-align: right; width: 90px;">
                    @if(!empty($logoPath) && is_file($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo" style="max-height: 48px;">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="resumo" width="100%" style="border-collapse: collapse; margin-bottom: 14px;">
        <tr><td>Total de dízimos</td><td class="num">{{ $fmt($totalDizimos) }}</td></tr>
        <tr><td>Total de ofertas</td><td class="num">{{ $fmt($totalOfertas) }}</td></tr>
        <tr class="total-row"><td>Total geral</td><td class="num">{{ $fmt($totalGeral) }}</td></tr>
    </table>

    <table class="dados">
        <thead>
            <tr>
                <th>Recebido de</th>
                <th>Categoria</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lancamentos as $tx)
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
