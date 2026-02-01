@extends('layouts.porto')

@php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
$isAdmin = Auth::user()?->is_admin ?? false;
@endphp

@section('title', 'Ver Escala - Moriah')

@section('page-title', 'Ver Escala')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Início</a></li>
    <li><a href="{{ route('moriah.ministerio') }}">Moriah</a></li>
    <li><a href="{{ route('moriah.schedules.index') }}">Escalas</a></li>
    <li><span>Ver Escala</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <div class="card-actions">
                    <a href="#" class="card-action card-action-toggle" data-card-toggle></a>
                    <a href="#" class="card-action card-action-dismiss" data-card-dismiss></a>
                </div>
                <h2 class="card-title">
                    <i class="bx bx-calendar me-2"></i>{{ $moriahSchedule->title }}
                </h2>
            </header>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Informações Gerais</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Data:</th>
                                <td>{{ $moriahSchedule->date->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Hora:</th>
                                <td>{{ $moriahSchedule->time ? \Carbon\Carbon::parse($moriahSchedule->time)->format('H:i') : '-' }}</td>
                            </tr>
                            @if($moriahSchedule->event)
                            <tr>
                                <th>Culto:</th>
                                <td>{{ $moriahSchedule->event->title }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-{{ $moriahSchedule->status == 'publicada' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($moriahSchedule->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Solicitar Confirmação:</th>
                                <td>{{ $moriahSchedule->request_confirmation ? 'Sim' : 'Não' }}</td>
                            </tr>
                            @if($moriahSchedule->observations)
                            <tr>
                                <th>Observações:</th>
                                <td>{{ $moriahSchedule->observations }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h5>Participantes ({{ $moriahSchedule->members->count() }})</h5>
                        @if($moriahSchedule->members->count() > 0)
                            <div class="list-group">
                                @foreach($moriahSchedule->members as $member)
                                    @php
                                        $scheduleMember = DB::table('moriah_schedule_members')
                                            ->where('moriah_schedule_id', $moriahSchedule->id)
                                            ->where('member_id', $member->id)
                                            ->first();
                                        
                                        $selectedFunctions = $selectedMemberFunctions[$member->id] ?? [];
                                    @endphp
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                @if($member->photo_url)
                                                    <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                                                @else
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                        {{ strtoupper(substr($member->name, 0, 2)) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">{{ $member->name }}</div>
                                                @if(!empty($selectedFunctions))
                                                    <small class="text-muted">{{ implode(', ', $selectedFunctions) }}</small>
                                                @else
                                                    <small class="text-muted">{{ $member->moriahFunctions->pluck('name')->join(', ') }}</small>
                                                @endif
                                                <div class="d-flex align-items-center gap-2 mt-2">
                                                    @if($isAdmin)
                                                        @php
                                                            $currentStatus = $scheduleMember ? $scheduleMember->status : 'pendente';
                                                            $statusClasses = [
                                                                'pendente' => 'btn-warning',
                                                                'confirmado' => 'btn-success',
                                                                'recusado' => 'btn-danger',
                                                                'cancelado' => 'btn-dark'
                                                            ];
                                                            $statusLabels = [
                                                                'pendente' => 'Pendente',
                                                                'confirmado' => 'Confirmado',
                                                                'recusado' => 'Recusado',
                                                                'cancelado' => 'Cancelado'
                                                            ];
                                                        @endphp
                                                        <div class="btn-group">
                                                            <button class="btn btn-sm dropdown-toggle {{ $statusClasses[$currentStatus] ?? 'btn-warning' }}" 
                                                                    type="button" 
                                                                    id="memberStatusDropdown{{ $scheduleMember->id ?? $member->id }}" 
                                                                    data-bs-toggle="dropdown" 
                                                                    aria-expanded="false"
                                                                    data-pivot-id="{{ $scheduleMember->id ?? '' }}">
                                                                {{ $statusLabels[$currentStatus] ?? 'Pendente' }}
                                                            </button>
                                                            <ul class="dropdown-menu" aria-labelledby="memberStatusDropdown{{ $scheduleMember->id ?? $member->id }}">
                                                                <li>
                                                                    <a class="dropdown-item member-status-option" 
                                                                       href="#" 
                                                                       data-status="pendente" 
                                                                       data-pivot-id="{{ $scheduleMember->id ?? '' }}">
                                                                        <span class="badge badge-warning">Pendente</span>
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item member-status-option" 
                                                                       href="#" 
                                                                       data-status="confirmado" 
                                                                       data-pivot-id="{{ $scheduleMember->id ?? '' }}">
                                                                        <span class="badge badge-success">Confirmado</span>
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item member-status-option" 
                                                                       href="#" 
                                                                       data-status="recusado" 
                                                                       data-pivot-id="{{ $scheduleMember->id ?? '' }}">
                                                                        <span class="badge badge-danger">Recusado</span>
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item member-status-option" 
                                                                       href="#" 
                                                                       data-status="cancelado" 
                                                                       data-pivot-id="{{ $scheduleMember->id ?? '' }}">
                                                                        <span class="badge badge-dark">Cancelado</span>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    @else
                                                        <span class="badge bg-{{ $scheduleMember && $scheduleMember->status == 'confirmado' ? 'success' : ($scheduleMember && $scheduleMember->status == 'recusado' ? 'danger' : ($scheduleMember && $scheduleMember->status == 'cancelado' ? 'dark' : 'warning')) }}">
                                                            {{ $scheduleMember ? ucfirst($scheduleMember->status) : 'Pendente' }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">Nenhum participante cadastrado.</p>
                        @endif
                    </div>

                    <div class="col-md-6 mb-4">
                        <h5>Músicas ({{ $moriahSchedule->songs->count() }})</h5>
                        @if($moriahSchedule->songs->count() > 0)
                            <div class="list-group">
                                @foreach($moriahSchedule->songs as $song)
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">{{ $song->version_name ?? $song->title }}</div>
                                                @if($song->artist)
                                                    <small class="text-muted">{{ $song->artist }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">Nenhuma música cadastrada.</p>
                        @endif
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('moriah.schedules.index') }}" class="btn btn-secondary">Voltar</a>
                    <a href="{{ route('moriah.schedules.edit', $moriahSchedule) }}" class="btn btn-primary">Editar</a>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
@if($isAdmin)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusOptions = document.querySelectorAll('.member-status-option');
    
    statusOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            const pivotId = this.dataset.pivotId;
            const newStatus = this.dataset.status;
            
            if (!pivotId) {
                alert('Erro: ID do registro não encontrado');
                return;
            }
            
            // Encontrar o botão do dropdown correspondente
            const dropdownMenu = this.closest('.dropdown-menu');
            const dropdownId = dropdownMenu.getAttribute('aria-labelledby');
            const button = document.getElementById(dropdownId);
            
            if (!button) return;
            
            // Desabilitar botão durante a requisição
            button.disabled = true;
            const originalText = button.textContent.trim();
            button.textContent = 'Atualizando...';
            
            // Criar FormData para enviar como PUT
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('status', newStatus);
            
            fetch('{{ route("moriah.schedules.members.updateStatus", ":pivotId") }}'.replace(':pivotId', pivotId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || `Erro ${response.status}: ${response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Atualizar classes e texto do botão
                    const statusClasses = {
                        'pendente': 'btn-warning',
                        'confirmado': 'btn-success',
                        'recusado': 'btn-danger',
                        'cancelado': 'btn-dark'
                    };
                    const statusLabels = {
                        'pendente': 'Pendente',
                        'confirmado': 'Confirmado',
                        'recusado': 'Recusado',
                        'cancelado': 'Cancelado'
                    };
                    
                    // Remover todas as classes de status
                    button.classList.remove('btn-warning', 'btn-success', 'btn-danger', 'btn-dark');
                    // Adicionar nova classe
                    button.classList.add(statusClasses[newStatus] || 'btn-warning');
                    // Atualizar texto
                    button.textContent = statusLabels[newStatus] || 'Pendente';
                    
                    // Fechar dropdown
                    const dropdownInstance = bootstrap.Dropdown.getInstance(button);
                    if (dropdownInstance) {
                        dropdownInstance.hide();
                    }
                } else {
                    throw new Error(data.message || 'Erro ao atualizar status');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar status: ' + error.message);
                // Restaurar texto original
                button.textContent = originalText;
            })
            .finally(() => {
                button.disabled = false;
            });
        });
    });
});
</script>
@endif
@endpush
