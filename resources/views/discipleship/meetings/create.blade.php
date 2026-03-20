@extends('layouts.porto')

@section('title', 'Registrar Encontro')

@section('page-title', 'Registrar Encontro')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><a href="{{ route('discipleship.meetings.index') }}">Encontros</a></li>
    <li><span>Registrar</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-10">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <form action="{{ route('discipleship.meetings.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="discipleship_member_id" class="form-label">Membro em Discipulado <span class="text-danger">*</span></label>
                        <select class="form-select @error('discipleship_member_id') is-invalid @enderror" id="discipleship_member_id" name="discipleship_member_id" required>
                            <option value="">Selecione um membro</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}" {{ old('discipleship_member_id', $memberId) == $m->id ? 'selected' : '' }}>
                                    {{ $m->member->name }} - {{ $m->cycle->nome }}
                                </option>
                            @endforeach
                        </select>
                        @error('discipleship_member_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('data') is-invalid @enderror" 
                                   id="data" name="data" value="{{ old('data', date('Y-m-d')) }}" required>
                            @error('data')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" required>
                                <option value="presencial" {{ old('tipo', 'presencial') === 'presencial' ? 'selected' : '' }}>Presencial</option>
                                <option value="online" {{ old('tipo') === 'online' ? 'selected' : '' }}>Online</option>
                            </select>
                            @error('tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="assuntos_tratados" class="form-label">Assuntos Tratados</label>
                        <textarea class="form-control @error('assuntos_tratados') is-invalid @enderror" 
                                  id="assuntos_tratados" name="assuntos_tratados" rows="4">{{ old('assuntos_tratados') }}</textarea>
                        @error('assuntos_tratados')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="goals-section">
                        <label class="form-label">Propósitos a vincular</label>
                        <small class="d-block text-muted mb-2">Selecione os propósitos discutidos neste encontro (apenas em andamento ou pausados)</small>
                        <div id="goals-container">
                            <p class="text-muted small" id="goals-placeholder">Selecione um membro para exibir os propósitos disponíveis.</p>
                            <div id="goals-checkboxes" class="d-none"></div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3"><i class="bx bx-heart me-1"></i>Questionário - Área Espiritual</h5>

                    {{-- Oração --}}
                    <div class="card mb-3" style="border-left: 4px solid #059669;">
                        <div class="card-body">
                            <h6 class="text-success mb-3">Oração</h6>
                            <div class="mb-3">
                                <label for="oracao_tempo_dia" class="form-label">Quanto tempo tem orado por dia? <span class="text-danger">*</span></label>
                                <select class="form-select @error('oracao_tempo_dia') is-invalid @enderror" id="oracao_tempo_dia" name="oracao_tempo_dia" required>
                                    @foreach([0,5,10,15,20,25,30,35,40,45,50,55,60] as $min)
                                        <option value="{{ $min }}" {{ old('oracao_tempo_dia') == (string)$min ? 'selected' : '' }}>{{ $min }} minutos</option>
                                    @endforeach
                                    <option value="mais_1h" {{ old('oracao_tempo_dia') === 'mais_1h' ? 'selected' : '' }}>+ de 1 hora</option>
                                </select>
                                @error('oracao_tempo_dia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="oracao_como_sao" class="form-label">Como são suas orações? <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('oracao_como_sao') is-invalid @enderror" id="oracao_como_sao" name="oracao_como_sao" rows="3" required>{{ old('oracao_como_sao') }}</textarea>
                                @error('oracao_como_sao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-0">
                                <label for="oracao_observacoes" class="form-label">Observações</label>
                                <textarea class="form-control @error('oracao_observacoes') is-invalid @enderror" id="oracao_observacoes" name="oracao_observacoes" rows="2">{{ old('oracao_observacoes') }}</textarea>
                                @error('oracao_observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Jejum --}}
                    <div class="card mb-3" style="border-left: 4px solid #dc2626;">
                        <div class="card-body">
                            <h6 class="text-danger mb-3">Jejum</h6>
                            <div class="mb-3">
                                <label for="jejum_horas_semana" class="form-label">Quantas horas de jejum tem feito por semana?</label>
                                <select class="form-select @error('jejum_horas_semana') is-invalid @enderror" id="jejum_horas_semana" name="jejum_horas_semana">
                                    @foreach([0,6,12,18,24] as $h)
                                        <option value="{{ $h }}" {{ old('jejum_horas_semana') == (string)$h ? 'selected' : '' }}>{{ $h }} horas</option>
                                    @endforeach
                                    <option value="mais_24" {{ old('jejum_horas_semana') === 'mais_24' ? 'selected' : '' }}>+ de 24 horas</option>
                                </select>
                                @error('jejum_horas_semana')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tem feito jejum? <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="jejum_tipo" id="jejum_total" value="total" {{ old('jejum_tipo') === 'total' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="jejum_total">Total</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="jejum_tipo" id="jejum_parcial" value="parcial" {{ old('jejum_tipo') === 'parcial' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="jejum_parcial">Parcial</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="jejum_tipo" id="jejum_nenhum" value="nenhum" {{ old('jejum_tipo', 'nenhum') === 'nenhum' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="jejum_nenhum">Nenhum</label>
                                    </div>
                                </div>
                                @error('jejum_tipo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Seu jejum é com propósito? <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="jejum_com_proposito" id="jejum_proposito_sim" value="sim" {{ old('jejum_com_proposito') === 'sim' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="jejum_proposito_sim">Sim</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="jejum_com_proposito" id="jejum_proposito_nao" value="nao" {{ old('jejum_com_proposito') === 'nao' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="jejum_proposito_nao">Não</label>
                                    </div>
                                </div>
                                @error('jejum_com_proposito')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-0">
                                <label for="jejum_observacoes" class="form-label">Observações</label>
                                <textarea class="form-control @error('jejum_observacoes') is-invalid @enderror" id="jejum_observacoes" name="jejum_observacoes" rows="2">{{ old('jejum_observacoes') }}</textarea>
                                @error('jejum_observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Leitura Bíblica --}}
                    <div class="card mb-3" style="border-left: 4px solid #2563eb;">
                        <div class="card-body">
                            <h6 class="text-primary mb-3">Leitura Bíblica</h6>
                            <div class="mb-3">
                                <label for="leitura_capitulos_dia" class="form-label">Quantos capítulos tem lido por dia? <span class="text-danger">*</span></label>
                                <select class="form-select @error('leitura_capitulos_dia') is-invalid @enderror" id="leitura_capitulos_dia" name="leitura_capitulos_dia" required>
                                    @foreach(range(0, 10) as $n)
                                        <option value="{{ $n }}" {{ old('leitura_capitulos_dia') == (string)$n ? 'selected' : '' }}>{{ $n }} {{ $n == 1 ? 'capítulo' : 'capítulos' }}</option>
                                    @endforeach
                                    <option value="mais_10" {{ old('leitura_capitulos_dia') === 'mais_10' ? 'selected' : '' }}>+ de 10</option>
                                </select>
                                @error('leitura_capitulos_dia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Você estuda os capítulos que lê? <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="leitura_estuda" id="leitura_estuda_sim" value="sim" {{ old('leitura_estuda') === 'sim' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="leitura_estuda_sim">Sim</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="leitura_estuda" id="leitura_estuda_nao" value="nao" {{ old('leitura_estuda') === 'nao' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="leitura_estuda_nao">Não</label>
                                    </div>
                                </div>
                                @error('leitura_estuda')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-0">
                                <label for="leitura_observacoes" class="form-label">Observações</label>
                                <textarea class="form-control @error('leitura_observacoes') is-invalid @enderror" id="leitura_observacoes" name="leitura_observacoes" rows="2">{{ old('leitura_observacoes') }}</textarea>
                                @error('leitura_observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label for="proximo_passo" class="form-label">Próximo Passo</label>
                        <textarea class="form-control @error('proximo_passo') is-invalid @enderror" 
                                  id="proximo_passo" name="proximo_passo" rows="3">{{ old('proximo_passo') }}</textarea>
                        @error('proximo_passo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="observacoes_privadas" class="form-label">Observações Privadas</label>
                        <textarea class="form-control @error('observacoes_privadas') is-invalid @enderror" 
                                  id="observacoes_privadas" name="observacoes_privadas" rows="3">{{ old('observacoes_privadas') }}</textarea>
                        <small class="form-text text-muted">Estas observações são privadas e não serão compartilhadas com o discípulo.</small>
                        @error('observacoes_privadas')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <a href="{{ route('discipleship.goals.create', $memberId ? ['discipleship_member_id' => $memberId] : []) }}" class="btn btn-outline-success" id="btnCadastrarProposito">
                            <i class="bx bx-target-lock me-1"></i>Cadastrar Propósito
                        </a>
                        <div class="d-flex gap-2">
                            <a href="{{ route('discipleship.meetings.index') }}" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-check me-1"></i>Registrar Encontro
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const memberSelect = document.getElementById('discipleship_member_id');
    const btnProposito = document.getElementById('btnCadastrarProposito');
    const baseUrl = '{{ route("discipleship.goals.create") }}';
    const goalsByMember = @json($goalsByMember ?? collect());
    
    function updateGoals() {
        const memberId = memberSelect.value;
        const placeholder = document.getElementById('goals-placeholder');
        const container = document.getElementById('goals-checkboxes');
        container.innerHTML = '';
        container.classList.add('d-none');
        placeholder.classList.remove('d-none');
        
        if (memberId && goalsByMember[memberId]) {
            const goals = goalsByMember[memberId];
            if (goals.length > 0) {
                placeholder.classList.add('d-none');
                container.classList.remove('d-none');
                const oldGoals = @json(old('goal_ids', []));
                goals.forEach(function(g) {
                    const checked = oldGoals.includes(String(g.id)) ? ' checked' : '';
                    const div = document.createElement('div');
                    div.className = 'form-check';
                    div.innerHTML = '<input class="form-check-input" type="checkbox" name="goal_ids[]" value="' + g.id + '" id="goal_' + g.id + '"' + checked + '>' +
                        '<label class="form-check-label" for="goal_' + g.id + '">' + (g.descricao || 'Propósito #' + g.id) + '</label>';
                    container.appendChild(div);
                });
            } else {
                placeholder.textContent = 'Nenhum propósito em andamento para este membro.';
            }
        } else if (memberId) {
            placeholder.textContent = 'Nenhum propósito em andamento para este membro.';
        }
    }
    
    if (memberSelect && btnProposito) {
        memberSelect.addEventListener('change', function() {
            const memberId = this.value;
            btnProposito.href = memberId ? baseUrl + '?discipleship_member_id=' + memberId : baseUrl;
            updateGoals();
        });
        updateGoals();
    }
});
</script>
@endpush
@endsection
