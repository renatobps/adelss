@php
    use App\Support\PdfText;

    /** @var \App\Models\Event $event */
    /** @var \App\Models\EventRegistration $registration */

    $isPaid = (bool) $event->is_paid;
    $paymentApproved = $registration->isPaymentApproved();
    $statusLabel = $isPaid ? ($paymentApproved ? 'PAGAMENTO CONFIRMADO' : 'PAGAMENTO PENDENTE') : 'INSCRICAO CONFIRMADA';
    $statusColor = (!$isPaid || $paymentApproved) ? '#1FA855' : '#D97706';

    $eventDate = $event->start_date;
    $eventEnd = $event->end_date;
    $multiDay = $eventEnd && !$eventEnd->isSameDay($eventDate);
    $dataLinha = $multiDay
        ? $eventDate->format('d/m/Y') . ' a ' . $eventEnd->format('d/m/Y')
        : $eventDate->translatedFormat('d/m/Y (l)');
    $horaLinha = $event->all_day ? 'Dia inteiro' : $eventDate->format('H:i');

    $subtitle = PdfText::stripEmoji($event->subtitle);
    $location = PdfText::stripEmoji($event->location);

    $logoPath = public_path('img/img/LOG SS AZUL.png');
    $logo = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $responsiblePhone = trim((string) $event->responsible_phone);
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Comprovante de Inscrição</title>
<style>
    @page { margin: 12mm 12mm; size: A4 portrait; }
    * { box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10px;
        color: #2E353E;
        margin: 0;
        padding: 0;
    }
    table { border-collapse: collapse; width: 100%; }
    td { vertical-align: top; }

    .banner-wrap { width: 100%; margin-bottom: 5mm; }
    .banner-wrap img { width: 100%; max-height: 62mm; border-radius: 4px; }
    .banner-fallback {
        background: #2E353E;
        color: #fff;
        text-align: center;
        padding: 10mm 6mm;
        border-radius: 4px;
        margin-bottom: 5mm;
    }
    .banner-fallback .ttl { font-size: 20px; font-weight: bold; }

    .doc-title {
        font-size: 9px;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: #6C757D;
        margin-bottom: 1.5mm;
    }
    .event-name { font-size: 17px; font-weight: bold; margin-bottom: 1mm; }
    .event-subtitle { font-size: 10px; color: #6C757D; margin-bottom: 4mm; }

    .number-box {
        border: 2px solid #2E353E;
        border-radius: 5px;
        padding: 3mm 4mm;
        text-align: center;
        background: #F6F8FA;
    }
    .number-box .lbl { font-size: 7.5px; letter-spacing: 1.5px; text-transform: uppercase; color: #6C757D; }
    .number-box .num { font-size: 19px; font-weight: bold; letter-spacing: 1px; margin-top: 1mm; }

    .status-badge {
        display: inline-block;
        color: #fff;
        font-weight: bold;
        font-size: 9px;
        letter-spacing: 1px;
        border-radius: 3px;
        padding: 1.6mm 3.5mm;
    }

    .section-title {
        font-size: 8px;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #6C757D;
        border-bottom: 1px solid #EEF0F2;
        padding-bottom: 1.2mm;
        margin: 5mm 0 2.5mm;
        font-weight: bold;
    }
    .info-table td { padding: 1.2mm 0; font-size: 10px; }
    .info-table .k { color: #6C757D; width: 32mm; }
    .info-table .v { font-weight: bold; }

    .qr-cell { text-align: center; }
    .qr-cell img { width: 40mm; height: 40mm; }
    .qr-note { font-size: 7.5px; color: #6C757D; margin-top: 1mm; }

    .instructions {
        background: #F6F8FA;
        border-left: 3px solid #0088CC;
        border-radius: 3px;
        padding: 3mm 4mm;
        margin-top: 5mm;
        font-size: 9px;
        color: #2E353E;
        line-height: 1.55;
    }
    .instructions .t { font-weight: bold; margin-bottom: 1mm; }

    .footer {
        margin-top: 7mm;
        border-top: 1px solid #EEF0F2;
        padding-top: 2.5mm;
        font-size: 7.5px;
        color: #8A97A3;
    }
    .footer img { width: 26px; }
</style>
</head>
<body>

    @if($bannerDataUri)
        <div class="banner-wrap"><img src="{{ $bannerDataUri }}" alt=""></div>
    @else
        <div class="banner-fallback">
            <div class="ttl">{{ $eventTitle }}</div>
        </div>
    @endif

    <div class="doc-title">Comprovante de Inscrição</div>
    <div class="event-name">{{ $eventTitle }}</div>
    @if($subtitle !== '')
        <div class="event-subtitle">{{ $subtitle }}</div>
    @endif

    <table>
        <tr>
            <td style="width: 62%; padding-right: 5mm;">
                <div class="number-box">
                    <div class="lbl">Número da inscrição</div>
                    <div class="num">{{ $registration->registration_number }}</div>
                </div>

                <div class="section-title">Dados do inscrito</div>
                <table class="info-table">
                    <tr><td class="k">Nome</td><td class="v">{{ PdfText::stripEmoji($registration->name) }}</td></tr>
                    <tr><td class="k">E-mail</td><td class="v">{{ $registration->email ?: '-' }}</td></tr>
                    <tr><td class="k">Telefone</td><td class="v">{{ $registration->phone ?: '-' }}</td></tr>
                </table>

                <div class="section-title">Dados do evento</div>
                <table class="info-table">
                    <tr><td class="k">Evento</td><td class="v">{{ $eventTitle }}</td></tr>
                    <tr><td class="k">Data</td><td class="v">{{ $dataLinha }}</td></tr>
                    <tr><td class="k">Horário</td><td class="v">{{ $horaLinha }}</td></tr>
                    <tr><td class="k">Local</td><td class="v">{{ $location !== '' ? $location : 'A confirmar' }}</td></tr>
                    @if($isPaid)
                        <tr><td class="k">Ingresso</td><td class="v">R$ {{ number_format((float) ($event->price ?? 0), 2, ',', '.') }}</td></tr>
                    @endif
                </table>

                <div style="margin-top: 4mm;">
                    <span class="status-badge" style="background: {{ $statusColor }};">{{ $statusLabel }}</span>
                </div>
            </td>
            <td style="width: 38%;" class="qr-cell">
                <div class="section-title" style="text-align:center;">Validação na entrada</div>
                <img src="{{ $qrDataUri }}" alt="QR Code">
                <div class="qr-note">Apresente este QR Code na entrada do evento<br>para liberar seu acesso.</div>
            </td>
        </tr>
    </table>

    <div class="instructions">
        <div class="t">Instruções de acesso</div>
        Apresente este comprovante (impresso ou no celular) na entrada do evento.
        O QR Code é pessoal e intransferível — cada comprovante libera a entrada de um participante.
        @if($isPaid && !$paymentApproved)
            Seu pagamento ainda está pendente: conclua-o antes do evento para garantir sua vaga.
        @endif
    </div>

    <div class="footer">
        <table>
            <tr>
                <td>
                    {{ config('app.name') }}
                    @if($event->responsible_name || $responsiblePhone !== '')
                        &bull; Contato: {{ PdfText::stripEmoji($event->responsible_name ?: 'Organização') }}{{ $responsiblePhone !== '' ? ' - ' . $responsiblePhone : '' }}
                    @endif
                    <br>
                    Emitido em {{ now()->format('d/m/Y H:i') }}
                </td>
                <td style="text-align:right;">
                    @if($logo)<img src="{{ $logo }}" alt="">@endif
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
