@php
    use App\Support\PdfText;
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Fechamento de caixa — {{ $start->translatedFormat('F Y') }}</title>
    <style>
        @page { margin: 18mm 14mm 18mm 14mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
        }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { margin: 0; font-size: 15px; color: #1e3a8a; }
        .header p { margin: 3px 0 0; font-size: 9px; color: #4b5563; }
        h2 {
            font-size: 11px;
            text-transform: uppercase;
            color: #1e3a8a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin: 16px 0 8px;
        }
        table.dados { width: 100%; border-collapse: collapse; }
        table.dados th, table.dados td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            vertical-align: top;
        }
        table.dados th {
            background: #eef2ff;
            font-size: 8px;
            text-transform: uppercase;
            text-align: left;
        }
        .num { text-align: right; white-space: nowrap; }
        .total-row td { background: #f1f5f9; font-weight: bold; }
        .resumo { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .resumo td { padding: 6px 8px; border: 1px solid #d1d5db; }
        .resumo .lbl { background: #f8fafc; width: 55%; }
        .saldo td { background: #1e3a8a; color: #fff; font-size: 12px; font-weight: bold; }
        .nota { font-size: 8px; color: #6b7280; margin-top: 10px; }
        .comp { page-break-before: always; }
        .comp img { max-width: 100%; max-height: 210mm; }
        .comp-meta { font-size: 9px; margin-bottom: 8px; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td>
                    <h1>Fechamento de caixa — Extrato mensal</h1>
                    <p>ADEL SÃO SEBASTIÃO</p>
                    <p>
                        Período: {{ $start->format('d/m/Y') }} a {{ $end->format('d/m/Y') }}
                        @if($account)
                            • Conta: {{ PdfText::stripEmoji($account->name) }}
                        @endif
                    </p>
                    <p>Gerado em {{ $generatedAt->format('d/m/Y H:i') }}</p>
                </td>
                <td style="text-align: right; width: 90px;">
                    @if(!empty($logoPath) && is_file($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo" style="max-height: 48px;">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <p class="nota">Este relatório considera apenas lançamentos <strong>recebidos e pagos</strong> no período (fechamento de caixa). Pendências a receber/a pagar não entram no saldo.</p>

    <h2>Entradas (receitas)</h2>
    <table class="dados">
        <thead>
            <tr>
                <th style="width: 18mm;">Data</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Origem</th>
                <th class="num" style="width: 28mm;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entradas as $tx)
                <tr>
                    <td>{{ $tx->transaction_date?->format('d/m/Y') }}</td>
                    <td>{{ PdfText::stripEmoji($tx->description) }}</td>
                    <td>{{ PdfText::stripEmoji($tx->category->name ?? '—') }}</td>
                    <td>{{ PdfText::stripEmoji($tx->source_name) }}</td>
                    <td class="num">{{ $fmt($tx->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhuma entrada paga neste período.</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="4">Total de entradas</td>
                <td class="num">{{ $fmt($totalEntradas) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Saídas (despesas)</h2>
    <table class="dados">
        <thead>
            <tr>
                <th style="width: 18mm;">Data</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Fornecedor / favorecido</th>
                <th class="num" style="width: 22mm;">Anexos</th>
                <th class="num" style="width: 28mm;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($saidas as $tx)
                <tr>
                    <td>{{ $tx->transaction_date?->format('d/m/Y') }}</td>
                    <td>{{ PdfText::stripEmoji($tx->description) }}</td>
                    <td>{{ PdfText::stripEmoji($tx->category->name ?? '—') }}</td>
                    <td>{{ PdfText::stripEmoji($tx->source_name) }}</td>
                    <td class="num">{{ $tx->attachments->count() }}</td>
                    <td class="num">{{ $fmt($tx->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhuma saída paga neste período.</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="5">Total de saídas</td>
                <td class="num">{{ $fmt($totalSaidas) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Totais e saldo</h2>
    <table class="resumo">
        <tr>
            <td class="lbl">Saldo anterior (até {{ $start->copy()->subDay()->format('d/m/Y') }})</td>
            <td class="num">{{ $fmt($saldoAnterior) }}</td>
        </tr>
        <tr>
            <td class="lbl">(+) Total de entradas</td>
            <td class="num">{{ $fmt($totalEntradas) }}</td>
        </tr>
        <tr>
            <td class="lbl">(−) Total de saídas</td>
            <td class="num">{{ $fmt($totalSaidas) }}</td>
        </tr>
        <tr class="saldo">
            <td>Saldo final em {{ $end->format('d/m/Y') }}</td>
            <td class="num">{{ $fmt($saldoFinal) }}</td>
        </tr>
    </table>

    @include('financial.reports.pdf.partials.signatures')

    @if(count($comprovantes) > 0)
        @foreach($comprovantes as $item)
            <div class="comp">
                <h2>Comprovante de despesa</h2>
                <div class="comp-meta">
                    <strong>{{ $item['transaction']->transaction_date?->format('d/m/Y') }}</strong>
                    — {{ PdfText::stripEmoji($item['transaction']->description) }}
                    — {{ $fmt($item['transaction']->amount) }}<br>
                    Arquivo: {{ $item['fileName'] }}
                </div>
                @if($item['kind'] === 'image' && $item['imageSrc'])
                    <img src="{{ $item['imageSrc'] }}" alt="Comprovante">
                @elseif($item['kind'] === 'pdf')
                    <p>O comprovante em PDF foi anexado nas páginas seguintes deste relatório.</p>
                @else
                    <p>Este arquivo não pôde ser exibido neste PDF. Guarde o original: {{ $item['fileName'] }}.</p>
                @endif
            </div>
        @endforeach
    @endif
</body>
</html>
