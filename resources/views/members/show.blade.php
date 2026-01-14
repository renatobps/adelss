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
    
    // Buscar turmas do membro
    $memberTurmas = $member->turmas()->with('school')->get();
    
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
            <div class="card-body text-center">
                <i class="bx bx-data fs-1 text-muted"></i>
                <p class="text-muted mb-0">Não há dados disponíveis</p>
            </div>
        </section>

        <!-- Card Ensino -->
        <section class="card mb-3">
            <header class="card-header">
                <h2 class="card-title">Ensino</h2>
            </header>
            <div class="card-body">
                @if($memberTurmas->count() > 0)
                    @foreach($memberTurmas as $turma)
                        <div class="mb-2">
                            <strong>{{ $turma->school->name ?? 'Escola' }}</strong>
                            <span class="badge badge-primary">Aluno(a)</span>
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
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'financeiro' ? 'active' : '' }}" 
                       href="{{ route('members.show', ['member' => $member->id, 'tab' => 'financeiro']) }}">
                        Financeiro
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'editar' ? 'active' : '' }}" 
                       href="{{ route('members.show', ['member' => $member->id, 'tab' => 'editar']) }}">
                        Editar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'permissoes' ? 'active' : '' }}" 
                       href="{{ route('members.show', ['member' => $member->id, 'tab' => 'permissoes']) }}">
                        Permissões
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger {{ $activeTab === 'remover' ? 'active' : '' }}" 
                       href="{{ route('members.show', ['member' => $member->id, 'tab' => 'remover']) }}">
                        <i class="bx bx-trash"></i> Remover
                    </a>
                </li>
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
                @if($activeTab === 'financeiro')
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
                @if($activeTab === 'editar')
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
                @if($activeTab === 'permissoes')
                <div class="tab-pane active">
                    <div class="p-3">
                        <p class="text-muted">A página de permissões será implementada posteriormente.</p>
                    </div>
                </div>
                @endif

                <!-- Aba Remover -->
                @if($activeTab === 'remover')
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
@endsection
