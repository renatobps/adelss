<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Escala Moriah - {{ $moriahSchedule->title ?? 'N/A' }}</title>

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

        /* ================= SEÇÕES ================= */
        .section {
            margin-bottom: 22px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            page-break-inside: avoid;
        }

        .section-header {
            background-color: #e0e7ff;
            padding: 10px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        /* ================= MEMBROS ================= */
        .members {
            width: 100%;
            border-collapse: collapse;
        }

        .members td {
            padding: 7px 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .member-name {
            width: 50%;
            font-weight: 500;
        }

        .member-functions {
            width: 30%;
            font-size: 11px;
            color: #6b7280;
        }

        .status {
            width: 20%;
            text-align: right;
            font-size: 11px;
            font-weight: bold;
        }

        .status-confirmed {
            color: #15803d;
        }

        .status-pending {
            color: #d97706;
        }

        .status-rejected {
            color: #dc2626;
        }

        .status-canceled {
            color: #6b7280;
        }

        /* ================= MÚSICAS ================= */
        .songs {
            width: 100%;
            border-collapse: collapse;
        }

        .songs td {
            padding: 7px 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .song-order {
            width: 30px;
            font-weight: bold;
            color: #1e3a8a;
        }

        .song-title {
            width: 60%;
            font-weight: 500;
        }

        .song-artist {
            width: 40%;
            font-size: 11px;
            color: #6b7280;
        }

        /* ================= FOOTER ================= */
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
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
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}" alt="Logo">
                        @endif
                    </td>
                    <td class="church-info {{ !$logoBase64 ? 'no-logo' : '' }}">
                        <div class="church-name">{{ $churchName }}</div>
                        <div class="title">Escala Moriah</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- INFO -->
        <div class="info">
            <table class="info-table">
                <tr>
                    <td class="label">Título:</td>
                    <td class="value">{{ $moriahSchedule->title }}</td>
                </tr>
                @if($moriahSchedule->event)
                <tr>
                    <td class="label">Culto:</td>
                    <td class="value">{{ $moriahSchedule->event->title }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Data:</td>
                    <td class="value">{{ \Carbon\Carbon::parse($moriahSchedule->date)->format('d/m/Y') }}</td>
                </tr>
                @if($moriahSchedule->time)
                <tr>
                    <td class="label">Horário:</td>
                    <td class="value">{{ \Carbon\Carbon::parse($moriahSchedule->time)->format('H:i') }}</td>
                </tr>
                @endif
                @if($moriahSchedule->observations)
                <tr>
                    <td class="label">Observações:</td>
                    <td class="value">{{ $moriahSchedule->observations }}</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- PARTICIPANTES -->
        @if($moriahSchedule->members->count() > 0)
        <div class="section">
            <div class="section-header">
                <div class="section-title">Participantes ({{ $moriahSchedule->members->count() }})</div>
            </div>
            <table class="members">
                @foreach($moriahSchedule->members as $member)
                    @php
                        $memberStatus = $membersWithStatus[$member->id] ?? 'pendente';
                        $functions = $selectedMemberFunctions[$member->id] ?? [];
                    @endphp
                    <tr>
                        <td class="member-name">{{ $member->name }}</td>
                        <td class="member-functions">
                            @if(count($functions) > 0)
                                {{ implode(', ', $functions) }}
                            @else
                                <span style="color: #9ca3af;">Sem função definida</span>
                            @endif
                        </td>
                        <td class="status status-{{ $memberStatus }}">
                            @if($memberStatus === 'confirmado')
                                ✓ Confirmado
                            @elseif($memberStatus === 'recusado')
                                ✗ Recusado
                            @elseif($memberStatus === 'cancelado')
                                ⊗ Cancelado
                            @else
                                ⏳ Pendente
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
        @endif

        <!-- MÚSICAS -->
        @if($moriahSchedule->songs->count() > 0)
        <div class="section">
            <div class="section-header">
                <div class="section-title">Repertório ({{ $moriahSchedule->songs->count() }})</div>
            </div>
            <table class="songs">
                @foreach($songsWithOrder as $index => $songData)
                    @php
                        $song = $songData['song'];
                        $order = $index + 1;
                    @endphp
                    <tr>
                        <td class="song-order">{{ $order }}.</td>
                        <td class="song-title">{{ $song->version_name ?? $song->title }}</td>
                        <td class="song-artist">{{ $song->artist ?? 'Artista não informado' }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
        @endif

        <!-- FOOTER -->
        <div class="footer">
            Documento gerado em {{ $generatedAt->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
