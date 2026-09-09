@extends('layouts.porto')

@section('title', 'Demonstrativo financeiro — Matriz')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.reports.index') }}">Relatórios</a></li>
    <li><span>Demonstrativo da matriz</span></li>
@endsection

@section('content')
@php
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
@endphp
<div class="row fr-page">
    @include('financial.reports.partials.sidebar')

    <div class="col-12 col-lg-9 fr-main">
        @include('financial.reports.partials.year-filters', [
            'action' => route('financial.reports.matrix-demonstrativo'),
        ])

        <div class="card fr-card mb-4">
            <div class="card-body">
                @include('financial.reports.partials.report-head', [
                    'title' => 'Demonstrativo financeiro — Igreja matriz',
                    'subtitle' => 'Ano '.$year.' · 12 páginas, uma para cada mês, com assinaturas',
                    'pdfUrl' => route('financial.reports.matrix-demonstrativo.pdf', ['year' => $year]),
                    'pdfLabel' => 'Gerar PDF anual',
                ])

                <p class="text-muted small mb-4">
                    Entradas: 37% dízimos dos obreiros e 63% dízimos dos membros e congregados, sobre a renda mensal.
                    Saídas: uma linha com o total do mês. O PDF segue o modelo enviado à sede.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Mês</th>
                                <th class="text-end text-success">Obreiros</th>
                                <th class="text-end text-success">Membros</th>
                                <th class="text-end text-success">Entradas</th>
                                <th class="text-end text-danger">Saídas</th>
                                <th class="text-end">Saldo anterior</th>
                                <th class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($months as $mes)
                                <tr>
                                    <td>{{ $mes['month_name'] }}</td>
                                    <td class="text-end text-success">{{ $fmt($mes['dizimo_obreiros']) }}</td>
                                    <td class="text-end text-success">{{ $fmt($mes['dizimo_membros']) }}</td>
                                    <td class="text-end text-success">{{ $fmt($mes['total_entradas']) }}</td>
                                    <td class="text-end text-danger">{{ $fmt($mes['total_saidas']) }}</td>
                                    <td class="text-end">{{ $fmt($mes['saldo_anterior']) }}</td>
                                    <td class="text-end fw-semibold">{{ $fmt($mes['saldo_final']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
