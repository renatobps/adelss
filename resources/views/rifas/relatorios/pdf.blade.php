<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Rifas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f3f3f3; text-align: left; }
    </style>
</head>
<body>
    <h2>Relatório de Rifas</h2>
    <p>Gerado em: {{ $geradoEm }}</p>

    <table>
        <thead>
            <tr>
                <th>Rifa</th>
                <th>Número</th>
                <th>Status</th>
                <th>Comprador</th>
                <th>Telefone</th>
                <th>Vendedor</th>
                <th>Data</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dados as $row)
                <tr>
                    <td>{{ $row->rifa_nome }}</td>
                    <td>{{ $row->numero }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
                    <td>{{ $row->comprador_nome }}</td>
                    <td>{{ $row->comprador_telefone }}</td>
                    <td>{{ $row->vendedor_nome }}</td>
                    <td>{{ $row->data_venda }}</td>
                    <td>R$ {{ number_format((float) $row->valor_numero, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Nenhum dado para exibir.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
