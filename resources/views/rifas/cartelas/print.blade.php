<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Impressão de cartelas - {{ $rifa->nome }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .cartela { page-break-inside: avoid; border: 1px solid #333; margin-bottom: 20px; padding: 10px; }
        .grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
        .item { border: 1px solid #ccc; text-align: center; padding: 8px; }
    </style>
</head>
<body>
    <h2>{{ $rifa->nome }}</h2>
    <p>Data do sorteio: {{ optional($rifa->data_sorteio)->format('d/m/Y') ?: 'Não definida' }}</p>

    @foreach($cartelas as $cartela)
        <div class="cartela">
            <h3>{{ $cartela->identificador }}</h3>
            <div class="grid">
                @foreach($cartela->numeros as $numero)
                    <div class="item">{{ $numero->numero }}</div>
                @endforeach
            </div>
        </div>
    @endforeach

    <script>window.print();</script>
</body>
</html>
