<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo #{{ $transaction->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 14px;
            color: #333;
            margin: 0;
            padding: 24px;
        }
        .receipt-container {
            border: 1px solid #ddd;
            padding: 32px;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        .receipt-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin: 24px 0;
        }
        .receipt-body {
            line-height: 1.8;
            text-align: justify;
            margin: 24px 0;
        }
        .signature-line {
            margin-top: 48px;
            text-align: center;
        }
        .signature-name {
            font-weight: bold;
            border-top: 1px solid #333;
            display: inline-block;
            padding-top: 8px;
            min-width: 260px;
        }
        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            @if(!empty($logoPath) && file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="ADEL" style="max-width: 90px;">
            @endif
            <div class="company-name">ADEL SÃO SEBASTIÃO</div>
        </div>

        <hr>

        <div class="receipt-title">RECIBO</div>

        <div class="receipt-body">
            @php
                if ($transaction->type === 'receita') {
                    if ($transaction->member) {
                        $fromName = strtoupper($transaction->member->name);
                    } else {
                        $fromName = strtoupper($transaction->received_from_other ?? 'OUTROS');
                    }
                    $categoryName = strtoupper($transaction->category ? $transaction->category->name : 'RECEITA');
                } else {
                    $fromName = 'ADEL SÃO SEBASTIÃO';
                    $categoryName = strtoupper($transaction->category ? $transaction->category->name : 'DESPESA');
                }

                $amount = number_format($transaction->amount, 2, ',', '.');
                $date = $transaction->transaction_date->format('d/m/Y');
                $city = 'SÃO SEBASTIÃO - DISTRITO FEDERAL';
            @endphp

            Recebi(emos) de <strong>{{ $fromName }}</strong>, a quantia de <strong>R$ {{ $amount }}</strong>, correspondente a "<strong>{{ $categoryName }}</strong>", e para clareza firmo(amos) o presente na cidade de <strong>{{ $city }}</strong> no dia <strong>{{ $date }}</strong>.
        </div>

        <hr>

        <div class="signature-line">
            <div class="signature-name">ADEL SÃO SEBASTIÃO</div>
            <div>{{ $date }}</div>
        </div>
    </div>
</body>
</html>
