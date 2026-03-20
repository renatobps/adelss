@extends('layouts.porto')

@section('title', 'Criar Propósito')

@section('page-title', 'Criar Propósito')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><a href="{{ route('discipleship.goals.index') }}">Propósitos</a></li>
    <li><span>Criar</span></li>
@endsection

@php
    $livrosBiblia = [
        'Gênesis', 'Êxodo', 'Levítico', 'Números', 'Deuteronômio', 'Josué', 'Juízes', 'Rute',
        '1 Samuel', '2 Samuel', '1 Reis', '2 Reis', '1 Crônicas', '2 Crônicas', 'Esdras', 'Neemias', 'Ester',
        'Jó', 'Salmos', 'Provérbios', 'Eclesiastes', 'Cantares', 'Isaías', 'Jeremias', 'Lamentações',
        'Ezequiel', 'Daniel', 'Oséias', 'Joel', 'Amós', 'Obadias', 'Jonas', 'Miqueias', 'Naum',
        'Habacuque', 'Sofonias', 'Ageu', 'Zacarias', 'Malaquias',
        'Mateus', 'Marcos', 'Lucas', 'João', 'Atos', 'Romanos', '1 Coríntios', '2 Coríntios',
        'Gálatas', 'Efésios', 'Filipenses', 'Colossenses', '1 Tessalonicenses', '2 Tessalonicenses',
        '1 Timóteo', '2 Timóteo', 'Tito', 'Filemom', 'Hebreus', 'Tiago', '1 Pedro', '2 Pedro',
        '1 João', '2 João', '3 João', 'Judas', 'Apocalipse'
    ];
@endphp

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <form action="{{ route('discipleship.goals.store') }}" method="POST" id="goalForm">
                    @csrf
                    
                    <!-- Campos Básicos -->
                    <div class="mb-4">
                        <h5 class="mb-3">Informações Básicas</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
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

                            <div class="col-md-6 mb-3">
                                <label for="tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" required>
                                    <option value="espiritual" {{ old('tipo', 'espiritual') === 'espiritual' ? 'selected' : '' }}>Espiritual</option>
                                    <option value="material" {{ old('tipo') === 'material' ? 'selected' : '' }}>Material</option>
                                </select>
                                @error('tipo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('descricao') is-invalid @enderror" 
                                   id="descricao" name="descricao" value="{{ old('descricao') }}" required>
                            @error('descricao')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="prazo" class="form-label">Prazo</label>
                                <input type="date" class="form-control @error('prazo') is-invalid @enderror" 
                                       id="prazo" name="prazo" value="{{ old('prazo') }}">
                                @error('prazo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="em_andamento" {{ old('status', 'em_andamento') === 'em_andamento' ? 'selected' : '' }}>Em Andamento</option>
                                    <option value="concluido" {{ old('status') === 'concluido' ? 'selected' : '' }}>Concluído</option>
                                    <option value="pausado" {{ old('status') === 'pausado' ? 'selected' : '' }}>Pausado</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- 🟦 Área de Propósito -->
                    <div class="mb-4">
                        <h5 class="mb-3">🟦 Área de Propósito</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantidade_dias" class="form-label">Quantidade de dias do propósito</label>
                                <input type="number" class="form-control @error('quantidade_dias') is-invalid @enderror" 
                                       id="quantidade_dias" name="quantidade_dias" 
                                       value="{{ old('quantidade_dias') }}" min="1" max="30">
                                @error('quantidade_dias')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Restrições durante o propósito</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="restricoes[]" value="filmes" id="restricao_filmes" {{ in_array('filmes', old('restricoes', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="restricao_filmes">Filmes</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="restricoes[]" value="series" id="restricao_series" {{ in_array('series', old('restricoes', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="restricao_series">Séries</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="restricoes[]" value="instagram" id="restricao_instagram" {{ in_array('instagram', old('restricoes', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="restricao_instagram">Instagram</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="restricoes[]" value="youtube" id="restricao_youtube" {{ in_array('youtube', old('restricoes', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="restricao_youtube">YouTube</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="restricoes[]" value="tiktok" id="restricao_tiktok" {{ in_array('tiktok', old('restricoes', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="restricao_tiktok">TikTok</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="restricoes[]" value="facebook" id="restricao_facebook" {{ in_array('facebook', old('restricoes', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="restricao_facebook">Facebook</label>
                                    </div>
                                </div>
                            </div>
                            @error('restricoes')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- 🟦 Área de Jejum -->
                    <div class="mb-4">
                        <h5 class="mb-3">🟦 Área de Jejum</h5>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Jejum</label>
                            <div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_jejum" id="jejum_nenhum" value="nenhum" {{ old('tipo_jejum', 'nenhum') === 'nenhum' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="jejum_nenhum">Nenhum</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_jejum" id="jejum_total" value="total" {{ old('tipo_jejum') === 'total' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="jejum_total">Jejum Total</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_jejum" id="jejum_parcial" value="parcial" {{ old('tipo_jejum') === 'parcial' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="jejum_parcial">Jejum Parcial</label>
                                </div>
                            </div>
                            @error('tipo_jejum')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Opção 1 - Jejum Total -->
                        <div id="jejum_total_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="horas_jejum_total" class="form-label">Quantidade de horas de jejum <span class="text-danger">*</span></label>
                                <select class="form-select @error('horas_jejum_total') is-invalid @enderror" id="horas_jejum_total" name="horas_jejum_total">
                                    <option value="">Selecione</option>
                                    @for($i = 6; $i <= 72; $i += 6)
                                        <option value="{{ $i }}" {{ old('horas_jejum_total') == $i ? 'selected' : '' }}>{{ $i }} horas</option>
                                    @endfor
                                </select>
                                @error('horas_jejum_total')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Opção 2 - Jejum Parcial -->
                        <div id="jejum_parcial_fields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="dias_jejum_parcial" class="form-label">Quantidade de dias <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('dias_jejum_parcial') is-invalid @enderror" 
                                           id="dias_jejum_parcial" name="dias_jejum_parcial" 
                                           value="{{ old('dias_jejum_parcial') }}" min="1" max="30">
                                    @error('dias_jejum_parcial')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alimentos a serem retirados <span class="text-danger">*</span></label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="alimentos_retirados[]" value="derivados_trigo" id="alimento_trigo" {{ in_array('derivados_trigo', old('alimentos_retirados', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alimento_trigo">Derivados de trigo</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="alimentos_retirados[]" value="guloseimas" id="alimento_guloseimas" {{ in_array('guloseimas', old('alimentos_retirados', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alimento_guloseimas">Guloseimas</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="alimentos_retirados[]" value="almoco" id="alimento_almoco" {{ in_array('almoco', old('alimentos_retirados', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alimento_almoco">Almoço</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="alimentos_retirados[]" value="jantar" id="alimento_jantar" {{ in_array('jantar', old('alimentos_retirados', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alimento_jantar">Jantar</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="alimentos_retirados[]" value="cafe_manha" id="alimento_cafe" {{ in_array('cafe_manha', old('alimentos_retirados', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="alimento_cafe">Café da manhã</label>
                                        </div>
                                    </div>
                                </div>
                                @error('alimentos_retirados')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- 🟦 Área de Oração -->
                    <div class="mb-4">
                        <h5 class="mb-3">🟦 Área de Oração</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Período de oração por dia</label>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="periodos_oracao_dia" id="oracao_1" value="1" {{ old('periodos_oracao_dia') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="oracao_1">1 vez ao dia</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="periodos_oracao_dia" id="oracao_2" value="2" {{ old('periodos_oracao_dia') == '2' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="oracao_2">2 vezes ao dia</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="periodos_oracao_dia" id="oracao_3" value="3" {{ old('periodos_oracao_dia') == '3' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="oracao_3">3 vezes ao dia</label>
                                    </div>
                                </div>
                                @error('periodos_oracao_dia')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="minutos_oracao_periodo" class="form-label">Quantidade de minutos por período</label>
                                <input type="number" class="form-control @error('minutos_oracao_periodo') is-invalid @enderror" 
                                       id="minutos_oracao_periodo" name="minutos_oracao_periodo" 
                                       value="{{ old('minutos_oracao_periodo') }}" min="1">
                                @error('minutos_oracao_periodo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- 🟦 Área de Estudo da Palavra -->
                    <div class="mb-4">
                        <h5 class="mb-3">🟦 Área de Estudo da Palavra</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="livro_biblia" class="form-label">Livro a ser estudado</label>
                                <select class="form-select @error('livro_biblia') is-invalid @enderror" id="livro_biblia" name="livro_biblia">
                                    <option value="">Selecione um livro</option>
                                    @foreach($livrosBiblia as $livro)
                                        <option value="{{ $livro }}" {{ old('livro_biblia') == $livro ? 'selected' : '' }}>{{ $livro }}</option>
                                    @endforeach
                                </select>
                                @error('livro_biblia')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="capitulos_por_dia" class="form-label">Quantidade de capítulos por dia</label>
                                <input type="number" class="form-control @error('capitulos_por_dia') is-invalid @enderror" 
                                       id="capitulos_por_dia" name="capitulos_por_dia" 
                                       value="{{ old('capitulos_por_dia') }}" min="1">
                                @error('capitulos_por_dia')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Observação -->
                    <div class="mb-4">
                        <label for="observacao" class="form-label">Observação</label>
                        <x-rich-text-editor
                            name="observacao"
                            id="observacao"
                            :value="old('observacao')"
                            placeholder="Digite suas observações aqui... (textos, reflexões, estrutura do propósito)"
                            :minHeight="320"
                            class="@error('observacao') is-invalid @enderror"
                        />
                        @error('observacao')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('discipleship.goals.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-check me-1"></i>Criar Propósito
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função para mostrar/ocultar campos de jejum
    function toggleJejumFields() {
        const tipoJejum = document.querySelector('input[name="tipo_jejum"]:checked')?.value;
        const jejumTotalFields = document.getElementById('jejum_total_fields');
        const jejumParcialFields = document.getElementById('jejum_parcial_fields');
        
        if (jejumTotalFields) jejumTotalFields.style.display = 'none';
        if (jejumParcialFields) jejumParcialFields.style.display = 'none';
        
        if (tipoJejum === 'total' && jejumTotalFields) {
            jejumTotalFields.style.display = 'block';
        } else if (tipoJejum === 'parcial' && jejumParcialFields) {
            jejumParcialFields.style.display = 'block';
        }
    }
    
    document.querySelectorAll('input[name="tipo_jejum"]').forEach(radio => {
        radio.addEventListener('change', toggleJejumFields);
    });
    
    toggleJejumFields();
});
</script>
@endsection
