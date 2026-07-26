@extends('layouts.porto')

@section('title', 'Editar Relatório de Culto')
@section('page-title', 'Editar Relatório')

@section('breadcrumbs')
    <li><a href="{{ route('cultos.index') }}">Relatórios de Culto</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Editar Relatório de Culto</h5>
    </div>
    <div class="card-body">
        @include('cultos.partials.wizard', [
            'report' => $report,
            'settings' => $settings,
            'members' => $members,
            'events' => $events,
            'types' => $types,
            'asModal' => false,
        ])
    </div>
</div>
@endsection
