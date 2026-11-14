<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $cartela->identificador }} — {{ $rifa->nome }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #182c42; font-size: 12px; }
        h1 { font-size: 24px; margin-bottom: 6px; }
        .header { border-bottom: 3px solid #ee8237; padding-bottom: 16px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        td { width: 20%; border: 1px solid #b9c5d2; text-align: center; padding: 12px 4px; }
        .numero { font-size: 19px; font-weight: bold; }
        .status { font-size: 9px; color: #556575; }
        .footer { margin-top: 18px; font-size: 10px; color: #556575; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $rifa->nome }}</h1>
        <strong>{{ $cartela->identificador }}</strong><br>
        Vendedor: {{ $cartela->vendedor?->name ?? 'Não atribuído' }}<br>
        Valor por número: R$ {{ number_format((float) $rifa->valor_numero, 2, ',', '.') }}<br>
        Sorteio: {{ $rifa->data_sorteio?->format('d/m/Y') ?? 'A definir' }}
    </div>
    <table>
        @foreach($cartela->numeros->chunk(5) as $linha)
            <tr>
                @foreach($linha as $numero)
                    <td><div class="numero">{{ $numero->numero }}</div><div class="status">{{ ucfirst($numero->status) }}</div></td>
                @endforeach
                @for($i = $linha->count(); $i < 5; $i++)<td></td>@endfor
            </tr>
        @endforeach
    </table>
    <p class="footer">Cartela destinada ao vendedor indicado. Situação dos números em {{ now()->format('d/m/Y H:i') }}. Consulte a organização para informações atualizadas.</p>
</body>
</html>
