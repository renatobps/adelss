<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Respostas — {{ $form->title }}</title>
    <style>
        @page { margin: 18mm 12mm; size: A4 landscape; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #2E353E; margin: 0; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .meta { color: #6C757D; font-size: 9px; margin: 0 0 12px; }
        .meta span { margin-right: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #DDD; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background: #F3F4F6; font-size: 8px; text-transform: uppercase; }
        tr:nth-child(even) td { background: #FAFBFC; }
        .empty { padding: 16px; text-align: center; color: #6C757D; }
        .aviso { margin-top: 10px; font-size: 8px; color: #6C757D; }
    </style>
</head>
<body>
    <h1>{{ $form->title }}</h1>

    <p class="meta">
        <span>Respostas: {{ $total }}</span>
        @if(filled($filters['start_date']) || filled($filters['end_date']))
            <span>
                Período:
                {{ filled($filters['start_date']) ? \Illuminate\Support\Carbon::parse($filters['start_date'])->format('d/m/Y') : 'início' }}
                a
                {{ filled($filters['end_date']) ? \Illuminate\Support\Carbon::parse($filters['end_date'])->format('d/m/Y') : 'hoje' }}
            </span>
        @endif
        @if(filled($filters['q']))
            <span>Busca: {{ $filters['q'] }}</span>
        @endif
        <span>Gerado em: {{ $geradoEm }}</span>
    </p>

    @if($submissions->count() === 0)
        <p class="empty">Nenhuma resposta para os filtros informados.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 62px;">Enviada</th>
                    @if($form->requires_identification)
                        <th>Nome</th>
                        <th style="width: 70px;">Telefone</th>
                    @endif
                    @foreach($fields as $campo)
                        <th>{{ $campo->label }}{{ $campo->trashed() ? ' (removido)' : '' }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($submissions as $submission)
                    @php $respostas = $submission->answersByField(); @endphp
                    <tr>
                        <td>{{ optional($submission->submitted_at)->format('d/m/y H:i') }}</td>
                        @if($form->requires_identification)
                            <td>{{ $submission->respondent_name }}</td>
                            <td>{{ $submission->respondent_phone }}</td>
                        @endif
                        @foreach($fields as $campo)
                            <td>{{ \App\Support\PdfText::stripEmoji($campo->formatValue($respostas[$campo->id] ?? null)) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($total > $limite)
            <p class="aviso">
                Exibindo as {{ $limite }} respostas mais recentes de {{ $total }}.
                Para a lista completa, use a exportação em Excel.
            </p>
        @endif
    @endif
</body>
</html>
