<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Escala Mensal de Culto' }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.5;
            background: #ffffff;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
        }

        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: #ffffff;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .logo-container {
            margin-bottom: 15px;
        }

        .logo-container img {
            max-height: 80px;
            max-width: 200px;
            object-fit: contain;
        }

        .church-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .document-title {
            font-size: 18px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 10px;
        }

        .content {
            padding: 25px;
        }

        .event-info {
            background: #f8fafc;
            border-left: 4px solid #1e3a8a;
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 4px;
        }

        .event-info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 8px;
        }

        .event-info-row:last-child {
            margin-bottom: 0;
        }

        .event-info-label {
            font-weight: bold;
            color: #1e3a8a;
            min-width: 80px;
        }

        .event-info-value {
            color: #111827;
            flex: 1;
        }

        .ministries-section {
            margin-top: 30px;
        }

        .ministry-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            margin-bottom: 20px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .ministry-header {
            background: #1e3a8a;
            color: #ffffff;
            padding: 12px 15px;
            font-weight: bold;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ministry-icon {
            font-size: 18px;
        }

        .ministry-body {
            padding: 15px;
        }

        .volunteer-list {
            list-style: none;
        }

        .volunteer-item {
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
        }

        .volunteer-item:last-child {
            border-bottom: none;
        }

        .volunteer-item::before {
            content: "•";
            color: #1e3a8a;
            font-weight: bold;
            margin-right: 10px;
            font-size: 16px;
        }

        .volunteer-name {
            color: #111827;
            font-size: 11px;
        }

        .empty-ministry {
            color: #6b7280;
            font-style: italic;
            padding: 15px 0;
            text-align: center;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }

        .footer-info {
            margin-bottom: 5px;
        }

        @media print {
            .ministry-card {
                page-break-inside: avoid;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="header">
        @if(isset($logoPath) && file_exists($logoPath))
            <div class="logo-container">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo">
            </div>
        @endif
        <div class="church-name">{{ $churchName ?? 'ADELSS' }}</div>
        <div class="document-title">Escala Mensal de Culto</div>
    </div>

    <div class="content">
        @yield('content')
    </div>

    <div class="footer">
        <div class="footer-info">Documento gerado em {{ now()->format('d/m/Y H:i') }}</div>
        <div class="footer-info">{{ $churchName ?? 'ADELSS' }} - Sistema de Gestão</div>
    </div>
</body>
</html>
