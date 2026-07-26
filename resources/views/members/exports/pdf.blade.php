<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Membros</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #2E353E; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #0088CC; }
        .meta { color: #6C757D; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #EEF0F2; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #f5f7f9; font-size: 9px; text-transform: uppercase; letter-spacing: .03em; color: #6C757D; }
        tr:nth-child(even) td { background: #fafbfc; }
    </style>
</head>
<body>
    <h1>Lista de Membros</h1>
    <div class="meta">Gerado em {{ now()->format('d/m/Y H:i') }} · {{ $members->count() }} registro(s)</div>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Telefone</th>
                <th>E-mail</th>
                <th>Status</th>
                <th>Nascimento</th>
                <th>Cidade</th>
                <th>Cargo</th>
                <th>Departamentos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $m)
                <tr>
                    <td>{{ $m->name }}</td>
                    <td>{{ $m->phone }}</td>
                    <td>{{ $m->email }}</td>
                    <td>{{ $m->status_label }}</td>
                    <td>{{ optional($m->birth_date)->format('d/m/Y') }}</td>
                    <td>{{ trim(($m->city ?? '') . ($m->state ? '/' . $m->state : '')) }}</td>
                    <td>{{ $m->role->name ?? '' }}</td>
                    <td>{{ $m->departments->pluck('name')->join(', ') ?: ($m->department->name ?? '') }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Nenhum membro encontrado com os filtros aplicados.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
