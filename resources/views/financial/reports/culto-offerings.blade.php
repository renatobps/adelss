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

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @if($report)
            <div class="card fr-card">
                <div class="card-body">
                    @include('financial.reports.partials.report-head', [
                        'title' => 'Dízimos e ofertas — '.$culto->display_name,
                        'subtitle' => 'Valores vinculados a este culto no fechamento',
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
                                    <th>Data</th>
                                    <th>Recebido de</th>
                                    <th>Categoria</th>
                                    <th class="text-end">Valor</th>
                                    @if($canGenerate)
                                        <th></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report['lancamentos'] as $tx)
                                    <tr>
                                        <td>{{ $tx->transaction_date?->format('d/m/Y') }}</td>
                                        <td>{{ $tx->source_name }}</td>
                                        <td>{{ $tx->category?->name }}</td>
                                        <td class="text-end">R$ {{ number_format((float) $tx->amount, 2, ',', '.') }}</td>
                                        @if($canGenerate)
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('financial.reports.cultos.detach', [$culto, $tx]) }}" class="d-inline"
                                                      onsubmit="return confirm('Desvincular este lançamento deste culto?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Desvincular</button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $canGenerate ? 5 : 4 }}" class="text-muted">Nenhum dízimo ou oferta vinculado a este culto.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($canGenerate)
                <div class="card fr-card mt-4">
                    <div class="card-body">
                        <h5 class="mb-1">Vincular dízimos e ofertas</h5>
                        <p class="text-muted small mb-3">
                            Lançamentos sem culto (recebidos em qualquer dia). Marque os que entram neste fechamento.
                        </p>
                        @if($report['pendentes']->isEmpty())
                            <p class="text-muted mb-0">Não há dízimos ou ofertas pendentes de vínculo.</p>
                        @else
                            <form method="POST" action="{{ route('financial.reports.cultos.attach', $culto) }}">
                                @csrf
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 2rem;"></th>
                                                <th>Data</th>
                                                <th>Recebido de</th>
                                                <th>Categoria</th>
                                                <th class="text-end">Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($report['pendentes'] as $tx)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="form-check-input" name="ids[]" value="{{ $tx->id }}">
                                                    </td>
                                                    <td>{{ $tx->transaction_date?->format('d/m/Y') }}</td>
                                                    <td>{{ $tx->source_name }}</td>
                                                    <td>{{ $tx->category?->name }}</td>
                                                    <td class="text-end">R$ {{ number_format((float) $tx->amount, 2, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    Vincular ao culto selecionado
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
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
