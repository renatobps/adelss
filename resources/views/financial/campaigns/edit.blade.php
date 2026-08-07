@extends('layouts.porto')

@section('title', 'Editar Campanha')

@section('page-title', 'Editar Campanha')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.campaigns.index') }}">Campanhas</a></li>
    <li><a href="{{ route('financial.campaigns.show', $campaign) }}">{{ $campaign->name }}</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
@include('financial.campaigns.partials.alerts')

<section class="card">
    <header class="card-header">
        <h5 class="mb-0"><i class="bx bx-edit me-2"></i>Editar Campanha</h5>
    </header>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="bx bx-info-circle me-1"></i>
            Alterações de valor e quantidade de parcelas valem apenas para <strong>novos patrocinadores</strong> — parcelas já geradas não são alteradas.
        </div>
        <form method="POST" action="{{ route('financial.campaigns.update', $campaign) }}">
            @csrf
            @method('PUT')
            @include('financial.campaigns.partials.form')
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('financial.campaigns.show', $campaign) }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Salvar Alterações</button>
            </div>
        </form>
    </div>
</section>
@endsection
