<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo - {{ $transaction->description }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .receipt-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        hr {
            border: none;
            border-top: 1px solid #333;
            margin: 20px 0;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
        }
        .receipt-body {
            text-align: justify;
            line-height: 1.8;
            font-size: 14px;
            margin: 30px 0;
        }
        .receipt-body strong {
            font-weight: bold;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 10px;
            text-align: center;
        }
        .signature-name {
            font-weight: bold;
            margin-top: 10px;
        }
        .signature-date {
            margin-top: 5px;
        }
        .print-buttons {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        @media print {
            .print-buttons {
                display: none;
            }
            body {
                background-color: white;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="print-buttons">
        <button class="btn btn-primary" onclick="window.print()">Imprimir recibo</button>
        <button class="btn btn-secondary" onclick="window.close()">Fechar</button>
    </div>

    <div class="receipt-container">
        <div class="receipt-header">
            <div class="logo">
                <img src="{{ asset('img/img/LOG SS AZUL.png') }}" alt="ADEL" style="max-width: 100px;">
            </div>
            <div class="company-name">ADEL SÃO SEBASTIÃO</div>
        </div>

        <hr>

        <div class="receipt-title">RECIBO</div>

        <div class="receipt-body">
            @php
                $isDizimo = $transaction->category && (
                    stripos($transaction->category->name, 'dizimo') !== false || 
                    stripos($transaction->category->name, 'dízimo') !== false
                );
                
                if ($transaction->type === 'receita') {
                    // Para receitas (dízimo ou oferta), mostrar nome do membro
                    if ($transaction->member) {
                        $fromName = strtoupper($transaction->member->name);
                    } else {
                        $fromName = strtoupper($transaction->received_from_other ?? 'OUTROS');
                    }
                    $categoryName = strtoupper($transaction->category ? $transaction->category->name : 'RECEITA');
                } else {
                    // Para despesas, sempre mostrar "ADEL SÃO SEBASTIÃO"
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
            <div class="signature-date">{{ $date }}</div>
        </div>
    </div>

    <script>
        // Auto-print quando a página carregar (opcional)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 500);
        // };
    </script>
</body>
</html>
