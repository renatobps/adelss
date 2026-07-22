@extends('layouts.porto')

@section('title', 'Configurações — Relatórios de Culto')
@section('page-title', 'Configurações')

@section('breadcrumbs')
    <li><a href="{{ route('cultos.index') }}">Relatórios de Culto</a></li>
    <li><span>Configurações</span></li>
@endsection

@section('content')
@include('cultos.partials.module-nav', ['active' => 'settings'])

<form method="POST" action="{{ route('cultos.settings.update') }}">
    @csrf
    @method('PUT')

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h5 class="mb-3">Modo do Relatório</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="cultos-mode-card {{ $settings->mode === 'detalhado' ? 'is-active' : '' }}">
                        <input type="radio" name="mode" value="detalhado" class="d-none" @checked($settings->mode === 'detalhado')>
                        <strong>Detalhado</strong>
                        <div class="small text-muted mt-1">Chamada individual, visitantes com formulário e fotos.</div>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="cultos-mode-card {{ $settings->mode === 'simples' ? 'is-active' : '' }}">
                        <input type="radio" name="mode" value="simples" class="d-none" @checked($settings->mode === 'simples')>
                        <strong>Simples</strong>
                        <div class="small text-muted mt-1">Apenas quantidades — ideal para igrejas que não precisam de chamada individual.</div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h5 class="mb-3">Alertas Pastorais</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Cultos consecutivos para alerta de ausência</label>
                    <input type="number" min="1" max="30" class="form-control" name="consecutive_absences_alert_threshold"
                           value="{{ $settings->consecutive_absences_alert_threshold }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Dias sem retorno para alerta de visitante</label>
                    <input type="number" min="1" max="365" class="form-control" name="visitor_no_return_days_alert"
                           value="{{ $settings->visitor_no_return_days_alert }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h5 class="mb-3">Visitantes</h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="auto_register_visitor_as_member"
                       name="auto_register_visitor_as_member" value="1" @checked($settings->auto_register_visitor_as_member)>
                <label class="form-check-label" for="auto_register_visitor_as_member">
                    Cadastrar visitante automaticamente
                </label>
            </div>
            <div class="form-text">Cria um registro na Membresia com status "visitante" ao salvar o relatório.</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h5 class="mb-3">Campos Opcionais</h5>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="enable_children_count"
                       name="enable_children_count" value="1" @checked($settings->enable_children_count)>
                <label class="form-check-label" for="enable_children_count">Registrar crianças</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="enable_volunteers_count"
                       name="enable_volunteers_count" value="1" @checked($settings->enable_volunteers_count)>
                <label class="form-check-label" for="enable_volunteers_count">Registrar voluntários/servindo</label>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h5 class="mb-3">Manifestações Espirituais</h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="enable_spiritual_decisions"
                       name="enable_spiritual_decisions" value="1" @checked($settings->enable_spiritual_decisions)>
                <label class="form-check-label" for="enable_spiritual_decisions">
                    Habilitar registro de batismos no Espírito, curas e decisões por Cristo
                </label>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save"></i> Salvar Configurações
        </button>
    </div>
</form>
@endsection

@push('styles')
<style>
.cultos-mode-card {
    display:block; border:1px solid #e5e7eb; border-radius:.85rem; padding:1rem; cursor:pointer; background:#fff;
}
.cultos-mode-card.is-active { border-color:#93c5fd; background:#eff6ff; box-shadow:0 0 0 1px rgba(59,130,246,.2); }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.cultos-mode-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.cultos-mode-card').forEach(c => c.classList.remove('is-active'));
        card.classList.add('is-active');
        card.querySelector('input[type=radio]').checked = true;
    });
});
</script>
@endpush
