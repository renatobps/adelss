<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo - {{ $transaction->description }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; padding: 16px; background: #e8eef5; }
        .print-buttons { text-align: center; margin-bottom: 16px; }
        .btn { padding: 10px 20px; margin: 0 5px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .recibo-wrap {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            aspect-ratio: 1447 / 1087;
            background: #fff;
        }
        @include('financial.transactions.partials.receipt-overlay-styles')
        @media print {
            .print-buttons { display: none; }
            body { background: #fff; padding: 0; }
            .recibo-wrap { max-width: none; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="print-buttons">
        <button class="btn btn-primary" onclick="window.print()">Imprimir recibo</button>
        <button class="btn btn-secondary" onclick="window.close()">Fechar</button>
    </div>
    <div class="recibo-wrap">
        @include('financial.transactions.partials.receipt-overlay')
    </div>
</body>
</html>
