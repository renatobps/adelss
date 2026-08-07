@extends('layouts.porto')

@section('title', 'Nova Campanha')

@section('page-title', 'Nova Campanha')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.campaigns.index') }}">Campanhas</a></li>
    <li><span>Nova</span></li>
@endsection

@section('content')
@include('financial.campaigns.partials.alerts')

<section class="card">
    <header class="card-header">
        <h5 class="mb-0"><i class="bx bx-donate-heart me-2"></i>Nova Campanha</h5>
    </header>
    <div class="card-body">
        <form method="POST" action="{{ route('financial.campaigns.store') }}">
            @csrf
            @include('financial.campaigns.partials.form')
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('financial.campaigns.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Criar Campanha</button>
            </div>
        </form>
    </div>
</section>
@endsection
