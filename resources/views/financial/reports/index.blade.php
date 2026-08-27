@extends('layouts.porto')

@section('title', 'Relatórios Financeiros')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Relatórios</span></li>
@endsection

@section('content')
<div class="row fr-page">
    @include('financial.reports.partials.sidebar')

    <div class="col-12 col-lg-9 fr-main">
        <div class="card fr-card" style="min-height: 280px;">
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center text-muted py-5">
                <p class="mb-0">Selecione um relatório no menu ao lado. No celular, toque em <strong>Relatórios</strong> para abrir a lista.</p>
            </div>
        </div>
    </div>
</div>
@endsection
