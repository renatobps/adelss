@extends('layouts.porto')

@section('title', 'Escalas Mensais de Cultos')

@section('page-title', 'Escalas Mensais de Cultos')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Início</a></li>
    <li><span>Serviço</span></li>
    <li><span>Escalas Mensais</span></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <div class="card-actions">
                    <a href="#" class="card-action card-action-toggle" data-card-toggle></a>
                    <a href="#" class="card-action card-action-dismiss" data-card-dismiss></a>
                </div>
                <h2 class="card-title">
                    <i class="bx bx-calendar me-2"></i>Escalas Mensais de Cultos
                </h2>
            </header>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateMonthlyModal">
                                    <i class="bx bx-calendar-plus me-2"></i>Gerar Escala Mensal
                                </button>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#manualPreletorModal">
                                    <i class="bx bx-user-voice me-2"></i>Cadastrar Escala de Preletor (Manual)
                                </button>
                                <a href="{{ route('voluntarios.escalas-mensais.create', ['month' => $month, 'year' => $year]) }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-2"></i>Cadastrar Escala Mensal
                                </a>
                            </div>
                        </div>
                        <form method="GET" action="{{ route('voluntarios.escalas-mensais.index') }}" class="row g-3">
                            <div class="col-md-3">
                                <label for="month" class="form-label">Mês</label>
                                <select name="month" id="month" class="form-select" onchange="this.form.submit()">
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(null, $m, 1)->locale('pt_BR')->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="year" class="form-label">Ano</label>
                                <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                                    @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">Todos</option>
                                    <option value="rascunho" {{ request('status') == 'rascunho' ? 'selected' : '' }}>Rascunho</option>
                                    <option value="publicada" {{ request('status') == 'publicada' ? 'selected' : '' }}>Publicada</option>
                                    <option value="cancelada" {{ request('status') == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                                    <option value="concluido" {{ request('status') == 'concluido' ? 'selected' : '' }}>Concluído</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                @if($schedules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Culto</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($schedules as $schedule)
                                    <tr>
                                        <td>{{ $schedule->event->start_date->format('d/m/Y') }}</td>
                                        <td><strong>{{ $schedule->event->title }}</strong></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm dropdown-toggle 
                                                    @if($schedule->status == 'publicada') btn-success
                                                    @elseif($schedule->status == 'cancelada') btn-danger
                                                    @elseif($schedule->status == 'concluido') btn-info
                                                    @else btn-warning
                                                    @endif" 
                                                    type="button" 
                                                    id="statusDropdown{{ $schedule->id }}" 
                                                    data-bs-toggle="dropdown" 
                                                    aria-expanded="false"
                                                    data-schedule-id="{{ $schedule->id }}">
                                                    @if($schedule->status == 'publicada')
                                                        Publicada
                                                    @elseif($schedule->status == 'cancelada')
                                                        Cancelada
                                                    @elseif($schedule->status == 'concluido')
                                                        Concluído
                                                    @else
                                                        Rascunho
                                                    @endif
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="statusDropdown{{ $schedule->id }}">
                                                    <li>
                                                        <a class="dropdown-item status-option" 
                                                           href="#" 
                                                           data-status="rascunho" 
                                                           data-schedule-id="{{ $schedule->id }}">
                                                            <span class="badge badge-warning">Rascunho</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item status-option" 
                                                           href="#" 
                                                           data-status="publicada" 
                                                           data-schedule-id="{{ $schedule->id }}">
                                                            <span class="badge badge-success">Publicada</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item status-option" 
                                                           href="#" 
                                                           data-status="cancelada" 
                                                           data-schedule-id="{{ $schedule->id }}">
                                                            <span class="badge badge-danger">Cancelada</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item status-option" 
                                                           href="#" 
                                                           data-status="concluido" 
                                                           data-schedule-id="{{ $schedule->id }}">
                                                            <span class="badge badge-info">Concluído</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('voluntarios.escalas-mensais.show', $schedule) }}" class="btn btn-sm btn-default" title="Ver">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <button type="button"
                                                        class="btn btn-sm btn-success notify-all-btn"
                                                        data-schedule-id="{{ $schedule->id }}"
                                                        data-culto-title="{{ $schedule->event->title }}"
                                                        title="Notificar todo mundo">
                                                    <i class="bx bxl-whatsapp"></i>
                                                </button>
                                                @if($schedule->status == 'publicada')
                                                    <a href="{{ route('voluntarios.escalas-mensais.pdf', $schedule) }}" class="btn btn-sm btn-info" title="Imprimir PDF" target="_blank">
                                                        <i class="bx bx-printer"></i>
                                                    </a>
                                                @endif
                                                @if($schedule->status == 'rascunho')
                                                    <a href="{{ route('voluntarios.escalas-mensais.edit', $schedule) }}" class="btn btn-sm btn-primary" title="Editar">
                                                        <i class="bx bx-edit"></i>
                                                    </a>
                                                @endif
                                                @if($schedule->status != 'publicada')
                                                    <form action="{{ route('voluntarios.escalas-mensais.destroy', $schedule) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover esta escala?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhuma escala cadastrada para o mês selecionado. 
                        <a href="{{ route('voluntarios.escalas-mensais.create', ['month' => $month, 'year' => $year]) }}">Cadastrar escalas</a>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>

<div class="modal fade" id="manualPreletorModal" tabindex="-1" aria-labelledby="manualPreletorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('voluntarios.escalas-mensais.preletor.manual') }}">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="manualPreletorModalLabel">Cadastro Manual - Preletor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @if(!$preletorArea)
                        <div class="alert alert-danger mb-0">
                            A área de serviço <strong>Preletor</strong> não foi encontrada.
                        </div>
                    @elseif($cultos->count() === 0)
                        <div class="alert alert-warning mb-0">
                            Nenhum culto encontrado para o mês selecionado.
                        </div>
                    @elseif($preletorVolunteers->count() === 0)
                        <div class="alert alert-warning mb-0">
                            Não existem voluntários ativos vinculados à área de serviço Preletor.
                        </div>
                    @else
                        <div class="mb-3">
                            <label for="manual_preletor_event_id" class="form-label">Culto <span class="text-danger">*</span></label>
                            <select class="form-select" id="manual_preletor_event_id" name="event_id" required>
                                <option value="">Selecione o culto...</option>
                                @foreach($cultos as $culto)
                                    <option value="{{ $culto->id }}">
                                        {{ $culto->start_date->format('d/m/Y H:i') }} - {{ $culto->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="manual_preletor_volunteer_id" class="form-label">Voluntário da Área Preletor <span class="text-danger">*</span></label>
                            <select class="form-select" id="manual_preletor_volunteer_id" name="preletor_volunteer_id" required>
                                <option value="">Selecione o voluntário...</option>
                                @foreach($preletorVolunteers as $volunteer)
                                    <option value="{{ $volunteer->id }}">{{ $volunteer->member->name ?? 'Sem nome' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancelar</button>
                    @if($preletorArea && $cultos->count() > 0 && $preletorVolunteers->count() > 0)
                        <button type="submit" class="btn btn-warning">
                            <i class="bx bx-check me-2"></i>Salvar Escala de Preletor
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="notifyAllFromListModal" tabindex="-1" aria-labelledby="notifyAllFromListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <form method="POST" id="notifyAllFromListForm" action="#" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="notifyAllFromListModalLabel">
                        <i class="bx bxl-whatsapp me-2"></i>Notificar Todos da Escala
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        Culto: <strong id="notify_all_list_culto">-</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Template de mensagem (opcional)</label>
                        <select class="form-select" id="notify_all_list_template_id" name="template_id">
                            <option value="">Sem template (digitar manualmente)</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" data-template="{{ e($template->template) }}">
                                    {{ $template->tipo_notificacao }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mensagem</label>
                        <textarea class="form-control" id="notify_all_list_message" name="mensagem" rows="5" placeholder="Digite a mensagem ou selecione um template acima"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Arquivo de mídia (opcional)</label>
                        <input type="file" class="form-control" name="arquivo" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx">
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="notify_all_list_send_pdf" name="enviar_pdf">
                        <label class="form-check-label" for="notify_all_list_send_pdf">
                            Enviar também o PDF da escala para todos
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bxl-whatsapp me-1"></i>Enviar para Todos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="generateMonthlyModal" tabindex="-1" aria-labelledby="generateMonthlyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('voluntarios.escalas-mensais.generate-monthly') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="generateMonthlyModalLabel">Gerar Escala Mensal Automática</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="service_area_id" class="form-label">Área de Serviço <span class="text-danger">*</span></label>
                        <select class="form-select" id="service_area_id" name="service_area_id" required>
                            <option value="">Selecione a área...</option>
                            @foreach($serviceAreas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="culto_tipo" class="form-label">Tipo de Culto <span class="text-danger">*</span></label>
                        <select class="form-select" id="culto_tipo" name="culto_tipo" required>
                            <option value="">Selecione o culto...</option>
                            <option value="familia">Culto da Família (Domingo)</option>
                            <option value="graca">Culto da Graça (Quarta)</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="month_generate" class="form-label">Mês</label>
                            <select name="month" id="month_generate" class="form-select">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create(null, $m, 1)->locale('pt_BR')->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="year_generate" class="form-label">Ano</label>
                            <select name="year" id="year_generate" class="form-select">
                                @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        Regras de geração por área:<br>
                        Apoio Geral: 1 | Direção do Culto: 1 | Intercessão: 4 | Portaria: 1 | Preletor: 1 | Recepção: 2 | Sala das Crianças: 2 (1 professor + 1 monitor).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-play-circle me-2"></i>Gerar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notifyModalElement = document.getElementById('notifyAllFromListModal');
    const notifyModal = notifyModalElement ? new bootstrap.Modal(notifyModalElement) : null;
    const notifyForm = document.getElementById('notifyAllFromListForm');
    const notifyCulto = document.getElementById('notify_all_list_culto');
    const notifyTemplate = document.getElementById('notify_all_list_template_id');
    const notifyMessage = document.getElementById('notify_all_list_message');

    document.querySelectorAll('.notify-all-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const scheduleId = this.getAttribute('data-schedule-id');
            const cultoTitle = this.getAttribute('data-culto-title') || '-';
            if (!notifyForm || !notifyModal) return;

            notifyForm.action = `{{ url('/servico/voluntarios/escalas-mensais') }}/${scheduleId}/volunteers/notify-all`;
            notifyCulto.textContent = cultoTitle;
            notifyTemplate.value = '';
            notifyMessage.value = '';
            notifyModal.show();
        });
    });

    if (notifyTemplate && notifyMessage) {
        notifyTemplate.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const templateText = selected.getAttribute('data-template') || '';
            if (templateText) {
                notifyMessage.value = templateText;
            }
        });
    }

    // Adicionar evento de clique nas opções de status
    document.querySelectorAll('.status-option').forEach(function(option) {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            const scheduleId = this.getAttribute('data-schedule-id');
            const newStatus = this.getAttribute('data-status');
            const button = document.querySelector(`#statusDropdown${scheduleId}`);
            
            // Confirmar alteração
            if (!confirm('Deseja alterar o status desta escala?')) {
                return;
            }
            
            // Fazer requisição AJAX
            fetch(`{{ url('servico/voluntarios/escalas-mensais') }}/${scheduleId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar o botão
                    const statusLabels = {
                        'rascunho': 'Rascunho',
                        'publicada': 'Publicada',
                        'cancelada': 'Cancelada',
                        'concluido': 'Concluído'
                    };
                    
                    const statusClasses = {
                        'rascunho': 'btn-warning',
                        'publicada': 'btn-success',
                        'cancelada': 'btn-danger',
                        'concluido': 'btn-info'
                    };
                    
                    // Remover todas as classes de status
                    button.classList.remove('btn-warning', 'btn-success', 'btn-danger', 'btn-info');
                    // Adicionar a classe correta
                    button.classList.add(statusClasses[newStatus]);
                    // Atualizar o texto
                    button.textContent = statusLabels[newStatus];
                    
                    // Mostrar mensagem de sucesso
                    alert(data.message);
                    
                    // Recarregar a página após 1 segundo para atualizar os filtros
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Erro ao atualizar status: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar status. Tente novamente.');
            });
        });
    });
});
</script>
@endpush
@endsection
