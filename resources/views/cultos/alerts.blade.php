@extends('layouts.porto')

@section('title', 'Alertas — Relatórios de Culto')
@section('page-title', 'Alertas')

@section('breadcrumbs')
    <li><a href="{{ route('cultos.index') }}">Relatórios de Culto</a></li>
    <li><span>Alertas</span></li>
@endsection

@section('content')
@include('cultos.partials.module-nav', ['active' => 'alerts'])

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h5 class="mb-3">Alertas Pastorais</h5>
        <p class="text-muted">
            Os alertas são verificados automaticamente todos os dias às 09:00 pelo comando
            <code>cultos:check-pastoral-alerts</code>.
        </p>
        <ul class="mb-0">
            <li>
                Ausência consecutiva:
                <strong>{{ $settings->consecutive_absences_alert_threshold ?: 'desativado' }}</strong>
                culto(s)
            </li>
            <li>
                Visitante sem retorno:
                <strong>{{ $settings->visitor_no_return_days_alert ?: 'desativado' }}</strong>
                dia(s)
            </li>
        </ul>
        <p class="small text-muted mt-3 mb-0">
            As notificações são enviadas via WhatsApp para membros com cargo de Pastor, Discipulador ou Líder.
            Ajuste os limiares em Configurações.
        </p>
    </div>
</div>
@endsection
