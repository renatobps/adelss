<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Escala Mensal de Culto - {{ $escala->event->title }}</title>

    <style>
        @page {
            margin: 0;
            size: A4 portrait;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        .page {
            padding: 26px;
        }

        .header {
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 18px;
            margin-bottom: 18px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo {
            width: 90px;
        }

        .logo img {
            max-width: 90px;
            max-height: 90px;
            height: auto;
            width: auto;
            display: block;
        }

        .church-info {
            text-align: right;
        }

        .church-info.no-logo {
            text-align: center;
        }

        .church-name {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .title {
            font-size: 13px;
            margin-top: 4px;
            text-transform: uppercase;
            opacity: 0.9;
        }

        .info {
            background-color: #f1f5f9;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 5px 6px;
        }

        .label {
            font-weight: bold;
            color: #1e3a8a;
            width: 80px;
        }

        .value {
            color: #111827;
        }

        .area {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .area-header {
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 13px;
        }

        .area-body {
            border: 1px solid #e5e7eb;
            border-top: none;
            padding: 12px;
        }

        .volunteer-item {
            padding: 6px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .volunteer-item:last-child {
            border-bottom: none;
        }

        .volunteer-name {
            font-weight: 500;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo">
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}" alt="Logo">
                        @endif
                    </td>
                    <td class="church-info {{ !$logoBase64 ? 'no-logo' : '' }}">
                        <div class="church-name">{{ $churchName }}</div>
                        <div class="title">Escala Mensal de Culto</div>
                        <div style="margin-top: 8px; font-size: 14px; font-weight: 600;">
                            {{ $escala->event->title }}
                        </div>
                        <div style="margin-top: 4px; font-size: 12px; opacity: 0.9;">
                            {{ $escala->event->start_date->format('d/m/Y') }} às {{ $escala->event->start_date->format('H:i') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Informações da Escala -->
        <div class="info">
            <table class="info-table">
                <tr>
                    <td class="label">Culto:</td>
                    <td class="value">{{ $escala->event->title }}</td>
                </tr>
                <tr>
                    <td class="label">Data:</td>
                    <td class="value">{{ $escala->event->start_date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Horário:</td>
                    <td class="value">{{ $escala->event->start_date->format('H:i') }}</td>
                </tr>
                <tr>
                    <td class="label">Mês/Ano:</td>
                    <td class="value">{{ \Carbon\Carbon::create($escala->year, $escala->month, 1)->locale('pt_BR')->translatedFormat('F/Y') }}</td>
                </tr>
            </table>
        </div>

        <!-- Áreas de Serviço -->
        @foreach($serviceAreas as $area)
            @if(isset($volunteersByArea[$area->id]) && $volunteersByArea[$area->id]['volunteers']->count() > 0)
            @php
                $areaData = $volunteersByArea[$area->id];
            @endphp
            <div class="area">
                <div class="area-header">
                    {{ $area->name }}
                </div>
                <div class="area-body">
                    @foreach($areaData['volunteers'] as $volunteer)
                        <div class="volunteer-item">
                            <span class="volunteer-name">{{ $volunteer->member->name ?? 'Sem nome' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach

        <!-- Footer -->
        <div class="footer">
            <p>Documento gerado em {{ $generatedAt->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
