@extends('layouts.porto')

@section('title', 'Indisponibilidades - Moriah')

@section('page-title', 'Indisponibilidades')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Início</a></li>
    <li><a href="{{ route('moriah.ministerio') }}">Moriah</a></li>
    <li><span>Indisponibilidades</span></li>
@endsection

@section('content')
<div class="row">
    <!-- Calendário -->
    <div class="col-lg-8">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">Indisponibilidades</h2>
                <p class="text-muted mb-0">MORIAH MUSIC</p>
            </header>
            <div class="card-body">
                <!-- Navegação do Calendário -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="prevMonth">
                        <i class="bx bx-chevron-left"></i>
                    </button>
                    <h4 class="mb-0" id="monthYear">
                        {{ \Carbon\Carbon::create($year, $month, 1)->locale('pt_BR')->translatedFormat('F \d\e Y') }}
                    </h4>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="nextMonth">
                        <i class="bx bx-chevron-right"></i>
                    </button>
                </div>

                <!-- Calendário -->
                <div id="calendar-container">
                    <table class="table table-bordered text-center" id="calendar">
                        <thead>
                            <tr>
                                <th class="text-muted small">Dom.</th>
                                <th class="text-muted small">Seg.</th>
                                <th class="text-muted small">Ter.</th>
                                <th class="text-muted small">Qua.</th>
                                <th class="text-muted small">Qui.</th>
                                <th class="text-muted small">Sex.</th>
                                <th class="text-muted small">Sáb.</th>
                            </tr>
                        </thead>
                        <tbody id="calendar-body">
                            <!-- Será preenchido via JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Detalhes da Data Selecionada -->
                <div id="selectedDateDetails" class="mt-4" style="display: none;">
                    <h5 id="selectedDateTitle"></h5>
                    <div id="selectedDateUnavailabilities">
                        <!-- Lista de indisponibilidades -->
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Lista de Membros -->
    <div class="col-lg-4">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">Membros</h2>
            </header>
            <div class="card-body">
                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-primary" id="selectAllMembers">
                        Selecionar todos
                    </button>
                </div>
                <div class="mb-3">
                    <input type="text" class="form-control" id="searchMembers" placeholder="Pesquisar">
                </div>
                <div class="list-group" id="membersList" style="max-height: 600px; overflow-y: auto;">
                    @foreach($members as $member)
                        <div class="list-group-item member-item" data-member-id="{{ $member->id }}" data-member-name="{{ $member->name }}">
                            <div class="d-flex align-items-center">
                                @if($member->photo_url)
                                    <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" 
                                         class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                         style="width: 40px; height: 40px; font-size: 14px; font-weight: bold;">
                                        {{ strtoupper(substr($member->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div class="flex-grow-1">
                                    <div class="fw-bold">{{ $member->name }}</div>
                                </div>
                                <i class="bx bx-chevron-right text-muted"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal Nova Indisponibilidade -->
<div class="modal fade" id="newUnavailabilityModal" tabindex="-1" aria-labelledby="newUnavailabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newUnavailabilityModalLabel">Nova Indisponibilidade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="unavailabilityForm">
                <div class="modal-body">
                    <input type="hidden" id="formMemberId" name="member_id">
                    <input type="hidden" id="formDate" name="date">
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3" maxlength="500" required></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-primary">
                                Apenas os administradores do ministério podem visualizar a descrição da indisponibilidade
                            </small>
                            <small class="text-muted">
                                <span id="charCount">0</span>/500
                            </small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label mb-0">Selecionar período</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="isPeriod" name="is_period">
                                <label class="form-check-label" for="isPeriod"></label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="dateInput" class="form-label">Data <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="dateInput" name="date" required>
                    </div>

                    <div class="mb-3" id="endDateContainer" style="display: none;">
                        <label for="endDateInput" class="form-label">Data Final</label>
                        <input type="date" class="form-control" id="endDateInput" name="end_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentMonth = {{ $month }};
    let currentYear = {{ $year }};
    let selectedDate = null;
    let selectedMembers = [];
    const unavailabilitiesByDate = @json($unavailabilitiesByDate);

    // Encontrar primeira data com indisponibilidades ou usar data atual
    function getInitialDate() {
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];
        
        // Se hoje está no mês atual e tem indisponibilidades, usar hoje
        if (today.getMonth() + 1 === currentMonth && 
            today.getFullYear() === currentYear && 
            unavailabilitiesByDate[todayStr]) {
            return todayStr;
        }
        
        // Procurar primeira data com indisponibilidades no mês
        const datesWithUnavailabilities = Object.keys(unavailabilitiesByDate).sort();
        if (datesWithUnavailabilities.length > 0) {
            return datesWithUnavailabilities[0];
        }
        
        // Se não houver indisponibilidades, usar hoje se estiver no mês, senão primeiro dia do mês
        if (today.getMonth() + 1 === currentMonth && today.getFullYear() === currentYear) {
            return todayStr;
        }
        
        return `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`;
    }

    // Renderizar calendário
    function renderCalendar() {
        const firstDay = new Date(currentYear, currentMonth - 1, 1);
        const lastDay = new Date(currentYear, currentMonth, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay();

        const tbody = document.getElementById('calendar-body');
        tbody.innerHTML = '';

        let date = 1;
        for (let i = 0; i < 6; i++) {
            const row = document.createElement('tr');
            for (let j = 0; j < 7; j++) {
                const cell = document.createElement('td');
                if (i === 0 && j < startingDayOfWeek) {
                    cell.innerHTML = '';
                } else if (date > daysInMonth) {
                    cell.innerHTML = '';
                } else {
                    const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
                    const hasUnavailability = unavailabilitiesByDate[dateStr] && unavailabilitiesByDate[dateStr].length > 0;
                    
                    cell.innerHTML = `<div class="calendar-day ${hasUnavailability ? 'has-unavailability' : ''}" 
                                         data-date="${dateStr}" 
                                         style="padding: 10px; cursor: pointer; ${dateStr === selectedDate ? 'background-color: #e3f2fd; border-radius: 50%;' : ''}">
                                         ${date}
                                     </div>`;
                    
                    cell.addEventListener('click', function() {
                        selectDate(dateStr);
                    });
                    
                    date++;
                }
                row.appendChild(cell);
            }
            tbody.appendChild(row);
            if (date > daysInMonth) break;
        }

        // Atualizar título do mês
        const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                           'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        document.getElementById('monthYear').textContent = `${monthNames[currentMonth - 1]} de ${currentYear}`;
    }

    // Selecionar data
    function selectDate(dateStr) {
        selectedDate = dateStr;
        renderCalendar();
        showDateDetails(dateStr);
    }

    // Mostrar detalhes da data
    function showDateDetails(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        const dateFormatted = date.toLocaleDateString('pt-BR', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });

        document.getElementById('selectedDateTitle').textContent = dateFormatted;
        const container = document.getElementById('selectedDateUnavailabilities');
        container.innerHTML = '';

        if (unavailabilitiesByDate[dateStr] && unavailabilitiesByDate[dateStr].length > 0) {
            unavailabilitiesByDate[dateStr].forEach(unavailability => {
                const div = document.createElement('div');
                div.className = 'd-flex align-items-center mb-2 p-2 bg-light rounded';
                
                // Preparar inicial do nome para fallback
                const memberName = unavailability.member.name || '';
                const nameParts = memberName.split(' ').filter(p => p.length > 0);
                const initials = nameParts.length > 0 
                    ? (nameParts[0][0] + (nameParts.length > 1 ? nameParts[nameParts.length - 1][0] : nameParts[0][1] || '')).toUpperCase().substring(0, 2)
                    : '??';
                
                div.innerHTML = `
                    ${unavailability.member.photo_url ? `
                        <img src="${unavailability.member.photo_url}" 
                             alt="${memberName}" 
                             class="rounded-circle me-2" 
                             style="width: 40px; height: 40px; object-fit: cover;"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    ` : ''}
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                         style="width: 40px; height: 40px; font-size: 14px; font-weight: bold; ${unavailability.member.photo_url ? 'display: none;' : ''}">
                        ${initials}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${memberName}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteUnavailability(${unavailability.id})">
                        <i class="bx bx-trash"></i>
                    </button>
                `;
                container.appendChild(div);
            });
        } else {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="bx bx-user-x" style="font-size: 48px; color: #ccc;"></i>
                    <p class="text-muted mt-2">Lista vazia.</p>
                </div>
            `;
        }

        document.getElementById('selectedDateDetails').style.display = 'block';
    }

    // Navegação do calendário
    document.getElementById('prevMonth').addEventListener('click', function() {
        currentMonth--;
        if (currentMonth < 1) {
            currentMonth = 12;
            currentYear--;
        }
        window.location.href = `{{ route('moriah.unavailabilities.index') }}?month=${currentMonth}&year=${currentYear}`;
    });

    document.getElementById('nextMonth').addEventListener('click', function() {
        currentMonth++;
        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        }
        window.location.href = `{{ route('moriah.unavailabilities.index') }}?month=${currentMonth}&year=${currentYear}`;
    });

    // Selecionar membro
    document.querySelectorAll('.member-item').forEach(item => {
        item.addEventListener('click', function() {
            if (!selectedDate) {
                alert('Selecione uma data no calendário primeiro');
                return;
            }

            const memberId = this.dataset.memberId;
            const memberName = this.dataset.memberName;

            // Preencher formulário
            document.getElementById('formMemberId').value = memberId;
            document.getElementById('formDate').value = selectedDate;
            document.getElementById('dateInput').value = selectedDate;
            document.getElementById('newUnavailabilityModalLabel').textContent = `Nova Indisponibilidade - ${memberName}`;

            // Abrir modal
            const modal = new bootstrap.Modal(document.getElementById('newUnavailabilityModal'));
            modal.show();
        });
    });

    // Toggle período
    document.getElementById('isPeriod').addEventListener('change', function() {
        const endDateContainer = document.getElementById('endDateContainer');
        if (this.checked) {
            endDateContainer.style.display = 'block';
            document.getElementById('endDateInput').required = true;
        } else {
            endDateContainer.style.display = 'none';
            document.getElementById('endDateInput').required = false;
            document.getElementById('endDateInput').value = '';
        }
    });

    // Contador de caracteres
    document.getElementById('description').addEventListener('input', function() {
        document.getElementById('charCount').textContent = this.value.length;
    });

    // Submeter formulário
    document.getElementById('unavailabilityForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const isPeriod = document.getElementById('isPeriod').checked;
        
        // Adicionar is_period ao FormData
        if (isPeriod) {
            formData.set('is_period', '1');
        } else {
            formData.delete('is_period');
            formData.delete('end_date');
        }
        
        // Validar se período está marcado e end_date está preenchido
        if (isPeriod && !formData.get('end_date')) {
            alert('Por favor, informe a data final do período.');
            return;
        }

        fetch('{{ route("moriah.unavailabilities.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Erro ao cadastrar indisponibilidade');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro ao cadastrar indisponibilidade');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert(error.message || 'Erro ao cadastrar indisponibilidade');
        });
    });

    // Buscar membros
    document.getElementById('searchMembers').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('.member-item').forEach(item => {
            const memberName = item.dataset.memberName.toLowerCase();
            if (memberName.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Renderizar calendário inicial
    renderCalendar();
    
    // Selecionar data inicial automaticamente
    const initialDate = getInitialDate();
    if (initialDate) {
        selectDate(initialDate);
    }
});

function deleteUnavailability(id) {
    if (!confirm('Deseja realmente remover esta indisponibilidade?')) {
        return;
    }

    fetch(`{{ route("moriah.unavailabilities.destroy", ":id") }}`.replace(':id', id), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao remover indisponibilidade');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao remover indisponibilidade');
    });
}
</script>

<style>
.calendar-day:hover {
    background-color: #f0f0f0 !important;
    border-radius: 50%;
}

.calendar-day.has-unavailability {
    position: relative;
}

.calendar-day.has-unavailability::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 6px;
    height: 6px;
    background-color: #dc3545;
    border-radius: 50%;
}
</style>
@endpush
@endsection
