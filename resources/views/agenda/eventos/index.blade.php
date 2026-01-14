@extends('layouts.porto')

@section('title', 'Eventos')

@section('page-title', 'Eventos')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Visão Geral</a></li>
    <li><span>Agenda</span></li>
    <li><span>Eventos</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">Eventos</h2>
            </header>
            <div class="card-body">
                <p class="text-muted">Módulo de Eventos em desenvolvimento.</p>
            </div>
        </section>
    </div>
</div>
@endsection


