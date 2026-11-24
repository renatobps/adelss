@extends('layouts.porto')
@section('title', 'Notificações — '.$event->title)
@section('page-title', 'Participantes e notificações')
@section('content')
@php
    $selectedIds = array_map('intval', old('participants', $event->participants->modelKeys()));
    // An empty submitted selection must remain empty when validation fails.
    if(session()->hasOldInput('intent') && !session()->hasOldInput('participants')) $selectedIds = [];
    $previewValues = ['{evento}' => $event->title, '{departamento}' => $department->name, '{data}' => $event->start_date->format('d/m/Y'), '{horario}' => $event->all_day ? 'Dia inteiro' : $event->start_date->format('H:i'), '{local}' => $event->location ?: 'A confirmar'];
    $instanceId = old('instance_id', $setting->instance_id);
    $lastRun = Illuminate\Support\Facades\Cache::get('departments.event-notifications.last-run');
    $workerActive = $lastRun && now()->diffInMinutes(Illuminate\Support\Carbon::parse($lastRun)) < 5;
@endphp
<div class="dept-page" data-event-notifications data-preview-values="{{ json_encode($previewValues) }}">
    <header class="dept-hero">
        <div class="dept-hero-copy"><div class="dept-eyebrow">{{ $department->name }} · {{ $event->start_date->format('d/m/Y') }}</div><h1>{{ $event->title }}</h1><p>Escolha os participantes, a conta de envio e a mensagem do evento.</p></div>
        <a href="{{ route('departments.events.show', [$department, $event]) }}" class="btn btn-outline-secondary">Ver evento</a>
    </header>
    @if(!$workerActive)
        <p class="alert alert-warning">O processador automático não registrou atividade nos últimos minutos. Os agendamentos e envios pendentes precisam do processador ativo.</p>
    @else
        <p class="alert alert-success">Processador automático ativo. Os avisos agendados são verificados a cada minuto.</p>
    @endif
    @error('notification')<p class="alert alert-danger">{{ $message }}</p>@enderror
    <form id="eventNotificationForm" method="POST" action="{{ route('departments.events.notifications.update', [$department, $event]) }}">
        @csrf @method('PUT')
        <input type="hidden" name="request_key" value="{{ $requestKey }}">
        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <section class="dept-panel mb-4">
                    @include('departments.partials.people-picker', ['kind' => 'event-participants', 'fieldName' => 'participants', 'selectedIds' => $selectedIds, 'protectedIds' => [], 'pickerTitle' => 'Participantes do evento', 'pickerDescription' => 'Selecione no cadastro de membros quem receberá os avisos. A seleção é exclusiva deste evento.', 'pickerAction' => 'Escolher participantes', 'emptyText' => 'Nenhum participante selecionado.'])
                    <div class="dept-panel-body pt-0">
                        <button type="button" class="btn btn-outline-secondary" style="min-height:44px" data-select-team="{{ json_encode($department->members()->pluck('members.id')->map(fn($id) => (int) $id)->all()) }}">Adicionar equipe do departamento</button>
                        <p class="dept-muted mt-2 mb-0">O telefone é o informado no cadastro do membro. Números ausentes, inválidos ou repetidos serão identificados no histórico.</p>
                        @error('participants')<p class="text-danger mt-2">{{ $message }}</p>@enderror
                        @error('participants.*')<p class="text-danger mt-2">{{ $message }}</p>@enderror
                    </div>
                </section>
                <section class="dept-panel">
                    <div class="dept-panel-heading"><div><h2>Mensagem do evento</h2><p>Personalize o aviso que será enviado por WhatsApp.</p></div></div>
                    <div class="dept-panel-body">
                        <label for="notification-message" class="form-label">Mensagem *</label>
                        <textarea id="notification-message" name="message" rows="8" required maxlength="4000" class="form-control @error('message') is-invalid @enderror">{{ old('message', $setting->message ?: App\Services\DepartmentEventNotificationService::DEFAULT_MESSAGE) }}</textarea>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Personalize com <code>{nome}</code>, <code>{evento}</code>, <code>{departamento}</code>, <code>{data}</code>, <code>{horario}</code> e <code>{local}</code>.</div>
                        <h3 class="h6 mt-4">Prévia da mensagem</h3>
                        <p class="p-3 rounded bg-light border mb-0" style="white-space:pre-wrap;overflow-wrap:anywhere" data-notification-preview aria-live="polite"></p>
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-5">
                <section class="dept-panel">
                    <div class="dept-panel-heading"><div><h2>Envio pelo WhatsApp</h2><p>Esta escolha se aplica somente a este evento.</p></div></div>
                    <div class="dept-panel-body">
                        <label for="notification-instance" class="form-label">Instância de envio</label>
                        <select id="notification-instance" name="instance_id" class="form-select @error('instance_id') is-invalid @enderror">
                            <option value="">Selecione uma instância</option>
                            @foreach($instances as $instance)
                                <option value="{{ $instance['id'] }}" @selected($instanceId === $instance['id'])>{{ $instance['name'] }} · {{ $instance['connected'] ? 'Conectada' : 'Desconectada' }}</option>
                            @endforeach
                            @if($instanceId && !$instances->contains('id', $instanceId))<option value="{{ $instanceId }}" selected>{{ $setting->instance_name ?: 'Instância salva' }} · Indisponível</option>@endif
                        </select>
                        @error('instance_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <a class="d-inline-flex align-items-center mt-2" style="min-height:44px" href="{{ route('departments.events.notifications.edit', [$department, $event, 'refresh_instances' => 1]) }}">Atualizar instâncias</a>
                        @if($instances->isEmpty())<p class="text-muted">Nenhuma instância disponível. Confira a configuração da integração WhatsApp.</p>@endif
                        <hr>
                        <input type="hidden" name="automatic_enabled" value="0">
                        <div class="form-check form-switch mb-3">
                            <input id="automatic-enabled" name="automatic_enabled" value="1" type="checkbox" class="form-check-input" @checked(old('automatic_enabled', $setting->automatic_enabled))>
                            <label for="automatic-enabled" class="form-check-label">Ativar envio automático</label>
                        </div>
                        <label for="notification-schedule" class="form-label">Data e horário do aviso</label>
                        <input id="notification-schedule" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at', $setting->scheduled_at?->format('Y-m-d\TH:i')) }}" class="form-control @error('scheduled_at') is-invalid @enderror">
                        @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <p class="form-text">Um aviso automático por agendamento, no fuso {{ config('app.timezone') }}. Para programar outro aviso, escolha uma nova data e horário.</p>
                        <div class="d-grid gap-2 mt-4">
                            <button class="btn btn-primary" style="min-height:44px" type="submit" name="intent" value="save">Salvar configurações</button>
                            <button class="btn btn-outline-success" style="min-height:44px" type="button" data-bs-toggle="modal" data-bs-target="#confirmEventNotification"><i class="bx bxl-whatsapp me-1" aria-hidden="true"></i>Enviar agora</button>
                        </div>
                        <p class="dept-muted small mt-3 mb-0">Enviar agora salva as opções acima e inicia um envio manual. O agendamento automático permanece independente.</p>
                    </div>
                </section>
            </div>
        </div>
    </form>
    <section class="dept-panel mt-4">
        <div class="dept-panel-heading"><div><h2>Histórico de envios</h2><p>Enviado indica que a integração aceitou a mensagem; não confirma a leitura no WhatsApp.</p></div><a href="{{ route('departments.events.notifications.edit', [$department, $event]) }}" class="btn btn-outline-secondary">Atualizar histórico</a></div>
        <div class="dept-panel-body">
            @php $labels = ['pending' => 'Pendente', 'processing' => 'Enviando', 'sent' => 'Enviado', 'failed' => 'Falha', 'skipped' => 'Não enviado', 'cancelled' => 'Cancelado', 'unknown' => 'Sem confirmação']; @endphp
            <div class="row g-3">
                @forelse($history as $delivery)
                    <div class="col-12 col-lg-6">
                        <article class="border rounded p-3 h-100">
                            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between"><strong>{{ $delivery->recipient_name }}</strong><span class="badge {{ $delivery->status === 'sent' ? 'bg-success' : (in_array($delivery->status, ['failed', 'unknown']) ? 'bg-danger' : 'bg-secondary') }}">{{ $labels[$delivery->status] ?? $delivery->status }}</span></div>
                            <p class="small text-muted mt-2 mb-1">{{ $delivery->mode === 'automatic' ? 'Automático' : 'Manual' }} · {{ $delivery->instance_name }} · {{ Illuminate\Support\Carbon::parse($delivery->created_at)->format('d/m/Y H:i') }}</p>
                            @if($delivery->error)<p class="small mb-0">{{ $delivery->error }}</p>@endif
                        </article>
                    </div>
                @empty
                    <div class="col-12"><p class="dept-muted mb-0">Nenhum envio iniciado para este evento.</p></div>
                @endforelse
            </div>
            <div class="mt-3">{{ $history->links() }}</div>
            <form method="POST" action="{{ route('departments.events.notifications.cancel', [$department, $event]) }}" class="mt-3">@csrf<button type="submit" class="btn btn-outline-danger" style="min-height:44px">Desativar automático e cancelar pendentes</button></form>
        </div>
    </section>
</div>
<div class="modal fade" id="confirmEventNotification" tabindex="-1" aria-labelledby="confirmEventNotificationTitle" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title h5" id="confirmEventNotificationTitle">Enviar aviso do evento?</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
        <div class="modal-body"><p>O aviso será enviado aos participantes selecionados, usando a instância escolhida acima.</p><p class="mb-0">Um novo envio manual pode repetir mensagens já recebidas. Confira o histórico antes de reenviar.</p></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Voltar</button><button type="submit" form="eventNotificationForm" name="intent" value="send" class="btn btn-success">Salvar e enviar agora</button></div>
    </div></div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/departments.js') }}?v={{ filemtime(public_path('js/departments.js')) }}" defer></script>
<script src="{{ asset('js/department-event-notifications.js') }}?v={{ filemtime(public_path('js/department-event-notifications.js')) }}" defer></script>
@endpush
