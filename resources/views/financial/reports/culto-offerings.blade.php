@extends('layouts.porto')

@section('title', 'Dízimos e Ofertas por Culto')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.reports.index') }}">Relatórios</a></li>
    <li><span>Dízimos e Ofertas</span></li>
@endsection

@section('content')
<div class="row fr-page">
    @include('financial.reports.partials.sidebar')

    <div class="col-12 col-lg-9 fr-main">
        <div class="card fr-card fr-filters-card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('financial.reports.cultos') }}" class="fr-filters">
                    <div class="fr-filters__wide">
                        <label class="form-label" for="culto_id">Culto</label>
                        <select class="form-select" id="culto_id" name="culto_id" onchange="this.form.submit()">
                            <option value="">Selecione um culto</option>
                            @foreach($cultos as $item)
                                <option value="{{ $item->id }}" @selected($culto?->id === $item->id)>{{ $item->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>

        @if($report)
            <div class="card fr-card">
                <div class="card-body">
                    @include('financial.reports.partials.report-head', [
                        'title' => 'Dízimos e ofertas — '.$culto->display_name,
                        'subtitle' => 'Somente valores recebidos deste culto',
                        'pdfUrl' => route('financial.reports.cultos.pdf', $culto),
                        'pdfLabel' => 'Baixar PDF',
                    ])

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="small text-muted">Dízimos</div>
                                <strong>R$ {{ number_format($report['totalDizimos'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="small text-muted">Ofertas</div>
                                <strong>R$ {{ number_format($report['totalOfertas'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="small text-muted">Total geral</div>
                                <strong>R$ {{ number_format($report['totalGeral'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Recebido de</th>
                                    <th>Categoria</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report['lancamentos'] as $tx)
                                    <tr>
                                        <td>{{ $tx->source_name }}</td>
                                        <td>{{ $tx->category?->name }}</td>
                                        <td class="text-end">R$ {{ number_format((float) $tx->amount, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted">Nenhum dízimo ou oferta recebido neste culto.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="card fr-card">
                <div class="card-body text-muted">Nenhum culto da Agenda encontrado. Cadastre o culto em Agenda para gerar este relatório.</div>
            </div>
        @endif
    </div>
</div>
@endsection
