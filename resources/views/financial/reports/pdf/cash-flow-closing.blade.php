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
        @page { margin: 5mm 14mm 18mm 14mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
        }
        .header { margin: 0 0 10px; }
        .header img { width: 100%; height: auto; display: block; }
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
        h2.receita { color: #1d4ed8; border-bottom-color: #93c5fd; }
        h2.despesa { color: #b91c1c; border-bottom-color: #fca5a5; }
        table.dados.receita th { background: #dbeafe; color: #1e3a8a; }
        table.dados.despesa th { background: #fee2e2; color: #991b1b; }
        .valor-receita { color: #1d4ed8; font-weight: bold; }
        .valor-despesa { color: #b91c1c; font-weight: bold; }
        .total-row.receita td { background: #eff6ff; color: #1d4ed8; }
        .total-row.despesa td { background: #fef2f2; color: #b91c1c; }
        .resumo { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .resumo td { padding: 6px 8px; border: 1px solid #d1d5db; }
        .resumo .lbl { background: #f8fafc; width: 55%; }
        .resumo .row-receita td { color: #1d4ed8; font-weight: bold; background: #eff6ff; }
        .resumo .row-despesa td { color: #b91c1c; font-weight: bold; background: #fef2f2; }
        .saldo td { background: #1e3a8a; color: #fff; font-size: 12px; font-weight: bold; }
        .assinaturas { width: 100%; margin-top: 36px; border-collapse: collapse; }
        .assinaturas td { width: 50%; text-align: center; padding: 18px 18px 8px; vertical-align: bottom; }
        .assinaturas .linha { border-top: 1px solid #111; margin: 42px auto 8px; width: 78%; }
        .assinaturas .nome { font-size: 10px; font-weight: bold; }
        .assinaturas .assinatura-img { max-height: 48px; max-width: 180px; display: block; margin: 0 auto 4px; }
        .comp { page-break-before: always; }
        .comp img { max-width: 100%; max-height: 210mm; }
        .comp-meta { font-size: 9px; margin-bottom: 8px; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($headerSrc))
            <img src="{{ $headerSrc }}" alt="ADEL — Assembleia de Deus de Luziânia">
        @endif
    </div>

    <h2 class="receita">Entradas (receitas)</h2>
    <table class="dados receita">
        <thead>
            <tr>
                <th style="width: 22mm;">Data</th>
                <th>Descrição</th>
                <th class="num" style="width: 32mm;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entradas as $tx)
                <tr>
                    <td>{{ $tx->transaction_date?->format('d/m/Y') }}</td>
                    <td>{{ PdfText::stripEmoji($tx->description) }}</td>
                    <td class="num valor-receita">{{ $fmt($tx->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Nenhuma entrada paga neste período.</td></tr>
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
                <th style="width: 22mm;">Data</th>
                <th>Descrição</th>
                <th class="num" style="width: 32mm;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($saidas as $tx)
                <tr>
                    <td>{{ $tx->transaction_date?->format('d/m/Y') }}</td>
                    <td>{{ PdfText::stripEmoji($tx->description) }}</td>
                    <td class="num valor-despesa">{{ $fmt($tx->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Nenhuma saída paga neste período.</td></tr>
            @endforelse
            <tr class="total-row despesa">
                <td colspan="2">Total de saídas</td>
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
        <tr class="row-receita">
            <td class="lbl">(+) Total de entradas</td>
            <td class="num">{{ $fmt($totalEntradas) }}</td>
        </tr>
        <tr class="row-despesa">
            <td class="lbl">(−) Total de saídas</td>
            <td class="num">{{ $fmt($totalSaidas) }}</td>
        </tr>
        <tr class="saldo">
            <td>Saldo final em {{ $end->format('d/m/Y') }}</td>
            <td class="num">{{ $fmt($saldoFinal) }}</td>
        </tr>
    </table>

    <table class="assinaturas">
        <tr>
            <td>
                @if(!empty($pastorAssinaturaSrc))
                    <img src="{{ $pastorAssinaturaSrc }}" class="assinatura-img" alt="Assinatura do pastor dirigente">
                @else
                    <div class="linha"></div>
                @endif
                @if(!empty($pastorNome))
                    <div class="nome">{{ $pastorNome }}</div>
                @endif
                <div class="cargo">Pastor Dirigente</div>
            </td>
            <td>
                @if(!empty($tesoureiroAssinaturaSrc))
                    <img src="{{ $tesoureiroAssinaturaSrc }}" class="assinatura-img" alt="Assinatura do tesoureiro da congregação">
                @else
                    <div class="linha"></div>
                @endif
                @if(!empty($tesoureiroNome))
                    <div class="nome">{{ $tesoureiroNome }}</div>
                @endif
                <div class="cargo">Tesoureiro (a) Congregação</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="linha"></div>
                <div class="nome">Sebastião Tavares da Silva</div>
                <div class="cargo">Pr. Presidente</div>
            </td>
            <td>
                <div class="linha"></div>
                <div class="cargo">Tesoureiro (a) ADEL</div>
            </td>
        </tr>
    </table>

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
