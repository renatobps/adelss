<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo #{{ $transaction->id }}</title>
    <style>
        @page { margin: 0; }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
        }
        @include('financial.transactions.partials.receipt-overlay-styles')
    </style>
</head>
<body>
    @include('financial.transactions.partials.receipt-overlay')
</body>
</html>
