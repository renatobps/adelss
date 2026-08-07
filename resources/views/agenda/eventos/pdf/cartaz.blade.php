@php
    use App\Support\PdfText;

    /** @var \App\Models\Event $event */

    $eventDate = $event->start_date;
    $eventEnd = $event->end_date;
    $multiDay = $eventEnd && !$eventEnd->isSameDay($eventDate);
    $dataLinha = $multiDay
        ? $eventDate->format('d/m/Y') . ' a ' . $eventEnd->format('d/m/Y')
        : $eventDate->translatedFormat('d \d\e F \d\e Y');
    $horaLinha = $event->all_day ? null : $eventDate->format('H:i');

    $subtitle = PdfText::stripEmoji($event->subtitle);
    $location = PdfText::stripEmoji($event->location);

    $logoPath = public_path('img/img/LOG SS AZUL.png');
    $logo = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Cartaz — {{ $eventTitle }}</title>
<style>
    @page { margin: 0; size: A4 portrait; }
    * { box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        color: #2E353E;
        margin: 0;
        padding: 0;
    }
    table { border-collapse: collapse; width: 100%; }
    td { vertical-align: top; }

    .page { padding: 14mm 16mm; }

    .banner-wrap { text-align: center; }
    .banner-wrap img { width: 100%; max-height: 96mm; border-radius: 6px; }
    .banner-fallback {
        background: #2E353E;
        border-radius: 6px;
        padding: 26mm 10mm;
        text-align: center;
        color: #fff;
    }
    .banner-fallback .ttl { font-size: 34px; font-weight: bold; }

    .title { font-size: 30px; font-weight: bold; text-align: center; margin-top: 8mm; line-height: 1.15; }
    .subtitle { font-size: 14px; color: #6C757D; text-align: center; margin-top: 2mm; }

    .meta { margin-top: 7mm; text-align: center; }
    .meta-item { font-size: 14px; margin-bottom: 2mm; }
    .meta-item .lbl { color: #6C757D; font-size: 10px; text-transform: uppercase; letter-spacing: 2px; display: block; }
    .meta-item .val { font-weight: bold; font-size: 16px; }

    .qr-block { margin-top: 9mm; text-align: center; }
    .qr-block img.qr { width: 72mm; height: 72mm; }
    .qr-call {
        font-size: 16px;
        font-weight: bold;
        margin-top: 3mm;
    }
    .qr-sub { font-size: 11px; color: #6C757D; margin-top: 1mm; }
    .qr-url { font-size: 10px; color: #0088CC; margin-top: 2mm; }

    .footer {
        position: fixed;
        bottom: 8mm;
        left: 16mm;
        right: 16mm;
        border-top: 1px solid #EEF0F2;
        padding-top: 2.5mm;
        font-size: 9px;
        color: #8A97A3;
    }
    .footer img { width: 30px; }
</style>
</head>
<body>
<div class="page">

    @if($bannerDataUri)
        <div class="banner-wrap"><img src="{{ $bannerDataUri }}" alt=""></div>
    @else
        <div class="banner-fallback"><div class="ttl">{{ $eventTitle }}</div></div>
    @endif

    <div class="title">{{ $eventTitle }}</div>
    @if($subtitle !== '')
        <div class="subtitle">{{ $subtitle }}</div>
    @endif

    <div class="meta">
        <table>
            <tr>
                <td style="text-align:center; width: {{ $location !== '' ? '50%' : '100%' }};">
                    <div class="meta-item">
                        <span class="lbl">Data{{ $horaLinha ? ' e horário' : '' }}</span>
                        <span class="val">{{ $dataLinha }}{{ $horaLinha ? ' — ' . $horaLinha : '' }}</span>
                    </div>
                </td>
                @if($location !== '')
                    <td style="text-align:center; width:50%;">
                        <div class="meta-item">
                            <span class="lbl">Local</span>
                            <span class="val">{{ $location }}</span>
                        </div>
                    </td>
                @endif
            </tr>
        </table>
    </div>

    <div class="qr-block">
        <img class="qr" src="{{ $qrDataUri }}" alt="QR Code">
        <div class="qr-call">Aponte a câmera do celular e inscreva-se</div>
        <div class="qr-sub">Programação completa, informações e inscrição online</div>
        <div class="qr-url">{{ $publicUrl }}</div>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td>{{ config('app.name') }}</td>
                <td style="text-align:right;">
                    @if($logo)<img src="{{ $logo }}" alt="">@endif
                </td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>
