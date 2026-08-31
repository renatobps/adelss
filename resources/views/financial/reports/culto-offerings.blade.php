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
                        'title' => 'Movimento do dia — '.$culto->display_name,
                        'subtitle' => $culto->start_date
                            ? 'Entradas e saídas pagas em '.$culto->start_date->format('d/m/Y')
                            : 'Entradas e saídas do dia do culto',
                        'pdfUrl' => route('financial.reports.cultos.pdf', $culto),
                        'pdfLabel' => 'Baixar PDF',
                    ])

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="small text-muted">Entradas</div>
                                <strong class="text-primary">R$ {{ number_format($report['totalEntradas'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="small text-muted">Saídas</div>
                                <strong class="text-danger">R$ {{ number_format($report['totalSaidas'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="small text-muted">Saldo do dia</div>
                                <strong>R$ {{ number_format($report['saldoDia'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-primary text-uppercase mb-2">Entradas (receitas)</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Recebido de</th>
                                    <th>Categoria</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report['entradas'] as $tx)
                                    <tr>
                                        <td>{{ $tx->transaction_date?->format('d/m/Y') }}</td>
                                        <td>{{ $tx->description }}</td>
                                        <td>{{ $tx->source_name }}</td>
                                        <td>{{ $tx->category?->name }}</td>
                                        <td class="text-end text-primary">R$ {{ number_format((float) $tx->amount, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">Nenhuma entrada paga neste dia.</td></tr>
                                @endforelse
                                <tr class="fw-semibold">
                                    <td colspan="4">Total de entradas</td>
                                    <td class="text-end text-primary">R$ {{ number_format($report['totalEntradas'], 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="text-danger text-uppercase mb-2">Saídas (despesas)</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Pago à</th>
                                    <th>Categoria</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report['saidas'] as $tx)
                                    <tr>
                                        <td>{{ $tx->transaction_date?->format('d/m/Y') }}</td>
                                        <td>{{ $tx->description }}</td>
                                        <td>{{ $tx->source_name }}</td>
                                        <td>{{ $tx->category?->name }}</td>
                                        <td class="text-end text-danger">R$ {{ number_format((float) $tx->amount, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">Nenhuma saída paga neste dia.</td></tr>
                                @endforelse
                                <tr class="fw-semibold">
                                    <td colspan="4">Total de saídas</td>
                                    <td class="text-end text-danger">R$ {{ number_format($report['totalSaidas'], 2, ',', '.') }}</td>
                                </tr>
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
        @else
            <div class="card fr-card">
                <div class="card-body text-muted">Nenhum culto da Agenda encontrado. Cadastre o culto em Agenda para gerar este relatório.</div>
            </div>
        @endif
    </div>
</div>
@endsection
