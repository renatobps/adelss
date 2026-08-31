@php
    use App\Support\PdfText;
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Movimento do dia — {{ $culto->display_name }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { margin: 0; font-size: 15px; color: #1e3a8a; }
        .header p { margin: 3px 0 0; font-size: 9px; color: #4b5563; }
        h2 { font-size: 11px; text-transform: uppercase; padding-bottom: 3px; margin: 16px 0 8px; border-bottom: 1px solid #cbd5e1; }
        h2.receita { color: #1d4ed8; border-bottom-color: #93c5fd; }
        h2.despesa { color: #b91c1c; border-bottom-color: #fca5a5; }
        table.dados { width: 100%; border-collapse: collapse; }
        table.dados th, table.dados td { border: 1px solid #d1d5db; padding: 4px 6px; }
        table.dados th { font-size: 8px; text-transform: uppercase; text-align: left; }
        table.dados.receita th { background: #dbeafe; color: #1e3a8a; }
        table.dados.despesa th { background: #fee2e2; color: #991b1b; }
        .num { text-align: right; white-space: nowrap; }
        .valor-receita { color: #1d4ed8; font-weight: bold; }
        .valor-despesa { color: #b91c1c; font-weight: bold; }
        .total-row.receita td { background: #eff6ff; color: #1d4ed8; font-weight: bold; }
        .total-row.despesa td { background: #fef2f2; color: #b91c1c; font-weight: bold; }
        .resumo { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .resumo td { padding: 6px 8px; border: 1px solid #d1d5db; }
        .resumo .row-receita td { color: #1d4ed8; font-weight: bold; background: #eff6ff; }
        .resumo .row-despesa td { color: #b91c1c; font-weight: bold; background: #fef2f2; }
        .saldo td { background: #1e3a8a; color: #fff; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td>
                    <h1>Movimento do dia do culto</h1>
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

    <table class="resumo">
        <tr class="row-receita"><td>(+) Total de entradas</td><td class="num">{{ $fmt($totalEntradas) }}</td></tr>
        <tr class="row-despesa"><td>(−) Total de saídas</td><td class="num">{{ $fmt($totalSaidas) }}</td></tr>
        <tr class="saldo"><td>Saldo do dia</td><td class="num">{{ $fmt($saldoDia) }}</td></tr>
    </table>

    <h2 class="receita">Entradas (receitas)</h2>
    <table class="dados receita">
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Recebido de</th>
                <th class="num">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entradas as $tx)
                <tr>
                    <td>{{ PdfText::stripEmoji($tx->description) }}</td>
                    <td>{{ PdfText::stripEmoji($tx->source_name) }}</td>
                    <td class="num valor-receita">{{ $fmt($tx->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Nenhuma entrada paga neste dia.</td></tr>
            @endforelse
            <tr class="total-row receita">
                <td colspan="2">Total de entradas</td>
                <td class="num">{{ $fmt($totalEntradas) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 class="despesa">Saídas (despesas)</h2>
    <table class="dados despesa">
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Pago à</th>
                <th class="num">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($saidas as $tx)
                <tr>
                    <td>{{ PdfText::stripEmoji($tx->description) }}</td>
                    <td>{{ PdfText::stripEmoji($tx->source_name) }}</td>
                    <td class="num valor-despesa">{{ $fmt($tx->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Nenhuma saída paga neste dia.</td></tr>
            @endforelse
            <tr class="total-row despesa">
                <td colspan="2">Total de saídas</td>
                <td class="num">{{ $fmt($totalSaidas) }}</td>
            </tr>
        </tbody>
    </table>

    @include('financial.reports.pdf.partials.signatures')
</body>
</html>
