<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle ?? 'Escala' }}</title>
    <style>
        @page { margin: 0; size: A4 portrait; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 0; }
        .page { padding: 26px; }
        .culto-block { page-break-after: always; }
        .culto-block:last-child { page-break-after: auto; }
        .header { background-color: #1e3a8a; color: #ffffff; padding: 18px; margin-bottom: 18px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .logo { width: 90px; }
        .logo img { max-width: 90px; max-height: 90px; }
        .church-info { text-align: right; }
        .church-name { font-size: 20px; font-weight: bold; }
        .title { font-size: 13px; margin-top: 4px; text-transform: uppercase; opacity: 0.9; }
        .info { background-color: #f1f5f9; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 6px; }
        .label { font-weight: bold; color: #1e3a8a; width: 80px; }
        .area { margin-bottom: 22px; border: 1px solid #e5e7eb; border-radius: 4px; page-break-inside: avoid; }
        .area-header { background-color: #e0e7ff; padding: 10px; }
        .area-title { font-size: 13px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; }
        .volunteers { width: 100%; border-collapse: collapse; }
        .volunteers td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; }
        .status { width: 30%; text-align: right; font-size: 11px; font-weight: bold; }
        .status-confirmed { color: #15803d; }
        .status-pending { color: #ca8a04; }
        .footer { margin-top: 26px; padding-top: 10px; border-top: 1px solid #d1d5db; text-align: center; font-size: 10px; color: #6b7280; }
        .period-title { font-size: 13px; font-weight: bold; color: #1e3a8a; margin: 12px 10px 6px; }
    </style>
</head>
<body>
@foreach($escalas as $escala)
<div class="page culto-block">
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Logo">
                    @endif
                </td>
                <td class="church-info">
                    <div class="church-name">{{ $churchName ?? 'ADELSS' }}</div>
                    <div class="title">{{ $documentTitle ?? 'Escala' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info">
        <table class="info-table">
            <tr>
                <td class="label">Culto:</td>
                <td>{{ $escala->event->title ?? 'N/A' }}</td>
                <td class="label">Data:</td>
                <td>{{ optional($escala->event?->start_date)->format('d/m/Y') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Horário:</td>
                <td>{{ optional($escala->event?->start_date)->format('H:i') ?? 'N/A' }}</td>
                <td class="label">Mês/Ano:</td>
                <td>{{ \Carbon\Carbon::create($escala->year, $escala->month, 1)->locale('pt_BR')->translatedFormat('F/Y') }}</td>
            </tr>
        </table>
    </div>

    @php $volunteersByArea = $volunteersBySchedule[$escala->id] ?? []; @endphp
    @foreach($serviceAreas as $area)
        @continue($area->parent_id)
        @php
            $childAreas = $serviceAreas->where('parent_id', $area->id)->sortBy([['sort_order', 'asc'], ['name', 'asc']]);
            $sections = $childAreas->isNotEmpty() ? $childAreas : collect([$area]);
        @endphp
        @foreach($sections as $section)
            @php
                $volunteers = $volunteersByArea[$section->id] ?? collect();
                $guestPreletorName = $escala->guestPreletorNameForArea($section);
            @endphp
            @if($volunteers->count() > 0 || $guestPreletorName)
                <div class="area">
                    <div class="area-header">
                        <div class="area-title">{{ $childAreas->isNotEmpty() ? $area->name.' — '.$section->name : $section->name }}</div>
                    </div>
                    @if($section->isIntercession())
                        @include('pdf.escalas.partials.intercessao', ['section' => $section, 'volunteers' => $volunteers])
                    @else
                    <table class="volunteers">
                        @if($guestPreletorName)
                            <tr>
                                <td>{{ $guestPreletorName }}</td>
                                <td class="status status-confirmed">Convidado</td>
                            </tr>
                        @endif
                        @foreach($volunteers as $volunteer)
                            @php $pivotStatus = $volunteer->pivot->status ?? 'pendente'; @endphp
                            <tr>
                                <td>{{ $volunteer->member->name ?? 'Sem nome' }}</td>
                                <td class="status {{ $pivotStatus === 'confirmado' ? 'status-confirmed' : 'status-pending' }}">
                                    {{ $pivotStatus === 'confirmado' ? 'Confirmado' : 'Pendente' }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    @endif
                </div>
            @endif
        @endforeach
    @endforeach

    <div class="footer">Gerado em {{ $generatedAt->format('d/m/Y H:i') }} · Sistema ADELSS</div>
</div>
@endforeach
</body>
</html>
