@extends('layouts.porto')

@section('title', 'Escalas de Serviço')

@section('page-title', 'Escalas de Serviço')

@section('breadcrumbs')
    <li><a href="{{ route('servico.escalas.index') }}">Escalas</a></li>
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
                    <i class="bx bx-calendar me-2"></i>Escalas de Serviço
                </h2>
            </header>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <a href="{{ route('servico.escalas.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-2"></i>Nova Escala
                        </a>
                    </div>
                </div>

                <!-- Filtros -->
                <form method="GET" action="{{ route('servico.escalas.index') }}" class="mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="area" class="form-label">Área</label>
                            <select name="area" id="area" class="form-select">
                                <option value="">Todas</option>
                                @foreach($serviceAreas as $area)
                                    <option value="{{ $area->id }}" {{ request('area') == $area->id ? 'selected' : '' }}>
                                        {{ $area->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Todos</option>
                                <option value="rascunho" {{ request('status') == 'rascunho' ? 'selected' : '' }}>Rascunho</option>
                                <option value="publicada" {{ request('status') == 'publicada' ? 'selected' : '' }}>Publicada</option>
                                <option value="cancelada" {{ request('status') == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                                <option value="concluido" {{ request('status') == 'concluido' ? 'selected' : '' }}>Concluído</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Data Início</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Data Fim</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-default">
                                    <i class="bx bx-search me-2"></i>Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                @if($schedules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Horário</th>
                                    <th>Áreas</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($schedules as $schedule)
                                    <tr>
                                        <td>{{ $schedule->date->format('d/m/Y') }}</td>
                                        <td><strong>{{ $schedule->title }}</strong></td>
                                        <td>
                                            @if($schedule->type == 'culto')
                                                <span class="badge badge-info">Culto</span>
                                            @else
                                                <span class="badge badge-primary">Evento</span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}</td>
                                        <td>{{ $schedule->areas->count() }} área(s)</td>
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
                                                <a href="{{ route('servico.escalas.show', $schedule) }}" class="btn btn-sm btn-default" title="Ver">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                @if($schedule->status != 'publicada')
                                                    <a href="{{ route('servico.escalas.edit', $schedule) }}" class="btn btn-sm btn-primary" title="Editar">
                                                        <i class="bx bx-edit"></i>
                                                    </a>
                                                @endif
                                                @if($schedule->status != 'publicada')
                                                    <form action="{{ route('servico.escalas.duplicate', $schedule) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-info" title="Duplicar" onclick="return confirm('Deseja duplicar esta escala?')">
                                                            <i class="bx bx-copy"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('servico.escalas.destroy', $schedule) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta escala? Esta ação não pode ser desfeita.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-4">
                        {{ $schedules->links() }}
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhuma escala encontrada.
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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
            fetch(`{{ url('servico/escalas') }}/${scheduleId}/status`, {
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
