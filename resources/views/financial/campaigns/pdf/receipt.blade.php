<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo — {{ $campaign->name }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 portrait;
        }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .page { padding: 26px; }
        .slip {
            width: 100%;
            border: 1px solid #94a3b8;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .slip td { vertical-align: top; }
        .canhoto {
            width: 32%;
            border-right: 2px dashed #94a3b8;
            padding: 12px;
        }
        .recibo { width: 68%; padding: 12px 16px; }
        .titulo {
            font-size: 12px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            margin: 0 0 6px 0;
        }
        .valor {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 4px 0;
        }
        .campo { margin: 3px 0; }
        .rotulo { color: #6b7280; font-size: 9px; text-transform: uppercase; }
        .mensagem {
            font-style: italic;
            color: #475569;
            border-left: 3px solid #1e3a8a;
            padding-left: 8px;
            margin: 8px 0;
            font-size: 10px;
        }
        .assinatura {
            margin-top: 24px;
            border-top: 1px solid #6b7280;
            width: 220px;
            padding-top: 3px;
            font-size: 9px;
            color: #6b7280;
            text-align: center;
        }
        .cabecalho td { vertical-align: middle; }
        .badge-pago {
            display: inline-block;
            background-color: #16a34a;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 8px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="page">
        @foreach($installments as $installment)
            @php($sponsor = $installment->sponsor)
            <table class="slip">
                <tr>
                    <td class="canhoto">
                        <p class="titulo">Canhoto</p>
                        <div class="valor">R$ {{ number_format((float) $installment->amount, 2, ',', '.') }}</div>
                        <div class="campo"><span class="rotulo">Campanha</span><br>{{ $campaign->name }}</div>
                        <div class="campo"><span class="rotulo">Patrocinador</span><br>{{ $sponsor->name }}</div>
                        <div class="campo"><span class="rotulo">Parcela</span><br>{{ $installment->installment_number }}/{{ $campaign->installments_count }}</div>
                        <div class="campo"><span class="rotulo">Data do pagamento</span><br>{{ $installment->paid_at?->format('d/m/Y') ?? '____/____/______' }}</div>
                        @if($installment->receipt_number)
                            <div class="campo"><span class="rotulo">Recibo nº</span><br>{{ $installment->receipt_number }}</div>
                        @endif
                    </td>
                    <td class="recibo">
                        <table class="cabecalho" width="100%">
                            <tr>
                                <td>
                                    <p class="titulo">Recibo {{ $installment->receipt_number ? '— nº ' . $installment->receipt_number : '' }}</p>
                                </td>
                                <td style="text-align: right;">
                                    @if(!empty($logoPath) && file_exists($logoPath))
                                        <img src="{{ $logoPath }}" alt="Logo" style="max-width: 70px; max-height: 40px;">
                                    @endif
                                </td>
                            </tr>
                        </table>
                        <div class="campo">
                            Recebemos de <strong>{{ $sponsor->name }}</strong> a importância de
                            <strong>R$ {{ number_format((float) $installment->amount, 2, ',', '.') }}</strong>,
                            referente à parcela <strong>{{ $installment->installment_number }}/{{ $campaign->installments_count }}</strong>
                            da campanha <strong>{{ $campaign->name }}</strong>@if($campaign->department) ({{ $campaign->department->name }})@endif.
                        </div>
                        @if($installment->isPaid())
                            <div class="campo">
                                <span class="badge-pago">Pago</span>
                                &nbsp;em {{ $installment->paid_at?->format('d/m/Y \à\s H:i') }}
                                — {{ $installment->paymentMethodLabel() }}
                            </div>
                        @endif
                        @if($campaign->receipt_message)
                            <div class="mensagem">{{ $campaign->receipt_message }}</div>
                        @endif
                        <div class="assinatura">Assinatura do responsável</div>
                    </td>
                </tr>
            </table>
        @endforeach
    </div>
</body>
</html>
