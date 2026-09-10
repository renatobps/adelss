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
                    'subtitle' => 'Ano '.$year.' · uma folha por mês, com recibos das saídas (2 por página) em anexo',
                    'pdfUrl' => route('financial.reports.matrix-demonstrativo.pdf', ['year' => $year]),
                    'pdfLabel' => 'Gerar PDF anual',
                ])

                <p class="text-muted small mb-4">
                    Entradas: 37% dízimos dos obreiros e 63% dízimos dos membros e congregados, sobre a renda mensal.
                    Saídas: totais por tipo de despesa paga no mês. O PDF anexa os recibos de pagamento, no nome da tesoureira, logo após cada mês.
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

                <h2 class="h6 mt-4 mb-3">Discriminação das saídas</h2>
                @php
                    $mesesComSaidas = collect($months)->filter(fn ($mes) => count($mes['saidas']) > 0);
                @endphp
                @forelse($mesesComSaidas as $mes)
                    <div class="mb-3">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                            <strong>{{ $mes['month_name'] }}</strong>
                            <span class="text-danger fw-semibold">{{ $fmt($mes['total_saidas']) }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th class="text-end" style="width: 8rem;">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mes['saidas'] as $saida)
                                        <tr>
                                            <td>{{ $saida['description'] }}</td>
                                            <td class="text-end text-danger">{{ $fmt($saida['amount']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Nenhuma saída paga neste ano.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
