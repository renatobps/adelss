<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Escala de Serviço - {{ $escala->title }}</title>

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

        /* ================= HEADER ================= */
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

        /* ================= INFO ================= */
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

        /* ================= ÁREAS ================= */
        .area {
            margin-bottom: 22px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            page-break-inside: avoid;
        }

        .area-header {
            background-color: #e0e7ff;
            padding: 10px;
        }

        .area-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .area-responsible {
            font-size: 11px;
            margin-top: 3px;
            color: #374151;
        }

        /* ================= VOLUNTÁRIOS ================= */
        .volunteers {
            width: 100%;
            border-collapse: collapse;
        }

        .volunteers td {
            padding: 7px 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .volunteer-name {
            width: 70%;
        }

        .status {
            width: 30%;
            text-align: right;
            font-size: 11px;
            font-weight: bold;
        }

        .status-confirmed {
            color: #15803d;
        }

        .status-pending {
            color: #ca8a04;
        }

        .status-refused {
            color: #b91c1c;
        }

        .empty-area {
            padding: 12px;
            font-size: 11px;
            font-style: italic;
            color: #6b7280;
        }

        /* ================= OBSERVAÇÕES ================= */
        .notes-section {
            margin-top: 22px;
            padding: 14px;
            border-left: 4px solid #1e3a8a;
            background-color: #f8fafc;
        }

        .notes-title {
            font-weight: bold;
            font-size: 12px;
            color: #1e3a8a;
            margin-bottom: 6px;
        }

        .notes-content {
            font-size: 11px;
            color: #374151;
        }

        /* ================= FOOTER ================= */
        .footer {
            margin-top: 26px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>

<body>
<div class="page">

    <!-- HEADER -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo">
                    @if(isset($logoBase64) && $logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo">
                    @elseif($logoPath && file_exists($logoPath))
                        <img src="file://{{ str_replace(['\\', ' '], ['/', '%20'], $logoPath) }}" alt="Logo">
                    @endif
                </td>
                <td class="church-info {{ !($logoPath && file_exists($logoPath)) ? 'no-logo' : '' }}">
                    <div class="church-name">ADEL - São Sebastião</div>
                    <div class="title">Escala de Serviço</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- INFO -->
    <div class="info">
        <table class="info-table">
            <tr>
                <td class="label">Culto:</td>
                <td class="value">{{ $escala->title }}</td>
                <td class="label">Data:</td>
                <td class="value">{{ $escala->date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Horário:</td>
                <td class="value">{{ \Carbon\Carbon::parse($escala->start_time)->format('H:i') }}</td>
                <td class="label">Local:</td>
                <td class="value">{{ $escala->location ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Status:</td>
                <td class="value">{{ ucfirst($escala->status) }}</td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>

    <!-- ÁREAS -->
    @foreach($escala->areas as $area)
        <div class="area">
            <div class="area-header">
                <div class="area-title">{{ $area->serviceArea->name }}</div>
                <div class="area-responsible">
                    Responsável: {{ $area->responsible ? $area->responsible->name : '—' }}
                </div>
            </div>

            @if($area->volunteers->count() > 0)
                <table class="volunteers">
                    @foreach($area->volunteers as $volunteerSchedule)
                        <tr>
                            <td class="volunteer-name">
                              Voluntário Escalado: {{ $volunteerSchedule->volunteer->member->name }}
                            </td>
                            <td class="status
                                @if($volunteerSchedule->status == 'confirmado') status-confirmed
                                @elseif($volunteerSchedule->status == 'pendente') status-pending
                                @elseif($volunteerSchedule->status == 'cancelado') status-refused
                                @endif
                            ">
                                @if($volunteerSchedule->status == 'confirmado')
                                    ✔ Confirmado
                                @elseif($volunteerSchedule->status == 'pendente')
                                    ⏳ Pendente
                                @elseif($volunteerSchedule->status == 'cancelado')
                                    ✖ Cancelado
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            @else
                <div class="empty-area">
                    Nenhum voluntário atribuído
                </div>
            @endif
        </div>
    @endforeach

    <!-- OBSERVAÇÕES -->
    @if($escala->notes)
        <div class="notes-section">
            <div class="notes-title">Observações</div>
            <div class="notes-content">{{ $escala->notes }}</div>
        </div>
    @endif

    <!-- FOOTER -->
    <div class="footer">
        Gerado em {{ $generatedAt->format('d/m/Y H:i') }} · Sistema ADELSS
    </div>

</div>
</body>
</html>
