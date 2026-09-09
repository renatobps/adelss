@php
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Demonstrativo financeiro — {{ $year }}</title>
    <style>
        @page { margin: 10mm 14mm 12mm 14mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 0;
        }
        .page { min-height: 250mm; }
        .header img { width: 100%; height: auto; display: block; margin-bottom: 8px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .meta td { padding: 2px 0; }
        .title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.6px;
            margin: 4px 0 2px;
            text-transform: uppercase;
        }
        .periodo {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 12px;
        }
        table.form { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.form th, table.form td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
        }
        table.form th {
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        table.form .num { text-align: right; width: 38mm; white-space: nowrap; font-weight: bold; }
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
        .assinaturas { width: 100%; margin-top: 28px; border-collapse: collapse; }
        .assinaturas td { width: 50%; text-align: center; padding: 14px 12px 6px; vertical-align: bottom; }
        .assinaturas .linha { border-top: 1px solid #111; margin: 36px auto 6px; width: 78%; }
        .assinaturas .nome { font-size: 10px; font-weight: bold; }
        .assinaturas .cargo { font-size: 9px; }
        .assinaturas .assinatura-img { max-height: 46px; max-width: 170px; display: block; margin: 0 auto 4px; }
    </style>
</head>
<body>
@foreach($months as $mes)
    <div class="page" @unless($loop->last) style="page-break-after: always;" @endunless>
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
            <tr>
                <td>{{ $mes['saidas_label'] }}</td>
                <td class="num">{{ $fmt($mes['total_saidas']) }}</td>
            </tr>
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
    </div>
@endforeach
</body>
</html>
