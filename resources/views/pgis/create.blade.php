@extends('layouts.porto')

@section('title', 'Adicionar PGI')

@section('page-title', 'Adicionar PGI')

@section('breadcrumbs')
    <li><a href="{{ route('pgis.index') }}">PGIs</a></li>
    <li><span>Adicionar PGI</span></li>
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
                    <i class="bx bx-group me-2"></i>Adicionar PGI
                </h2>
                <p class="card-subtitle">Cadastre um novo PGI no sistema</p>
            </header>
            <div class="card-body">
                <form action="{{ route('pgis.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <!-- Seção: Informações -->
                        <div class="col-md-6">
                            <div class="card mb-4" style="border: 1px solid #e0e0e0;">
                                <header class="card-header" style="background-color: #f8f9fa;">
                                    <h3 class="card-title mb-0">
                                        <i class="bx bx-user me-2"></i>Informações
                                    </h3>
                                </header>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nome do PGI <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               id="name" name="name" value="{{ old('name') }}" 
                                               placeholder="Digite o nome do PGI" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="logo" class="form-label">Logo do PGI</label>
                                        <input type="file" class="form-control @error('logo') is-invalid @enderror" 
                                               id="logo" name="logo" accept="image/*">
                                        <small class="form-text text-muted">Formatos aceitos: JPEG, PNG, JPG, GIF, SVG. Tamanho máximo: 2MB.</small>
                                        @error('logo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div id="logoPreview" class="mt-2" style="display: none;">
                                            <img id="logoPreviewImg" src="" alt="Preview do logo" style="max-width: 150px; max-height: 150px; border-radius: 50%; border: 2px solid #ddd;">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="banner" class="form-label">Banner do PGI</label>
                                        <input type="file" class="form-control @error('banner') is-invalid @enderror" 
                                               id="banner" name="banner" accept="image/*">
                                        <small class="form-text text-muted">Formatos aceitos: JPEG, PNG, JPG, GIF, SVG. Tamanho máximo: 2MB.</small>
                                        @error('banner')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div id="bannerPreview" class="mt-2" style="display: none;">
                                            <img id="bannerPreviewImg" src="" alt="Preview do banner" style="max-width: 100%; max-height: 200px; border-radius: 4px; border: 2px solid #ddd;">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="opening_date" class="form-label">Data de abertura</label>
                                        <input type="date" class="form-control @error('opening_date') is-invalid @enderror" 
                                               id="opening_date" name="opening_date" value="{{ old('opening_date') }}">
                                        @error('opening_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="day_of_week" class="form-label">Dia da semana</label>
                                        <select class="form-select @error('day_of_week') is-invalid @enderror" 
                                                id="day_of_week" name="day_of_week">
                                            <option value="">Selecione...</option>
                                            <option value="segunda" {{ old('day_of_week') == 'segunda' ? 'selected' : '' }}>Segunda-feira</option>
                                            <option value="terça" {{ old('day_of_week') == 'terça' ? 'selected' : '' }}>Terça-feira</option>
                                            <option value="quarta" {{ old('day_of_week') == 'quarta' ? 'selected' : '' }}>Quarta-feira</option>
                                            <option value="quinta" {{ old('day_of_week') == 'quinta' ? 'selected' : '' }}>Quinta-feira</option>
                                            <option value="sexta" {{ old('day_of_week') == 'sexta' ? 'selected' : '' }}>Sexta-feira</option>
                                            <option value="sábado" {{ old('day_of_week') == 'sábado' ? 'selected' : '' }}>Sábado</option>
                                            <option value="domingo" {{ old('day_of_week') == 'domingo' ? 'selected' : '' }}>Domingo</option>
                                        </select>
                                        @error('day_of_week')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="profile" class="form-label">Perfil</label>
                                        <select class="form-select @error('profile') is-invalid @enderror" 
                                                id="profile" name="profile">
                                            <option value="">Selecione...</option>
                                            <option value="Masculino" {{ old('profile') == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                                            <option value="Feminino" {{ old('profile') == 'Feminino' ? 'selected' : '' }}>Feminino</option>
                                            <option value="Misto" {{ old('profile') == 'Misto' ? 'selected' : '' }}>Misto</option>
                                        </select>
                                        @error('profile')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="time_schedule" class="form-label">Horário</label>
                                        <select class="form-select @error('time_schedule') is-invalid @enderror" 
                                                id="time_schedule" name="time_schedule">
                                            <option value="">Selecione...</option>
                                            <option value="Manhã" {{ old('time_schedule') == 'Manhã' ? 'selected' : '' }}>Manhã</option>
                                            <option value="Tarde" {{ old('time_schedule') == 'Tarde' ? 'selected' : '' }}>Tarde</option>
                                            <option value="Noite" {{ old('time_schedule') == 'Noite' ? 'selected' : '' }}>Noite</option>
                                        </select>
                                        @error('time_schedule')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seção: Liderança -->
                        <div class="col-md-6">
                            <div class="card mb-4" style="border: 1px solid #e0e0e0;">
                                <header class="card-header" style="background-color: #f8f9fa;">
                                    <h3 class="card-title mb-0">
                                        <i class="bx bx-user-pin me-2"></i>Liderança
                                    </h3>
                                </header>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="leader_1_id" class="form-label">Líder 1</label>
                                        <select class="form-select @error('leader_1_id') is-invalid @enderror" 
                                                id="leader_1_id" name="leader_1_id">
                                            <option value="">Nenhum</option>
                                            @foreach($members as $member)
                                                <option value="{{ $member->id }}" {{ old('leader_1_id') == $member->id ? 'selected' : '' }}>
                                                    {{ $member->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('leader_1_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="leader_2_id" class="form-label">Líder 2</label>
                                        <select class="form-select @error('leader_2_id') is-invalid @enderror" 
                                                id="leader_2_id" name="leader_2_id">
                                            <option value="">Nenhum</option>
                                            @foreach($members as $member)
                                                <option value="{{ $member->id }}" {{ old('leader_2_id') == $member->id ? 'selected' : '' }}>
                                                    {{ $member->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('leader_2_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="leader_training_1_id" class="form-label">Líder em treinamento 1</label>
                                        <select class="form-select @error('leader_training_1_id') is-invalid @enderror" 
                                                id="leader_training_1_id" name="leader_training_1_id">
                                            <option value="">Nenhum</option>
                                            @foreach($members as $member)
                                                <option value="{{ $member->id }}" {{ old('leader_training_1_id') == $member->id ? 'selected' : '' }}>
                                                    {{ $member->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('leader_training_1_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="leader_training_2_id" class="form-label">Líder em treinamento 2</label>
                                        <select class="form-select @error('leader_training_2_id') is-invalid @enderror" 
                                                id="leader_training_2_id" name="leader_training_2_id">
                                            <option value="">Nenhum</option>
                                            @foreach($members as $member)
                                                <option value="{{ $member->id }}" {{ old('leader_training_2_id') == $member->id ? 'selected' : '' }}>
                                                    {{ $member->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('leader_training_2_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seção: Endereço -->
                        <div class="col-md-6">
                            <div class="card mb-4" style="border: 1px solid #e0e0e0;">
                                <header class="card-header" style="background-color: #f8f9fa;">
                                    <h3 class="card-title mb-0">
                                        <i class="bx bx-home me-2"></i>Endereço
                                    </h3>
                                </header>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Endereço</label>
                                        <input type="text" class="form-control @error('address') is-invalid @enderror" 
                                               id="address" name="address" value="{{ old('address') }}" 
                                               placeholder="Digite o endereço">
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="neighborhood" class="form-label">Bairro</label>
                                        <input type="text" class="form-control @error('neighborhood') is-invalid @enderror" 
                                               id="neighborhood" name="neighborhood" value="{{ old('neighborhood') }}" 
                                               placeholder="Digite o bairro">
                                        @error('neighborhood')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="number" class="form-label">Número</label>
                                        <input type="text" class="form-control @error('number') is-invalid @enderror" 
                                               id="number" name="number" value="{{ old('number') }}" 
                                               placeholder="Digite o número">
                                        @error('number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seção: Anotações -->
                        <div class="col-md-6">
                            <div class="card mb-4" style="border: 1px solid #e0e0e0;">
                                <header class="card-header" style="background-color: #f8f9fa;">
                                    <h3 class="card-title mb-0">
                                        <i class="bx bx-edit me-2"></i>Anotações
                                    </h3>
                                </header>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Observações</label>
                                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                                  id="notes" name="notes" rows="8" 
                                                  placeholder="Digite observações sobre o PGI...">{{ old('notes') }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-2"></i>Salvar
                        </button>
                        <a href="{{ route('pgis.index') }}" class="btn btn-default">
                            <i class="bx bx-arrow-back me-2"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

@push('styles')
<style>
    .card-header h3 {
        font-size: 1.1rem;
        font-weight: 600;
    }
    .form-control:focus, .form-select:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
</style>
@endpush

@push('scripts')
<script>
    // Preview do logo
    document.getElementById('logo')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logoPreview').style.display = 'block';
                document.getElementById('logoPreviewImg').src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('logoPreview').style.display = 'none';
        }
    });

    // Preview do banner
    document.getElementById('banner')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('bannerPreview').style.display = 'block';
                document.getElementById('bannerPreviewImg').src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('bannerPreview').style.display = 'none';
        }
    });
</script>
@endpush
@endsection

