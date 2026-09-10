@php
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Demonstrativo financeiro — {{ $year }}</title>
    <style>
        @page { margin: 7mm 12mm 8mm 12mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111827;
            margin: 0;
        }
        .header { text-align: center; margin: 0 0 2px; }
        .header img {
            width: 170mm;
            height: 26.5mm;
            display: block;
            margin: 0 auto 2px;
        }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .meta td { padding: 1px 0; }
        .title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.4px;
            margin: 2px 0 1px;
            text-transform: uppercase;
        }
        .periodo {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            margin: 0 0 6px;
        }
        table.form { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.form th, table.form td {
            border: 1px solid #cbd5e1;
            padding: 3px 5px;
        }
        table.form th {
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        table.form .num { text-align: right; width: 32mm; white-space: nowrap; font-weight: bold; }
        table.form.receita th { background: #dbeafe; color: #1e3a8a; }
        table.form.receita .sec th { background: #1d4ed8; color: #fff; text-align: center; }
        table.form.receita td { color: #1e3a8a; }
        table.form.receita .total td { background: #eff6ff; font-weight: bold; }
        table.form.despesa th { background: #fee2e2; color: #991b1b; }
        table.form.despesa .sec th { background: #b91c1c; color: #fff; text-align: center; }
        table.form.despesa td { color: #991b1b; }
        table.form.despesa .total td { background: #fef2f2; font-weight: bold; }
        table.form.resumo th { background: #e5e7eb; color: #111827; }
        table.form.resumo .sec th { background: #334155; color: #fff; text-align: center; }
        table.form.resumo .row-receita td { color: #1d4ed8; background: #eff6ff; font-weight: bold; }
        table.form.resumo .row-despesa td { color: #b91c1c; background: #fef2f2; font-weight: bold; }
        table.form.resumo .total td { background: #1e3a8a; color: #fff; font-weight: bold; }
        .assinaturas { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .assinaturas td { width: 50%; text-align: center; padding: 6px 8px 2px; vertical-align: bottom; }
        .assinaturas .linha { border-top: 1px solid #111; margin: 16px auto 4px; width: 78%; }
        .assinaturas .nome { font-size: 9px; font-weight: bold; color: #111827; padding-top: 2px; }
        .assinaturas .cargo { font-size: 8px; color: #4b5563; }
        .assinaturas .assinatura-img { max-height: 28px; max-width: 130px; display: block; margin: 0 auto 2px; }
        .assinatura-bloco { width: 100%; border-collapse: collapse; }
        .recibos-folha { page-break-inside: avoid; }
        .recibo-slot {
            position: relative;
            width: 186mm;
            height: 132mm;
            margin: 0 auto 3mm;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .recibo-corte {
            border-top: 1px dashed #94a3b8;
            margin: 2mm 0 3mm;
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        @include('financial.transactions.partials.receipt-overlay-styles')
        .recibo-slot .recibo-page { width: 100%; height: 100%; }
        .recibo-slot .f { font-size: 11px; }
        .recibo-slot .f-numero,
        .recibo-slot .f-valor { font-size: 13px; }
        .recibo-slot .f-assinatura {
            top: 64%;
            left: 16%;
            width: 58%;
            height: 22%;
            overflow: visible;
        }
        .recibo-slot .f-assinatura-img {
            height: 86px;
            max-height: 86px;
            max-width: 260px;
            width: auto;
            display: block;
        }
    </style>
</head>
<body>
@foreach($months as $mes)
    @php
        $recibosDoMes = $mes['saidas'] ?? [];
        $folhasRecibo = array_chunk($recibosDoMes, 2);
        $temRecibos = $folhasRecibo !== [];
    @endphp
    <div class="page" @if(! $loop->last || $temRecibos) style="page-break-after: always;" @endif>
        <div class="header">
            @if(!empty($headerSrc))
                <img src="{{ $headerSrc }}" alt="ADEL">
            @endif
        </div>

        <table class="meta">
            <tr>
                <td><strong>CONGREGAÇÃO:</strong> {{ $congregacao }}</td>
                <td style="text-align: right;"><strong>MÊS:</strong> {{ $mes['month_name'] }}/{{ $year }}</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: right;">{{ $cidade }}, {{ $mes['date_label'] }}</td>
            </tr>
        </table>

        <div class="title">Demonstrativo financeiro</div>
        <div class="periodo">Referente ao mês de {{ $mes['month_name_lower'] }} de {{ $year }}</div>

        <table class="form receita">
            <tr class="sec">
                <th colspan="2">Discriminação das entradas</th>
            </tr>
            <tr>
                <th>Descrição</th>
                <th class="num">Valor</th>
            </tr>
            <tr>
                <td>Dízimos dos obreiros</td>
                <td class="num">{{ $fmt($mes['dizimo_obreiros']) }}</td>
            </tr>
            <tr>
                <td>Dízimos dos membros e congregados</td>
                <td class="num">{{ $fmt($mes['dizimo_membros']) }}</td>
            </tr>
            <tr class="total">
                <td>Total das entradas</td>
                <td class="num">{{ $fmt($mes['total_entradas']) }}</td>
            </tr>
        </table>

        <table class="form despesa">
            <tr class="sec">
                <th colspan="2">Discriminação das saídas</th>
            </tr>
            <tr>
                <th>Descrição</th>
                <th class="num">Valor</th>
            </tr>
            @forelse($mes['saidas'] ?? [] as $saida)
                <tr>
                    <td>{{ $saida['description'] }}</td>
                    <td class="num">{{ $fmt($saida['amount']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Nenhuma saída paga neste mês.</td>
                </tr>
            @endforelse
            <tr class="total">
                <td>Total das saídas</td>
                <td class="num">{{ $fmt($mes['total_saidas']) }}</td>
            </tr>
        </table>

        <table class="form resumo">
            <tr class="sec">
                <th colspan="2">Resumo</th>
            </tr>
            <tr>
                <th>Descrição</th>
                <th class="num">Valor</th>
            </tr>
            <tr>
                <td>Saldo do mês anterior</td>
                <td class="num">{{ $fmt($mes['saldo_anterior']) }}</td>
            </tr>
            <tr class="row-receita">
                <td>Entradas do mês</td>
                <td class="num">{{ $fmt($mes['total_entradas']) }}</td>
            </tr>
            <tr class="row-despesa">
                <td>Saídas do mês</td>
                <td class="num">{{ $fmt($mes['total_saidas']) }}</td>
            </tr>
            <tr class="total">
                <td>Saldo</td>
                <td class="num">{{ $fmt($mes['saldo_final']) }}</td>
            </tr>
        </table>

        <table class="assinaturas">
            <tr>
                <td>
                    <table class="assinatura-bloco">
                        <tr>
                            <td>
                                @if(!empty($pastorAssinaturaSrc))
                                    <img src="{{ $pastorAssinaturaSrc }}" class="assinatura-img" alt="Assinatura do pastor dirigente">
                                @else
                                    <div class="linha"></div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="nome">{{ $pastorNome ?: ' ' }}</td>
                        </tr>
                        <tr>
                            <td class="cargo">Pastor Dirigente</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table class="assinatura-bloco">
                        <tr>
                            <td>
                                @if(!empty($tesoureiroAssinaturaSrc))
                                    <img src="{{ $tesoureiroAssinaturaSrc }}" class="assinatura-img" alt="Assinatura do tesoureiro da congregação">
                                @else
                                    <div class="linha"></div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="nome">{{ $tesoureiroNome ?: ' ' }}</td>
                        </tr>
                        <tr>
                            <td class="cargo">Tesoureiro (a) Congregação</td>
                        </tr>
                    </table>
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
    </div>
    @foreach($folhasRecibo as $par)
        <div class="recibos-folha" @unless($loop->parent->last && $loop->last) style="page-break-after: always;" @endunless>
            @foreach($par as $recibo)
                @include('financial.reports.pdf.partials.matrix-expense-receipt', [
                    'recibo' => $recibo,
                    'reciboFundoSrc' => $reciboFundoSrc ?? null,
                    'pastorNome' => $pastorNome ?? null,
                    'pastorAssinaturaSrc' => $pastorAssinaturaSrc ?? null,
                    'tesoureiroNome' => $tesoureiroNome ?? null,
                    'tesoureiroAssinaturaSrc' => $tesoureiroAssinaturaSrc ?? null,
                ])
                @if(! $loop->last)
                    <div class="recibo-corte">corte</div>
                @endif
            @endforeach
        </div>
    @endforeach
@endforeach
</body>
</html>
