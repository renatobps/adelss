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
                            <div>
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
