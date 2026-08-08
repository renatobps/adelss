<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Inscrições — {{ $eventTitle }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #2E353E; font-size: 10px; }
        .header { border-bottom: 2px solid #0088CC; padding-bottom: 8px; margin-bottom: 12px; }
        .header h1 { margin: 0 0 2px; font-size: 15px; }
        .header p { margin: 0; font-size: 9px; color: #6C757D; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #EEF0F2; text-align: left; font-size: 9px; text-transform: uppercase; color: #6C757D; }
        th, td { padding: 5px 6px; border-bottom: 1px solid #EEF0F2; }
        td.center, th.center { text-align: center; }
        .presente { color: #1FA855; font-weight: bold; }
        .cancelado { color: #6C757D; text-decoration: line-through; }
        .assinatura { border-bottom: 1px solid #C7CDD4; display: inline-block; width: 120px; }
        .footer { margin-top: 14px; font-size: 8px; color: #6C757D; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lista de inscritos — {{ $eventTitle }}</h1>
        <p>
            {{ $event->start_date?->format('d/m/Y H:i') }}
            @if($event->location) · {{ $event->location }} @endif
            · Filtro: {{ $situacaoLabel }}@if($busca !== '') · Busca: "{{ $busca }}"@endif
            · {{ $registrations->count() }} inscrito(s)
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Inscrição</th>
                <th>Nome</th>
                <th>Status</th>
                <th>Contato</th>
                @if($event->is_paid)
                    <th>Pagamento</th>
                    <th>Valor</th>
                @endif
                <th class="center">Presença</th>
                <th class="center">Assinatura</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registrations as $r)
                <tr>
                    <td>{{ $r->registration_number ?: '—' }}</td>
                    <td class="{{ $r->status === \App\Models\EventRegistration::STATUS_CANCELADO ? 'cancelado' : '' }}">{{ $r->name }}</td>
                    <td>{{ $r->status_label }}</td>
                    <td>
                        {{ $r->email ?: '—' }}<br>
                        {{ $r->phone ?: '—' }}
                    </td>
                    @if($event->is_paid)
                        <td>{{ strtoupper((string) ($r->payment->status ?? 'pendente')) }}</td>
                        <td>{{ $r->payment ? 'R$ '.number_format((float) $r->payment->amount, 2, ',', '.') : '—' }}</td>
                    @endif
                    <td class="center">
                        @if($r->checked_in_at)
                            <span class="presente">{{ $r->checked_in_at->format('d/m H:i') }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="center"><span class="assinatura">&nbsp;</span></td>
                </tr>
            @empty
                <tr><td colspan="8">Nenhuma inscrição encontrada com os filtros aplicados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Gerado em {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
