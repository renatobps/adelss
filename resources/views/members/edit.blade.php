@extends('layouts.porto')

@section('title', 'Editar Membro')

@section('page-title', 'Editar Membro')

@section('breadcrumbs')
    <li><a href="{{ route('members.index') }}">Membros</a></li>
    <li><span>Editar</span></li>
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
                    <i class="bx bx-edit me-2"></i>Editar Membro
                </h2>
                <p class="card-subtitle">Edite as informações do membro</p>
            </header>
            <div class="card-body">
                <form action="{{ route('members.update', $member) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h5 class="border-bottom pb-2">Informações Básicas</h5>
                        </div>

                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $member->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" name="status" required>
                                    <option value="">Selecione...</option>
                                    <option value="ativo" {{ old('status', $member->status) == 'ativo' ? 'selected' : '' }}>Ativo</option>
                                    <option value="inativo" {{ old('status', $member->status) == 'inativo' ? 'selected' : '' }}>Inativo</option>
                                    <option value="visitante" {{ old('status', $member->status) == 'visitante' ? 'selected' : '' }}>Visitante</option>
                                    <option value="membro_transferido" {{ old('status', $member->status) == 'membro_transferido' ? 'selected' : '' }}>Membro Transferido</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email', $member->email) }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telefone</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', $member->phone) }}" 
                                       placeholder="(00) 00000-0000">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="gender" class="form-label">Gênero</label>
                                <select class="form-select @error('gender') is-invalid @enderror" 
                                        id="gender" name="gender">
                                    <option value="">Selecione...</option>
                                    <option value="M" {{ old('gender', $member->gender) == 'M' ? 'selected' : '' }}>Masculino</option>
                                    <option value="F" {{ old('gender', $member->gender) == 'F' ? 'selected' : '' }}>Feminino</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="marital_status" class="form-label">Estado Civil</label>
                                <select class="form-select @error('marital_status') is-invalid @enderror" 
                                        id="marital_status" name="marital_status">
                                    <option value="">Selecione...</option>
                                    <option value="solteiro" {{ old('marital_status', $member->marital_status) == 'solteiro' ? 'selected' : '' }}>Solteiro(a)</option>
                                    <option value="casado" {{ old('marital_status', $member->marital_status) == 'casado' ? 'selected' : '' }}>Casado(a)</option>
                                    <option value="divorciado" {{ old('marital_status', $member->marital_status) == 'divorciado' ? 'selected' : '' }}>Divorciado(a)</option>
                                    <option value="viuvo" {{ old('marital_status', $member->marital_status) == 'viuvo' ? 'selected' : '' }}>Viúvo(a)</option>
                                    <option value="uniao_estavel" {{ old('marital_status', $member->marital_status) == 'uniao_estavel' ? 'selected' : '' }}>União Estável</option>
                                </select>
                                @error('marital_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="birth_date" class="form-label">Data de Nascimento</label>
                                <input type="date" class="form-control @error('birth_date') is-invalid @enderror" 
                                       id="birth_date" name="birth_date" 
                                       value="{{ old('birth_date', $member->birth_date?->format('Y-m-d')) }}">
                                @error('birth_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="membership_date" class="form-label">Data de Membresia</label>
                                <input type="date" class="form-control @error('membership_date') is-invalid @enderror" 
                                       id="membership_date" name="membership_date" 
                                       value="{{ old('membership_date', $member->membership_date?->format('Y-m-d')) }}">
                                @error('membership_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12 mb-3 mt-3">
                            <h5 class="border-bottom pb-2">Associações</h5>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="departments" class="form-label">Departamentos</label>
                                <select class="form-select @error('departments') is-invalid @enderror" 
                                        id="departments" name="departments[]" multiple size="5">
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ in_array($department->id, old('departments', $member->departments->pluck('id')->toArray())) ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Segure Ctrl (ou Cmd no Mac) para selecionar múltiplos departamentos</small>
                                @error('departments')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12 mb-3 mt-3">
                            <h5 class="border-bottom pb-2">Documentos</h5>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cpf" class="form-label">CPF</label>
                                <input type="text" class="form-control @error('cpf') is-invalid @enderror" 
                                       id="cpf" name="cpf" value="{{ old('cpf', $member->cpf) }}" 
                                       placeholder="000.000.000-00">
                                @error('cpf')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rg" class="form-label">RG</label>
                                <input type="text" class="form-control @error('rg') is-invalid @enderror" 
                                       id="rg" name="rg" value="{{ old('rg', $member->rg) }}">
                                @error('rg')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12 mb-3 mt-3">
                            <h5 class="border-bottom pb-2">Endereço</h5>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="address" class="form-label">Endereço</label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror" 
                                       id="address" name="address" value="{{ old('address', $member->address) }}" 
                                       placeholder="Rua, número, complemento">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="city" class="form-label">Cidade</label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                       id="city" name="city" value="{{ old('city', $member->city) }}">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="state" class="form-label">Estado</label>
                                <input type="text" class="form-control @error('state') is-invalid @enderror" 
                                       id="state" name="state" value="{{ old('state', $member->state) }}" 
                                       placeholder="UF" maxlength="2">
                                @error('state')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="zip_code" class="form-label">CEP</label>
                                <input type="text" class="form-control @error('zip_code') is-invalid @enderror" 
                                       id="zip_code" name="zip_code" value="{{ old('zip_code', $member->zip_code) }}" 
                                       placeholder="00000-000">
                                @error('zip_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12 mb-3 mt-3">
                            <h5 class="border-bottom pb-2">Outras Informações</h5>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="role_id" class="form-label">Cargo</label>
                                <select class="form-select @error('role_id') is-invalid @enderror" 
                                        id="role_id" name="role_id">
                                    <option value="">Selecione um cargo...</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ old('role_id', $member->role_id) == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Opcional - Você pode criar novos cargos <a href="{{ route('member-roles.create') }}" target="_blank">aqui</a></small>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="photo" class="form-label">Foto</label>
                                @if($member->photo_url)
                                    <div class="mb-2">
                                        <img src="{{ $member->photo_url }}" 
                                             alt="{{ $member->name }}" 
                                             class="img-thumbnail" 
                                             width="150">
                                        <p class="text-muted small mt-1">Foto atual</p>
                                    </div>
                                @endif
                                <input type="file" class="form-control @error('photo') is-invalid @enderror" 
                                       id="photo" name="photo" accept="image/*">
                                <small class="form-text text-muted">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB</small>
                                @error('photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Observações</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" name="notes" rows="3">{{ old('notes', $member->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-2"></i>Atualizar
                        </button>
                        <a href="{{ route('members.index') }}" class="btn btn-default">
                            <i class="bx bx-arrow-back me-2"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection

