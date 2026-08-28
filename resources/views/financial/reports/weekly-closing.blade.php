@extends('layouts.porto')

@section('title', 'Fechamento Semanal de Caixa')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.reports.index') }}">Relatórios</a></li>
    <li><span>Fechamento Semanal</span></li>
@endsection

@section('content')
<div class="row fr-page">
    @include('financial.reports.partials.sidebar')

    <div class="col-12 col-lg-9 fr-main">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card fr-card fr-filters-card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('financial.reports.weekly-closing') }}" class="fr-filters" id="weekForm">
                    <div>
                        <label class="form-label" for="week_date">Semana (segunda a domingo)</label>
                        <input type="date" class="form-control" id="week_date" name="week_date" value="{{ $weekStart->toDateString() }}" onchange="this.form.submit()">
                        <small class="text-muted">{{ $weekStart->format('d/m/Y') }} a {{ $weekEnd->format('d/m/Y') }}</small>
                    </div>
                </form>
            </div>
        </div>

        @if($diverged && $live['latest_closing'])
            <div class="alert alert-warning">
                Os lançamentos desta semana foram alterados desde o último fechamento gerado em
                {{ $live['latest_closing']->generated_at?->format('d/m/Y H:i') }}.
                Total no fechamento: R$ {{ number_format((float) $live['latest_closing']->saldo, 2, ',', '.') }}
                | Total atual: R$ {{ number_format((float) $live['saldo'], 2, ',', '.') }}.
                Considere gerar um novo fechamento.
            </div>
        @endif

        <div class="card fr-card mb-4">
            <div class="card-body">
                @include('financial.reports.partials.report-head', [
                    'title' => 'Fechamento semanal de caixa',
                    'subtitle' => $weekStart->format('d/m/Y').' a '.$weekEnd->format('d/m/Y'),
                    'pdfUrl' => $live['latest_closing'] ? route('financial.reports.weekly-closing.pdf', $live['latest_closing']) : null,
                    'pdfLabel' => 'PDF do último fechamento',
                ])

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="small text-muted">Receitas recebidas</div>
                            <strong class="text-success">R$ {{ number_format($live['total_receitas'], 2, ',', '.') }}</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="small text-muted">Despesas pagas</div>
                            <strong class="text-danger">R$ {{ number_format($live['total_despesas'], 2, ',', '.') }}</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="small text-muted">Saldo</div>
                            <strong>R$ {{ number_format($live['saldo'], 2, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>

                @if($canGenerate)
                    <form method="POST" action="{{ route('financial.reports.weekly-closing.generate') }}" class="mb-4">
                        @csrf
                        <input type="hidden" name="week_date" value="{{ $weekStart->toDateString() }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-lock-alt me-1"></i>Gerar fechamento
                        </button>
                    </form>
                @endif

                <h6>Receitas por categoria</h6>
                <table class="table table-sm">
                    @foreach($live['receitas_por_categoria'] as $row)
                        <tr>
                            <td>{{ $row['name'] }} ({{ $row['count'] }})</td>
                            <td class="text-end">R$ {{ number_format($row['total'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </table>

                <h6 class="mt-4">Despesas por categoria</h6>
                <table class="table table-sm">
                    @foreach($live['despesas_por_categoria'] as $row)
                        <tr>
                            <td>{{ $row['name'] }} ({{ $row['count'] }})</td>
                            <td class="text-end">R$ {{ number_format($row['total'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
