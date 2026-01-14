@extends('layouts.porto')

@section('title', 'Dashboard')

@section('page-title', 'Visão Geral')

@section('breadcrumbs')
    <li><span>Dashboard</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">Bem-vindo ao ADELSS Sistema Web</h2>
                <p class="card-subtitle">Sistema de gestão e administração</p>
            </header>
            <div class="card-body">

<div class="row mt-4">
    <div class="col-md-3">
        <section class="card card-featured-left card-featured-primary mb-3">
            <div class="card-body">
                <div class="widget-summary">
                    <div class="widget-summary-col widget-summary-col-icon">
                        <div class="summary-icon bg-primary">
                            <i class="bx bx-user"></i>
                        </div>
                    </div>
                    <div class="widget-summary-col">
                        <div class="summary">
                            <h4 class="title">Membros</h4>
                            <div class="info">
                                <strong class="amount">@if(class_exists('App\Models\Member')){{ \App\Models\Member::count() }}@else 0 @endif</strong>
                            </div>
                        </div>
                        <div class="summary-footer">
                            <a class="text-muted text-uppercase" href="{{ route('members.index') }}">(ver todos)</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="col-md-3">
        <section class="card card-featured-left card-featured-success mb-3">
            <div class="card-body">
                <div class="widget-summary">
                    <div class="widget-summary-col widget-summary-col-icon">
                        <div class="summary-icon bg-success">
                            <i class="bx bx-check-circle"></i>
                        </div>
                    </div>
                    <div class="widget-summary-col">
                        <div class="summary">
                            <h4 class="title">Ativos</h4>
                            <div class="info">
                                <strong class="amount">@if(class_exists('App\Models\Member')){{ \App\Models\Member::active()->count() }}@else 0 @endif</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="col-md-3">
        <section class="card card-featured-left card-featured-info mb-3">
            <div class="card-body">
                <div class="widget-summary">
                    <div class="widget-summary-col widget-summary-col-icon">
                        <div class="summary-icon bg-info">
                            <i class="bx bx-building"></i>
                        </div>
                    </div>
                    <div class="widget-summary-col">
                        <div class="summary">
                            <h4 class="title">Departamentos</h4>
                            <div class="info">
                                <strong class="amount">@if(class_exists('App\Models\Department')){{ \App\Models\Department::active()->count() }}@else 0 @endif</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="col-md-3">
        <section class="card card-featured-left card-featured-warning mb-3">
            <div class="card-body">
                <div class="widget-summary">
                    <div class="widget-summary-col widget-summary-col-icon">
                        <div class="summary-icon bg-warning">
                            <i class="bx bx-group"></i>
                        </div>
                    </div>
                    <div class="widget-summary-col">
                        <div class="summary">
                            <h4 class="title">PGIs</h4>
                            <div class="info">
                                <strong class="amount">@if(class_exists('App\Models\Pgi')){{ \App\Models\Pgi::count() }}@else 0 @endif</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
            </div>
        </section>
    </div>
</div>
@endsection

