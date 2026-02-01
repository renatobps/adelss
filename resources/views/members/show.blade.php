@extends('layouts.porto')

@section('title', 'Perfil do Membro')

@section('page-title', $member->name)

@section('breadcrumbs')
    <li><a href="{{ route('members.index') }}">Membros</a></li>
    <li><span>Perfil</span></li>
@endsection

@section('content')

@php
    $activeTab = request()->get('tab', 'informacoes');
    $nameParts = explode(' ', $member->name);
    $firstName = $nameParts[0] ?? '';
    $lastName = implode(' ', array_slice($nameParts, 1)) ?? '';
    
    // Verificar permissões
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $isOwnProfile = $user && $user->member && $user->member->id === $member->id;
    
    // Verificar permissões específicas
    $canEdit = $isAdmin || $isOwnProfile || 
              ($user && ($user->hasPermission('members.index.edit') || 
                        $user->hasPermission('members.edit') ||
                        $user->hasPermission('members.index.manage')));
    
    // O membro NÃO pode excluir o próprio perfil
    $canDelete = ($isAdmin || 
                 ($user && ($user->hasPermission('members.index.delete') || 
                           $user->hasPermission('members.delete') ||
                           $user->hasPermission('members.index.manage')))) 
                 && !$isOwnProfile;
    
    $canViewFinancial = $isAdmin || $isOwnProfile;
    $canManagePermissions = $isAdmin; // Apenas admin pode gerenciar permissões
    
    // Turmas já vêm do controller: $allTurmas, $memberTurmas, $teacherTurmas
    
    // Calcular percentual de completude do perfil
    $completionFields = [
        'name' => $member->name,
        'email' => $member->email,
        'phone' => $member->phone,
        'birth_date' => $member->birth_date,
        'address' => $member->address,
        'photo_url' => $member->photo_url,
        'role_id' => $member->role_id,
    ];
    $completedFields = count(array_filter($completionFields));
    $completionPercent = round(($completedFields / count($completionFields)) * 100);
@endphp

<div class="row">
    <!-- Coluna Esquerda - Perfil e Cards -->
    <div class="col-lg-4 col-xl-3 mb-4 mb-xl-0">
        <!-- Card Principal do Perfil -->
        <section class="card">
            <div class="card-body">
                <div class="thumb-info mb-3">
                    @if($member->photo_url)
                        <img src="{{ $member->photo_url }}" class="rounded img-fluid" alt="{{ $member->name }}">
                    @else
                        <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fas fa-user text-white" style="font-size: 5rem;"></i>
                        </div>
                    @endif
                    <div class="thumb-info-title">
                        <span class="thumb-info-inner">{{ $member->name }}</span>
                        <span class="thumb-info-type">{{ $member->role->name ?? 'Membro' }}</span>
                    </div>
                </div>

                <!-- Profile Completion -->
                <div class="widget-toggle-expand mb-3">
                    <div class="widget-header">
                        <h5 class="mb-2 font-weight-semibold text-dark">Completude do Perfil</h5>
                        <div class="widget-toggle">+</div>
                    </div>
                    <div class="widget-content-collapsed">
                        <div class="progress progress-xs light">
                            <div class="progress-bar" role="progressbar" aria-valuenow="{{ $completionPercent }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $completionPercent }}%;">
                                {{ $completionPercent }}%
                            </div>
                        </div>
                    </div>
                    <div class="widget-content-expanded">
                        <ul class="simple-todo-list mt-3">
                            <li class="{{ $member->photo_url ? 'completed' : '' }}">Atualizar Foto</li>
                            <li class="{{ $member->name ? 'completed' : '' }}">Alterar Informações Pessoais</li>
                            <li class="{{ $member->email ? 'completed' : '' }}">Adicionar E-mail</li>
                            <li class="{{ $member->role_id ? 'completed' : '' }}">Definir Cargo</li>
                        </ul>
                    </div>
                </div>

                <hr class="dotted short">

                <h5 class="mb-2 mt-3">Sobre</h5>
                <p class="text-2">
                    @if($member->notes)
                        {{ \Illuminate\Support\Str::limit($member->notes, 100) }}
                    @else
                        Nenhuma informação adicional disponível.
                    @endif
                </p>
                @if($member->notes && strlen($member->notes) > 100)
                <div class="clearfix">
                    <a class="text-uppercase text-muted float-end" href="#">{{ __('(Ver Tudo)') }}</a>
                </div>
                @endif

                <hr class="dotted short">

                <!-- Informações de Contato -->
                <div class="mb-3">
                    <h6 class="mb-2">Contato</h6>
                    @if($member->phone)
                        <p class="mb-1"><i class="bx bx-phone me-2"></i>{{ $member->phone }}</p>
                    @endif
                    @if($member->email)
                        <p class="mb-1"><i class="bx bx-envelope me-2"></i>{{ $member->email }}</p>
                    @endif
                    @if($member->address)
                        <p class="mb-1"><i class="bx bx-map me-2"></i>{{ $member->address }}</p>
                    @endif
                </div>
            </div>
        </section>

        <!-- Card PGIs -->
        <section class="card mb-3">
            <header class="card-header">
                <h2 class="card-title">PGIs</h2>
            </header>
            <div class="card-body">
                @if($member->pgi)
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <i class="bx bx-group me-2 text-primary"></i>
                            <a href="{{ route('pgis.show', $member->pgi) }}" class="text-decoration-none fw-bold" style="color: #2c3e50;">
                                {{ $member->pgi->name }}
                            </a>
                        </div>
                        <a href="{{ route('pgis.show', $member->pgi) }}" class="btn btn-sm btn-outline-primary" title="Ver PGI">
                            <i class="bx bx-show"></i>
                        </a>
                    </div>
                @else
                    <div class="text-center">
                        <i class="bx bx-data fs-1 text-muted"></i>
                        <p class="text-muted mb-0">Não há dados disponíveis</p>
                    </div>
                @endif
            </div>
        </section>

        <!-- Card Ensino -->
        <section class="card mb-3">
            <header class="card-header">
                <h2 class="card-title">Ensino</h2>
            </header>
            <div class="card-body">
                @if(isset($allTurmas) && $allTurmas->count() > 0)
                    @foreach($allTurmas as $turma)
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <h6 class="mb-1 font-weight-semibold">
                                        <a href="{{ route('ensino.turmas.show', $turma) }}" class="text-decoration-none fw-bold" style="color: #007bff;">
                                            <i class="bx bx-link-external me-1"></i>{{ $turma->name }}
                                        </a>
                                    </h6>
                                    @if($turma->school)
                                        <p class="text-muted small mb-1">
                                            <i class="bx bx-building me-1"></i>{{ $turma->school->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                @if(isset($memberTurmas) && $memberTurmas->contains('id', $turma->id))
                                    <span class="badge badge-primary">
                                        <i class="bx bx-user me-1"></i>Aluno(a)
                                    </span>
                                @endif
                                @if(isset($teacherTurmas) && $teacherTurmas->contains('id', $turma->id))
                                    <span class="badge badge-info">
                                        <i class="bx bx-chalkboard me-1"></i>Professor(a)
                                    </span>
                                @endif
                                @if($turma->schedule)
                                    <span class="badge badge-secondary">
                                        <i class="bx bx-time me-1"></i>{{ ucfirst($turma->schedule) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted mb-0 text-center">
                        <i class="bx bx-data fs-1 text-muted d-block mb-2"></i>
                        Não há dados disponíveis
                    </p>
                @endif
            </div>
        </section>

        <!-- Card Acompanhamento pessoal -->
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">Acompanhamento pessoal</h2>
            </header>
            <div class="card-body text-center">
                <i class="bx bx-data fs-1 text-muted"></i>
                <p class="text-muted mb-0">Não há dados disponíveis</p>
            </div>
        </section>
    </div>

    <!-- Coluna Central - Conteúdo Principal com Abas -->
    <div class="col-lg-8 col-xl-6">
        <div class="tabs">
            <ul class="nav nav-tabs tabs-primary">
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'informacoes' ? 'active' : '' }}" 
                       href="{{ route('members.show', ['member' => $member->id, 'tab' => 'informacoes']) }}">
                        Informações
                    </a>
                </li>
                @if($canViewFinancial)
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'financeiro' ? 'active' : '' }}" 
                       href="{{ route('members.show', ['member' => $member->id, 'tab' => 'financeiro']) }}">
                        Financeiro
                    </a>
                </li>
                @endif
                @if($canEdit)
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'editar' ? 'active' : '' }}" 
                       href="{{ route('members.show', ['member' => $member->id, 'tab' => 'editar']) }}">
                        Editar
                    </a>
                </li>
                @endif
                @if($canManagePermissions)
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'permissoes' ? 'active' : '' }}" 
                       href="{{ route('members.show', ['member' => $member->id, 'tab' => 'permissoes']) }}">
                        Permissões
                    </a>
                </li>
                @endif
                @if($canDelete)
                <li class="nav-item">
                    <a class="nav-link text-danger {{ $activeTab === 'remover' ? 'active' : '' }}" 
                       href="{{ route('members.show', ['member' => $member->id, 'tab' => 'remover']) }}">
                        <i class="bx bx-trash"></i> Remover
                    </a>
                </li>
                @endif
            </ul>
            <div class="tab-content">
                <!-- Aba Informações -->
                @if($activeTab === 'informacoes')
                <div class="tab-pane active">
                    <div class="p-3">
                        <h4 class="mb-3 font-weight-semibold text-dark">Dados pessoais</h4>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Nome:</strong> {{ $firstName }}</p>
                                <p><strong>Sobrenome:</strong> {{ $lastName }}</p>
                                <p><strong>Data de nascimento:</strong> 
                                    {{ $member->birth_date ? $member->birth_date->format('d/m/Y') . ' (' . $member->age . ' Anos)' : '-' }}</p>
                                <p><strong>Faixa etária:</strong> 
                                    @if($member->age)
                                        @if($member->age < 12) Criança
                                        @elseif($member->age < 18) Adolescente
                                        @elseif($member->age < 60) Adulto
                                        @else Idoso
                                        @endif
                                    @else
                                        -
                                    @endif</p>
                                <p><strong>Sexo:</strong> {{ $member->gender == 'M' ? 'Masculino' : ($member->gender == 'F' ? 'Feminino' : '-') }}</p>
                                <p><strong>Estado civil:</strong> 
                                    @if($member->marital_status)
                                        @switch($member->marital_status)
                                            @case('solteiro')
                                                Solteiro(a)
                                                @break
                                            @case('casado')
                                                Casado(a)
                                                @break
                                            @case('divorciado')
                                                Divorciado(a)
                                                @break
                                            @case('viuvo')
                                                Viúvo(a)
                                                @break
                                            @case('uniao_estavel')
                                                União Estável
                                                @break
                                            @default
                                                {{ ucfirst($member->marital_status) }}
                                        @endswitch
                                    @else
                                        -
                                    @endif
                                </p>
                                <p><strong>Documento 1:</strong> {{ $member->cpf ?? '-' }}</p>
                            </div>
                        </div>

                        <h4 class="mb-3 pt-4 font-weight-semibold text-dark">Contatos</h4>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Telefone 1:</strong> {{ $member->phone ?? '-' }}</p>
                                <p><strong>E-mail:</strong> {{ $member->email ?? '-' }}</p>
                            </div>
                        </div>

                        <h4 class="mb-3 pt-4 font-weight-semibold text-dark">Endereço</h4>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Endereço:</strong> {{ $member->address ?? '-' }}</p>
                                <p><strong>Bairro:</strong> -</p>
                                <p><strong>Número:</strong> -</p>
                                <p><strong>CEP:</strong> {{ $member->zip_code ?? '-' }}</p>
                                <p><strong>Cidade:</strong> {{ $member->city ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Aba Financeiro -->
                @if($activeTab === 'financeiro' && $canViewFinancial)
                <div class="tab-pane active">
                    <div class="p-3">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <a href="#" class="btn btn-success">
                                    <i class="bx bx-plus me-1"></i>Adicionar receita
                                </a>
                                <a href="#" class="btn btn-danger">
                                    <i class="bx bx-plus me-1"></i>Adicionar despesa
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Nome</th>
                                        <th>Categoria</th>
                                        <th>Arquivos</th>
                                        <th>Total</th>
                                        <th>Recibo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                        <td>{{ $transaction->description ?? '-' }}</td>
                                        <td>{{ $transaction->category->name ?? '-' }}</td>
                                        <td>
                                            @if($transaction->attachments && $transaction->attachments->count() > 0)
                                                @foreach($transaction->attachments as $attachment)
                                                    <a href="{{ asset('storage/' . $attachment->file_path) }}" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-primary me-1 mb-1" 
                                                       title="{{ $attachment->file_name }}">
                                                        <i class="bx bx-file"></i> {{ \Illuminate\Support\Str::limit($attachment->file_name, 15) }}
                                                    </a>
                                                @endforeach
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="{{ $transaction->type === 'despesa' ? 'text-danger' : 'text-success' }}">
                                                {{ $transaction->type === 'despesa' ? '-' : '' }}R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                                            </span>
                                            @if($transaction->is_paid)
                                                <i class="bx bx-check-circle text-success ms-1"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('financial.transactions.receipt', $transaction) }}" 
                                               target="_blank" 
                                               class="btn btn-sm btn-info" 
                                               title="Imprimir Recibo">
                                                <i class="bx bx-printer"></i> Recibo
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Nenhuma transação encontrada</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($transactions->hasPages())
                        <div class="mt-3">
                            {{ $transactions->links() }}
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Aba Editar -->
                @if($activeTab === 'editar' && $canEdit)
                <div class="tab-pane active">
                    <form class="p-3" action="{{ route('members.update', $member) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <h4 class="mb-3 font-weight-semibold text-dark">Informações Pessoais</h4>
                        <div class="row mb-4">
                            <div class="mb-3 col-md-6">
                                <label for="name">Nome <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $member->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="email">E-mail</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email', $member->email) }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="mb-3 col-md-6">
                                <label for="phone">Telefone</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', $member->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="birth_date">Data de Nascimento</label>
                                <input type="date" class="form-control @error('birth_date') is-invalid @enderror" 
                                       id="birth_date" name="birth_date" value="{{ old('birth_date', $member->birth_date?->format('Y-m-d')) }}">
                                @error('birth_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="mb-3 col-md-4">
                                <label for="gender">Gênero</label>
                                <select class="form-control @error('gender') is-invalid @enderror" id="gender" name="gender">
                                    <option value="">Selecione</option>
                                    <option value="M" {{ old('gender', $member->gender) == 'M' ? 'selected' : '' }}>Masculino</option>
                                    <option value="F" {{ old('gender', $member->gender) == 'F' ? 'selected' : '' }}>Feminino</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="marital_status">Estado Civil</label>
                                <select class="form-control @error('marital_status') is-invalid @enderror" id="marital_status" name="marital_status">
                                    <option value="">Selecione</option>
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
                            <div class="mb-3 col-md-4">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
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

                        <div class="row mb-4">
                            <div class="mb-3 col-md-4">
                                <label for="department_id">Departamento</label>
                                <select class="form-control @error('department_id') is-invalid @enderror" id="department_id" name="department_id">
                                    <option value="">Selecione um departamento...</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ old('department_id', $member->department_id) == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="role_id">Cargo</label>
                                <select class="form-control @error('role_id') is-invalid @enderror" id="role_id" name="role_id">
                                    <option value="">Nenhum</option>
                                    @foreach(\App\Models\MemberRole::active()->orderBy('name')->get() as $role)
                                        <option value="{{ $role->id }}" {{ old('role_id', $member->role_id) == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="photo">Foto</label>
                                <input type="file" class="form-control @error('photo') is-invalid @enderror" 
                                       id="photo" name="photo" accept="image/*">
                                @error('photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <h4 class="mb-3 mt-4 font-weight-semibold text-dark">Endereço</h4>
                        <div class="row mb-4">
                            <div class="mb-3 col">
                                <label for="address">Endereço</label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror" 
                                       id="address" name="address" value="{{ old('address', $member->address) }}">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="mb-3 col-md-6">
                                <label for="city">Cidade</label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                       id="city" name="city" value="{{ old('city', $member->city) }}">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="state">Estado</label>
                                <input type="text" class="form-control @error('state') is-invalid @enderror" 
                                       id="state" name="state" value="{{ old('state', $member->state) }}" maxlength="2">
                                @error('state')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-2">
                                <label for="zip_code">CEP</label>
                                <input type="text" class="form-control @error('zip_code') is-invalid @enderror" 
                                       id="zip_code" name="zip_code" value="{{ old('zip_code', $member->zip_code) }}">
                                @error('zip_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="mb-3 col-md-6">
                                <label for="cpf">CPF</label>
                                <input type="text" class="form-control @error('cpf') is-invalid @enderror" 
                                       id="cpf" name="cpf" value="{{ old('cpf', $member->cpf) }}">
                                @error('cpf')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="rg">RG</label>
                                <input type="text" class="form-control @error('rg') is-invalid @enderror" 
                                       id="rg" name="rg" value="{{ old('rg', $member->rg) }}">
                                @error('rg')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="mb-3 col">
                                <label for="notes">Observações</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" name="notes" rows="3">{{ old('notes', $member->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @php
                            $user = $member->user;
                        @endphp
                        @if($user)
                        <h4 class="mb-3 mt-4 font-weight-semibold text-dark">Acesso ao Sistema</h4>
                        <div class="row mb-4">
                            <div class="mb-3 col-md-6">
                                <label for="login_email" class="form-label">E-mail de Acesso</label>
                                <input type="email" class="form-control" 
                                       id="login_email" 
                                       value="{{ $user->email }}" 
                                       readonly 
                                       style="background-color: #f8f9fa;">
                                <small class="form-text text-muted">
                                    <i class="bx bx-info-circle me-1"></i>O e-mail de acesso é o mesmo do membro. Para alterar, edite o campo "E-mail" acima.
                                </small>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="new_password" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control @error('new_password') is-invalid @enderror" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="Deixe em branco para manter a senha atual">
                                @error('new_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="bx bx-info-circle me-1"></i>Mínimo de 6 caracteres. Deixe em branco para manter a senha atual.
                                </small>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="mb-3 col-md-6">
                                <label for="new_password_confirmation" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control @error('new_password_confirmation') is-invalid @enderror" 
                                       id="new_password_confirmation" 
                                       name="new_password_confirmation" 
                                       placeholder="Confirme a nova senha">
                                @error('new_password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        @elseif($member->email)
                        <div class="alert alert-info mt-4">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Usuário de acesso será criado automaticamente</strong> ao salvar este formulário com o e-mail informado.
                        </div>
                        @endif

                        <div class="row">
                            <div class="col-md-12 text-end mt-3">
                                <button type="submit" class="btn btn-primary">Salvar</button>
                                <a href="{{ route('members.show', ['member' => $member->id, 'tab' => 'informacoes']) }}" class="btn btn-default">Cancelar</a>
                            </div>
                        </div>
                    </form>
                </div>
                @endif

                <!-- Aba Permissões -->
                @if($activeTab === 'permissoes' && $canManagePermissions)
                <div class="tab-pane active">
                    <div class="p-3">
                        @if(!$member->user)
                            <div class="alert alert-warning">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>Este membro ainda não possui usuário de acesso.</strong><br>
                                Para configurar permissões, é necessário que o membro tenha um e-mail cadastrado. 
                                Vá para a aba <strong>Editar</strong> e defina um e-mail para o membro, depois salve.
                            </div>
                        @else
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('permissions.update', $member) }}">
                                @csrf
                                @method('PUT')

                                {{-- Checkbox Super Administrador --}}
                                <div class="card mb-4 border-warning">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="is_admin" 
                                                   id="is_admin" 
                                                   value="1"
                                                   {{ $user->is_admin ? 'checked' : '' }}
                                                   onchange="toggleAdminPermissions(this)">
                                            <label class="form-check-label fw-bold text-warning" for="is_admin">
                                                SUPER ADMINISTRADOR
                                            </label>
                                            <small class="d-block text-muted mt-1">
                                                Usuário com acesso total ao sistema. Todas as permissões serão ignoradas.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div id="permissions-section" style="{{ $user->is_admin ? 'display:none;' : '' }}">
                                @foreach($modules as $module)
                                    <div class="card mb-4 border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input module-checkbox" 
                                                       type="checkbox" 
                                                       id="module_{{ $module->id }}"
                                                       data-module-id="{{ $module->id }}">
                                                <label class="form-check-label text-white fw-bold" for="module_{{ $module->id }}">
                                                    {{ strtoupper($module->name) }}
                                                </label>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            @php
                                                $moduleChildren = $module->children;
                                            @endphp
                                            
                                            @foreach($moduleChildren as $group)
                                                <div class="mb-3 ps-3 border-start border-2 border-primary">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input group-checkbox" 
                                                               type="checkbox" 
                                                               id="group_{{ $group->id }}"
                                                               data-group-id="{{ $group->id }}"
                                                               data-module-id="{{ $module->id }}">
                                                        <label class="form-check-label fw-bold" for="group_{{ $group->id }}">
                                                            {{ $group->name }}
                                                        </label>
                                                    </div>
                                                    
                                                    @php
                                                        $groupActions = $group->children ?? collect();
                                                    @endphp
                                                    
                                                    @if($groupActions->count() > 0)
                                                        <div class="ms-4 mt-2">
                                                            @foreach($groupActions as $action)
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input action-checkbox" 
                                                                           type="checkbox" 
                                                                           name="permissions[]"
                                                                           id="perm_{{ $action->id }}"
                                                                           value="{{ $action->id }}"
                                                                           data-group-id="{{ $group->id }}"
                                                                           data-module-id="{{ $module->id }}"
                                                                           {{ in_array($action->id, $assignedPermissions) ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="perm_{{ $action->id }}">
                                                                        {{ $action->name }}
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                                </div>

                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i>Salvar Permissões
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Aba Remover -->
                @if($activeTab === 'remover' && $canDelete)
                <div class="tab-pane active">
                    <div class="p-3">
                        <div class="alert alert-danger">
                            <h4 class="alert-heading mb-3">Você tem certeza que deseja remover esta pessoa?</h4>
                            <p class="mb-4">Esta ação não poderá ser revertida depois.</p>
                            <form action="{{ route('members.destroy', $member) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-lg" onclick="return confirm('Tem certeza? Esta ação é irreversível!');">
                                    Sim, eu tenho certeza
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Coluna Direita - Estatísticas e Informações -->
    <div class="col-xl-3">
        <h4 class="mb-3 mt-0 font-weight-semibold text-dark">Estatísticas Financeiras</h4>
        <ul class="simple-card-list mb-3">
            <li class="primary">
                <h3>R$ {{ number_format($totalDizimo, 2, ',', '.') }}</h3>
                <p class="text-light">Total Dízimo</p>
            </li>
            <li class="primary">
                <h3>R$ {{ number_format($totalOferta, 2, ',', '.') }}</h3>
                <p class="text-light">Total Oferta</p>
            </li>
            <li class="primary">
                <h3>{{ $transactions->total() }}</h3>
                <p class="text-light">Total Transações</p>
            </li>
        </ul>

        <!-- Card de Escalas do Voluntário -->
        @if(isset($upcomingSchedules) && $upcomingSchedules->count() > 0)
        <section class="card mb-3" id="escalas-card">
            <header class="card-header">
                <h2 class="card-title">
                    <i class="bx bx-calendar-check me-2"></i>Minhas Escalas
                </h2>
            </header>
            <div class="card-body">
                @foreach($upcomingSchedules as $item)
                    @if($item['type'] === 'normal')
                        @php
                            $schedule = $item['schedule'];
                            $serviceArea = $item['serviceArea'];
                            $scheduleVolunteer = $item['scheduleVolunteer'];
                        @endphp
                        <div class="border rounded p-3 mb-3 {{ $scheduleVolunteer->status === 'confirmado' ? 'border-success' : '' }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1 font-weight-semibold">{{ $schedule->title }}</h6>
                                    <p class="text-muted mb-1 small">
                                        <i class="bx bx-calendar me-1"></i>{{ $schedule->date->format('d/m/Y') }}
                                        @if($schedule->start_time)
                                            <i class="bx bx-time me-1 ms-2"></i>{{ is_object($schedule->start_time) ? $schedule->start_time->format('H:i') : \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}
                                        @endif
                                    </p>
                                    <p class="mb-0">
                                        <span class="badge badge-info">{{ $serviceArea->name ?? 'Área não definida' }}</span>
                                        @if($scheduleVolunteer->status === 'confirmado')
                                            <span class="badge badge-success ms-2">
                                                <i class="bx bx-check-circle me-1"></i>Confirmado
                                            </span>
                                        @else
                                            <span class="badge badge-warning ms-2">Pendente</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @if($scheduleVolunteer->status !== 'confirmado')
                                <form action="{{ route('voluntarios.escalas.volunteers.confirm', $scheduleVolunteer) }}" method="POST" class="mt-2 confirm-presence-form">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-check me-1"></i>Confirmar Presença
                                    </button>
                                </form>
                            @endif
                        </div>
                    @elseif($item['type'] === 'monthly')
                        @php
                            $monthlySchedule = $item['schedule'];
                            $serviceArea = $item['serviceArea'];
                            $pivot = $item['pivot'];
                            $pivotStatus = $pivot->status ?? 'pendente';
                        @endphp
                        <div class="border rounded p-3 mb-3 {{ $pivotStatus === 'confirmado' ? 'border-success' : '' }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1 font-weight-semibold">
                                        {{ $monthlySchedule->event->title ?? 'Culto' }}
                                        <span class="badge badge-primary ms-1">Mensal</span>
                                    </h6>
                                    <p class="text-muted mb-1 small">
                                        <i class="bx bx-calendar me-1"></i>{{ $monthlySchedule->event->start_date->format('d/m/Y') ?? 'N/A' }}
                                        @if($monthlySchedule->event->start_date)
                                            <i class="bx bx-time me-1 ms-2"></i>{{ $monthlySchedule->event->start_date->format('H:i') }}
                                        @endif
                                    </p>
                                    <p class="mb-0">
                                        <span class="badge badge-info">{{ $serviceArea->name ?? 'Área não definida' }}</span>
                                        @if($pivotStatus === 'confirmado')
                                            <span class="badge badge-success ms-2">
                                                <i class="bx bx-check-circle me-1"></i>Confirmado
                                            </span>
                                        @else
                                            <span class="badge badge-warning ms-2">Pendente</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @if($pivotStatus !== 'confirmado')
                                <form action="{{ route('voluntarios.escalas-mensais.volunteers.confirm', $pivot->id) }}" method="POST" class="mt-2 confirm-monthly-presence-form">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-check me-1"></i>Confirmar Presença
                                    </button>
                                </form>
                            @endif
                        </div>
                    @elseif($item['type'] === 'moriah')
                        @php
                            $moriahSchedule = $item['schedule'];
                            $pivot = $item['pivot'];
                            $pivotStatus = $pivot->status ?? 'pendente';
                            
                            // Buscar funções selecionadas do membro nesta escala
                            // Usar o pivot que já vem do controller ou buscar novamente
                            $scheduleMember = $pivot ?? \DB::table('moriah_schedule_members')
                                ->where('moriah_schedule_id', $moriahSchedule->id)
                                ->where('member_id', $member->id)
                                ->first();
                            
                            $selectedFunctions = [];
                            if ($scheduleMember && isset($scheduleMember->id)) {
                                $selectedFunctions = \DB::table('moriah_schedule_member_functions')
                                    ->where('moriah_schedule_member_id', $scheduleMember->id)
                                    ->join('moriah_functions', 'moriah_schedule_member_functions.moriah_function_id', '=', 'moriah_functions.id')
                                    ->pluck('moriah_functions.name')
                                    ->toArray();
                            }
                        @endphp
                        <div class="border rounded p-3 mb-3 {{ $pivotStatus === 'confirmado' ? 'border-success' : '' }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1 font-weight-semibold">
                                        {{ $moriahSchedule->title }}
                                        <span class="badge badge-info ms-1">Moriah</span>
                                    </h6>
                                    <p class="text-muted mb-1 small">
                                        <i class="bx bx-calendar me-1"></i>{{ $moriahSchedule->date->format('d/m/Y') }}
                                        @if($moriahSchedule->time)
                                            <i class="bx bx-time me-1 ms-2"></i>{{ is_object($moriahSchedule->time) ? $moriahSchedule->time->format('H:i') : \Carbon\Carbon::parse($moriahSchedule->time)->format('H:i') }}
                                        @endif
                                    </p>
                                    @if(!empty($selectedFunctions))
                                        <p class="mb-1 small">
                                            <i class="bx bx-music me-1"></i>{{ implode(', ', $selectedFunctions) }}
                                        </p>
                                    @endif
                                    <p class="mb-0">
                                        @if($pivotStatus === 'confirmado')
                                            <span class="badge badge-success">
                                                <i class="bx bx-check-circle me-1"></i>Confirmado
                                            </span>
                                        @elseif($pivotStatus === 'recusado')
                                            <span class="badge badge-danger">
                                                <i class="bx bx-x-circle me-1"></i>Recusado
                                            </span>
                                        @else
                                            <span class="badge badge-warning">Pendente</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @if($moriahSchedule->request_confirmation && $pivotStatus === 'pendente' && $isOwnProfile)
                                @php
                                    // Garantir que temos o ID correto do pivot
                                    $pivotId = isset($pivot->id) ? $pivot->id : (isset($scheduleMember->id) ? $scheduleMember->id : null);
                                @endphp
                                @if($pivotId)
                                <div class="mt-2 d-flex gap-2">
                                    <form action="{{ route('moriah.schedules.members.confirm', $pivotId) }}" method="POST" class="confirm-moriah-presence-form">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="bx bx-check me-1"></i>Confirmar
                                        </button>
                                    </form>
                                    <form action="{{ route('moriah.schedules.members.reject', $pivotId) }}" method="POST" class="reject-moriah-presence-form">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja recusar esta escala?');">
                                            <i class="bx bx-x me-1"></i>Recusar
                                        </button>
                                    </form>
                                </div>
                                @endif
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
        @elseif($volunteer || (isset($moriahSchedules) && $moriahSchedules->count() > 0))
        <section class="card mb-3">
            <header class="card-header">
                <h2 class="card-title">
                    <i class="bx bx-calendar-check me-2"></i>Minhas Escalas
                </h2>
            </header>
            <div class="card-body text-center">
                <i class="bx bx-calendar-x fs-1 text-muted d-block mb-2"></i>
                <p class="text-muted mb-0">Nenhuma escala agendada</p>
            </div>
        </section>
        @endif

        <h4 class="mb-3 mt-4 pt-2 font-weight-semibold text-dark">Informações</h4>
        <ul class="simple-bullet-list mb-3">
            <li class="blue">
                <span class="title">Status</span>
                <span class="description truncate">{{ ucfirst($member->status) }}</span>
            </li>
            @if($member->department)
            <li class="green">
                <span class="title">Departamento</span>
                <span class="description truncate">{{ $member->department->name }}</span>
            </li>
            @endif
            @if($member->pgi)
            <li class="orange">
                <span class="title">PGI</span>
                <span class="description truncate">{{ $member->pgi->name }}</span>
            </li>
            @endif
            @if($member->membership_date)
            <li class="red">
                <span class="title">Membro desde</span>
                <span class="description truncate">{{ $member->membership_date->format('d/m/Y') }}</span>
            </li>
            @endif
        </ul>
    </div>
</div>

@if(isset($pendingSchedulesCount) && $pendingSchedulesCount > 0)
<!-- Modal: Notificação de Escalas Pendentes -->
<div class="modal fade" id="pendingSchedulesModal" tabindex="-1" aria-labelledby="pendingSchedulesModalLabel" aria-hidden="false" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-warning" style="border-width: 3px;">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="pendingSchedulesModalLabel">
                    <i class="bx bx-calendar-exclamation me-2"></i>Atenção: Escalas Pendentes!
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bx bx-calendar-x fs-1 text-warning mb-3"></i>
                    <h4 class="mb-2">Você tem {{ $pendingSchedulesCount }} {{ $pendingSchedulesCount == 1 ? 'escala pendente' : 'escalas pendentes' }} de confirmação!</h4>
                    <p class="text-muted mb-3">Por favor, confirme sua presença nas escalas abaixo para que possamos organizar melhor o serviço.</p>
                </div>
                
                @if($upcomingSchedules->count() > 0)
                <div class="list-group">
                    @foreach($upcomingSchedules->filter(function($item) { 
                        if ($item['type'] === 'normal') {
                            return $item['scheduleVolunteer']->status !== 'confirmado';
                        } elseif ($item['type'] === 'monthly') {
                            return ($item['pivot']->status ?? 'pendente') !== 'confirmado';
                        } else { // moriah
                            return ($item['pivot']->status ?? 'pendente') !== 'confirmado';
                        }
                    }) as $item)
                        @if($item['type'] === 'normal')
                            @php
                                $schedule = $item['schedule'];
                                $serviceArea = $item['serviceArea'];
                            @endphp
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $schedule->title }}</h6>
                                        <p class="mb-1 small text-muted">
                                            <i class="bx bx-calendar me-1"></i>{{ $schedule->date->format('d/m/Y') }}
                                            @if($schedule->start_time)
                                                <i class="bx bx-time me-1 ms-2"></i>{{ is_object($schedule->start_time) ? $schedule->start_time->format('H:i') : \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}
                                            @endif
                                        </p>
                                        <span class="badge badge-info">{{ $serviceArea->name ?? 'Área não definida' }}</span>
                                    </div>
                                </div>
                            </div>
                        @elseif($item['type'] === 'monthly')
                            @php
                                $monthlySchedule = $item['schedule'];
                                $serviceArea = $item['serviceArea'];
                            @endphp
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            {{ $monthlySchedule->event->title ?? 'Culto' }}
                                            <span class="badge badge-primary ms-1">Mensal</span>
                                        </h6>
                                        <p class="mb-1 small text-muted">
                                            <i class="bx bx-calendar me-1"></i>{{ $monthlySchedule->event->start_date->format('d/m/Y') ?? 'N/A' }}
                                            @if($monthlySchedule->event->start_date)
                                                <i class="bx bx-time me-1 ms-2"></i>{{ $monthlySchedule->event->start_date->format('H:i') }}
                                            @endif
                                        </p>
                                        <span class="badge badge-info">{{ $serviceArea->name ?? 'Área não definida' }}</span>
                                    </div>
                                </div>
                            </div>
                        @elseif($item['type'] === 'moriah')
                            @php
                                $moriahSchedule = $item['schedule'];
                            @endphp
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            {{ $moriahSchedule->title }}
                                            <span class="badge badge-info ms-1">Moriah</span>
                                        </h6>
                                        <p class="mb-1 small text-muted">
                                            <i class="bx bx-calendar me-1"></i>{{ $moriahSchedule->date->format('d/m/Y') }}
                                            @if($moriahSchedule->time)
                                                <i class="bx bx-time me-1 ms-2"></i>{{ is_object($moriahSchedule->time) ? $moriahSchedule->time->format('H:i') : \Carbon\Carbon::parse($moriahSchedule->time)->format('H:i') }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Fechar
                </button>
                <button type="button" class="btn btn-primary" id="btn-ver-escalas">
                    <i class="bx bx-calendar-check me-1"></i>Ver Minhas Escalas
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Abrir modal de escalas pendentes automaticamente
    @if(isset($pendingSchedulesCount) && $pendingSchedulesCount > 0)
    const pendingModalElement = document.getElementById('pendingSchedulesModal');
    const pendingModal = new bootstrap.Modal(pendingModalElement, {
        backdrop: 'static',
        keyboard: false
    });
    pendingModal.show();
    
    // Event listener para o botão "Ver Minhas Escalas"
    const btnVerEscalas = document.getElementById('btn-ver-escalas');
    if (btnVerEscalas) {
        btnVerEscalas.addEventListener('click', function() {
            // Fechar o modal primeiro
            pendingModal.hide();
            
            // Aguardar o modal fechar completamente antes de fazer scroll
            pendingModalElement.addEventListener('hidden.bs.modal', function scrollToEscalas() {
                // Remover o listener após usar
                pendingModalElement.removeEventListener('hidden.bs.modal', scrollToEscalas);
                
                // Fazer scroll para o card de escalas
                const escalasCard = document.getElementById('escalas-card');
                if (escalasCard) {
                    setTimeout(function() {
                        escalasCard.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center' 
                        });
                        
                        // Adicionar um destaque visual temporário
                        escalasCard.style.transition = 'box-shadow 0.3s ease';
                        escalasCard.style.boxShadow = '0 0 20px rgba(0, 123, 255, 0.5)';
                        setTimeout(function() {
                            escalasCard.style.boxShadow = '';
                        }, 2000);
                    }, 100);
                }
            }, { once: true });
        });
    }
    @endif
    
    // Interceptar envio do formulário de confirmação de presença (escalas normais)
    const confirmForms = document.querySelectorAll('.confirm-presence-form');
    
    confirmForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formElement = this;
            const submitButton = formElement.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            // Desabilitar botão e mostrar loading
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Confirmando...';
            
            // Criar FormData
            const formData = new FormData(formElement);
            
            // Fazer requisição AJAX
            fetch(formElement.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formElement.querySelector('input[name="_token"]').value,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar visual do card
                    const card = formElement.closest('.border');
                    if (card) {
                        card.classList.add('border-success');
                        card.classList.remove('border');
                    }
                    
                    // Atualizar badge de status
                    const statusBadge = card?.querySelector('.badge-warning');
                    if (statusBadge) {
                        statusBadge.outerHTML = '<span class="badge badge-success ms-2"><i class="bx bx-check-circle me-1"></i>Confirmado</span>';
                    }
                    
                    // Remover formulário
                    formElement.remove();
                    
                    // Mostrar mensagem de sucesso
                    if (typeof showNotification === 'function') {
                        showNotification('success', data.message || 'Presença confirmada com sucesso!');
                    } else {
                        alert(data.message || 'Presença confirmada com sucesso!');
                    }
                    
                    // Recarregar após 1 segundo para atualizar dados
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Erro ao confirmar presença');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                alert('Erro ao confirmar presença. Por favor, tente novamente.');
            });
        });
    });
    
    // Interceptar envio do formulário de confirmação de presença (escalas mensais)
    const confirmMonthlyForms = document.querySelectorAll('.confirm-monthly-presence-form');
    
    confirmMonthlyForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formElement = this;
            const submitButton = formElement.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            // Desabilitar botão e mostrar loading
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Confirmando...';
            
            // Criar FormData
            const formData = new FormData(formElement);
            
            // Fazer requisição AJAX
            fetch(formElement.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formElement.querySelector('input[name="_token"]').value,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar visual do card
                    const card = formElement.closest('.border');
                    if (card) {
                        card.classList.add('border-success');
                        card.classList.remove('border');
                    }
                    
                    // Atualizar badge de status
                    const statusBadge = card?.querySelector('.badge-warning');
                    if (statusBadge) {
                        statusBadge.outerHTML = '<span class="badge badge-success ms-2"><i class="bx bx-check-circle me-1"></i>Confirmado</span>';
                    }
                    
                    // Remover formulário
                    formElement.remove();
                    
                    // Mostrar mensagem de sucesso
                    if (typeof showNotification === 'function') {
                        showNotification('success', data.message || 'Presença confirmada com sucesso!');
                    } else {
                        alert(data.message || 'Presença confirmada com sucesso!');
                    }
                    
                    // Recarregar após 1 segundo para atualizar dados
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Erro ao confirmar presença');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                alert('Erro ao confirmar presença. Por favor, tente novamente.');
            });
        });
    });
    
    // Interceptar envio do formulário de confirmação/recusa de presença (escalas do Moriah)
    const confirmMoriahForms = document.querySelectorAll('.confirm-moriah-presence-form');
    const rejectMoriahForms = document.querySelectorAll('.reject-moriah-presence-form');
    
    confirmMoriahForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formElement = this;
            const submitButton = formElement.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            // Desabilitar botão e mostrar loading
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Confirmando...';
            
            // Criar FormData
            const formData = new FormData(formElement);
            
            // Adicionar _method PUT se não existir
            if (!formData.has('_method')) {
                formData.append('_method', 'PUT');
            }
            
            // Fazer requisição AJAX
            fetch(formElement.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formElement.querySelector('input[name="_token"]').value,
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
                    location.reload();
                } else {
                    throw new Error(data.message || 'Erro ao confirmar presença');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                alert('Erro ao confirmar presença: ' + error.message);
            });
        });
    });
    
    rejectMoriahForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formElement = this;
            const submitButton = formElement.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            // Desabilitar botão e mostrar loading
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Recusando...';
            
            // Criar FormData
            const formData = new FormData(formElement);
            
            // Adicionar _method PUT se não existir
            if (!formData.has('_method')) {
                formData.append('_method', 'PUT');
            }
            
            // Fazer requisição AJAX
            fetch(formElement.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formElement.querySelector('input[name="_token"]').value,
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
                    location.reload();
                } else {
                    throw new Error(data.message || 'Erro ao recusar escala');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                alert('Erro ao recusar escala: ' + error.message);
            });
        });
    });
});

// JavaScript para checkboxes em cascata de permissões
@if($activeTab === 'permissoes' && $canManagePermissions)
// Função para mostrar/ocultar permissões quando Super Admin é marcado (global)
window.toggleAdminPermissions = function(checkbox) {
    const permissionsSection = document.getElementById('permissions-section');
    if (checkbox && checkbox.checked) {
        permissionsSection.style.display = 'none';
        document.querySelectorAll('.action-checkbox').forEach(cb => cb.checked = false);
    } else {
        permissionsSection.style.display = 'block';
    }
};

(function() {

    // Função para atualizar estado dos checkboxes do módulo
    function updateModuleCheckbox(moduleId) {
        const moduleCheckbox = document.getElementById('module_' + moduleId);
        const groupCheckboxes = document.querySelectorAll(`input[data-module-id="${moduleId}"].group-checkbox`);
        const actionCheckboxes = document.querySelectorAll(`input[data-module-id="${moduleId}"].action-checkbox`);
        
        let allChecked = true;
        let someChecked = false;
        
        actionCheckboxes.forEach(cb => {
            if (cb.checked) someChecked = true;
            else allChecked = false;
        });
        
        if (moduleCheckbox) {
            moduleCheckbox.checked = allChecked;
            moduleCheckbox.indeterminate = someChecked && !allChecked;
        }
    }

    // Função para atualizar estado dos checkboxes do grupo
    function updateGroupCheckbox(groupId, moduleId) {
        const groupCheckbox = document.getElementById('group_' + groupId);
        const actionCheckboxes = document.querySelectorAll(`input[data-group-id="${groupId}"].action-checkbox`);
        
        let allChecked = true;
        
        actionCheckboxes.forEach(cb => {
            if (!cb.checked) allChecked = false;
        });
        
        if (groupCheckbox) {
            groupCheckbox.checked = allChecked;
        }
        
        updateModuleCheckbox(moduleId);
    }

    // Event listeners
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('module-checkbox')) {
            const moduleId = e.target.dataset.moduleId;
            const groupCheckboxes = document.querySelectorAll(`input[data-module-id="${moduleId}"].group-checkbox`);
            const actionCheckboxes = document.querySelectorAll(`input[data-module-id="${moduleId}"].action-checkbox`);
            
            groupCheckboxes.forEach(cb => cb.checked = e.target.checked);
            actionCheckboxes.forEach(cb => cb.checked = e.target.checked);
        }
        
        if (e.target.classList.contains('group-checkbox')) {
            const groupId = e.target.dataset.groupId;
            const moduleId = e.target.dataset.moduleId;
            const actionCheckboxes = document.querySelectorAll(`input[data-group-id="${groupId}"].action-checkbox`);
            
            actionCheckboxes.forEach(cb => cb.checked = e.target.checked);
            updateModuleCheckbox(moduleId);
        }
        
        if (e.target.classList.contains('action-checkbox')) {
            const groupId = e.target.dataset.groupId;
            const moduleId = e.target.dataset.moduleId;
            updateGroupCheckbox(groupId, moduleId);
        }
    });

    // Inicializar estados dos checkboxes ao carregar
    document.addEventListener('DOMContentLoaded', function() {
        const memberModules = document.querySelectorAll('.module-checkbox');
        memberModules.forEach(cb => {
            const moduleId = cb.dataset.moduleId;
            updateModuleCheckbox(moduleId);
        });
    });
})();
@endif
</script>
@endpush
@endsection
