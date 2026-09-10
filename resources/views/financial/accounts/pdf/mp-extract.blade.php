@php
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Extrato Mercado Pago</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 15px; margin: 0 0 4px; color: #1e3a8a; }
        .meta { color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 8px; text-transform: uppercase; color: #6b7280; }
        .num { text-align: right; white-space: nowrap; }
        .in { color: #15803d; }
        .out { color: #b91c1c; }
        .totais { margin-top: 10px; width: 55%; }
        .totais td { font-weight: bold; }
    </style>
</head>
<body>
    <h1>Extrato Mercado Pago</h1>
    <div class="meta">
        Últimos {{ $days }} dias · gerado em {{ now()->format('d/m/Y H:i') }}
        @if($q !== '')
            · filtro: “{{ $q }}”
        @endif
        · {{ count($items) }} movimento(s)
    </div>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Descrição</th>
                <th>Meio</th>
                <th class="num">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                @php
                    $dir = $item['direction'] ?? '';
                    $tipo = $dir === 'in' ? 'Entrada' : ($dir === 'out' ? 'Saída' : 'Pendente');
                    $cls = $dir === 'in' ? 'in' : ($dir === 'out' ? 'out' : '');
                @endphp
                <tr>
                    <td>{{ $item['occurred_at_label'] ?? '—' }}</td>
                    <td class="{{ $cls }}">{{ $tipo }}</td>
                    <td>
                        {{ $item['description'] ?? 'Pagamento' }}
                        @if(!empty($item['payer']))
                            <div style="color:#6b7280;font-size:8px;">{{ $item['payer'] }}</div>
                        @endif
                    </td>
                    <td>{{ $item['method'] ?? '—' }}</td>
                    <td class="num {{ $cls }}">{{ $dir === 'out' ? '-' : '' }}{{ $fmt($item['amount'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhum movimento para exportar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <table class="totais">
        <tr>
            <td class="in">Entradas</td>
            <td class="num in">{{ $fmt($in_total) }}</td>
        </tr>
        <tr>
            <td class="out">Saídas</td>
            <td class="num out">{{ $fmt($out_total) }}</td>
        </tr>
    </table>
</body>
</html>
