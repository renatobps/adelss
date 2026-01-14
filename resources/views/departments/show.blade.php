@extends('layouts.porto')

@section('title', 'Visualizar Departamento')

@section('page-title', $department->name)

@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><span>Visualizar</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header" style="background: linear-gradient(135deg, {{ $department->color ?? '#667eea' }} 0%, {{ $department->color ?? '#764ba2' }} 100%); color: white; position: relative; overflow: hidden;">
                <div style="position: relative; z-index: 1; text-align: center; padding: 2rem 0;">
                    <div class="mb-3">
                        @if($department->icon)
                            <i class="{{ $department->icon }}" style="font-size: 4rem; color: white;"></i>
                        @else
                            <i class="bx bx-group" style="font-size: 4rem; color: white;"></i>
                        @endif
                    </div>
                    <h2 class="mb-0" style="color: white;">{{ $department->name }}</h2>
                    <p class="mb-0" style="color: rgba(255,255,255,0.9);">
                        @if($department->status == 'ativo')
                            <span class="badge badge-success">Ativo</span>
                        @else
                            <span class="badge badge-secondary">Arquivado</span>
                        @endif
                    </p>
                </div>
            </header>
            <div class="card-body">
                <div class="d-flex justify-content-end mb-4">
                    <a href="{{ route('departments.edit', $department) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('departments.index') }}" class="btn btn-default">
                        <i class="bx bx-arrow-back me-2"></i>Voltar
                    </a>
                </div>

                @if($department->description)
                    <div class="mb-4">
                        <h5>Sobre o departamento</h5>
                        <p>{{ $department->description }}</p>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <section class="card">
                            <header class="card-header">
                                <h2 class="card-title">Líderes</h2>
                            </header>
                            <div class="card-body">
                                @if($department->leaders && $department->leaders->count() > 0)
                                    @foreach($department->leaders as $leader)
                                        <div class="d-flex align-items-center mb-3">
                                            @if($leader->photo_url)
                                                <img src="{{ $leader->photo_url }}" 
                                                     alt="{{ $leader->name }}" 
                                                     class="rounded-circle me-3" 
                                                     width="60" 
                                                     height="60"
                                                     style="object-fit: cover;">
                                            @else
                                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                     style="width: 60px; height: 60px;">
                                                    <i class="bx bx-user text-white" style="font-size: 2rem;"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <h5 class="mb-0">{{ $leader->name }}</h5>
                                                <p class="text-muted mb-0">{{ $leader->email ?? '-' }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                @elseif($department->leader)
                                    {{-- Fallback para compatibilidade com leader_id antigo --}}
                                    <div class="d-flex align-items-center">
                                        @if($department->leader->photo_url)
                                            <img src="{{ $department->leader->photo_url }}" 
                                                 alt="{{ $department->leader->name }}" 
                                                 class="rounded-circle me-3" 
                                                 width="60" 
                                                 height="60"
                                                 style="object-fit: cover;">
                                        @else
                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="bx bx-user text-white" style="font-size: 2rem;"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h5 class="mb-0">{{ $department->leader->name }}</h5>
                                            <p class="text-muted mb-0">{{ $department->leader->email ?? '-' }}</p>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted">Nenhum líder definido</p>
                                @endif
                            </div>
                        </section>
                    </div>

                    <div class="col-md-6 mb-4">
                        <section class="card">
                            <header class="card-header">
                                <h2 class="card-title">Informações</h2>
                            </header>
                            <div class="card-body">
                                <p><strong>Status:</strong> 
                                    @if($department->status == 'ativo')
                                        <span class="badge badge-success">Ativo</span>
                                    @else
                                        <span class="badge badge-secondary">Arquivado</span>
                                    @endif
                                </p>
                                <p><strong>Template:</strong> {{ $department->template ?? 'Personalizado' }}</p>
                                <p><strong>Total de Membros:</strong> {{ $department->members->count() }}</p>
                                <p><strong>Total de Cargos:</strong> {{ $department->roles->count() }}</p>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <section class="card">
                            <header class="card-header">
                                <h2 class="card-title">Participantes ({{ $department->members->count() }})</h2>
                            </header>
                            <div class="card-body">
                                @if($department->members->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Nome</th>
                                                    <th>Cargo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($department->members as $member)
                                                    <tr>
                                                        <td>{{ $member->name }}</td>
                                                        <td>
                                                            @php
                                                                $pivot = isset($pivots) ? $pivots->get($member->id) : null;
                                                                $roleName = '-';
                                                                if ($pivot && $pivot->role) {
                                                                    $roleName = $pivot->role->name;
                                                                } elseif ($pivot && $pivot->department_role_id) {
                                                                    $role = \App\Models\DepartmentRole::find($pivot->department_role_id);
                                                                    $roleName = $role ? $role->name : '-';
                                                                }
                                                            @endphp
                                                            <span class="badge badge-info">{{ $roleName }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted text-center">*Sem participantes</p>
                                @endif
                            </div>
                        </section>
                    </div>

                    <div class="col-md-6 mb-4">
                        <section class="card">
                            <header class="card-header">
                                <h2 class="card-title">Cargos/funções ({{ $department->roles->count() }})</h2>
                            </header>
                            <div class="card-body">
                                @if($department->roles->count() > 0)
                                    <ul class="list-unstyled">
                                        @foreach($department->roles as $role)
                                            <li class="mb-2 p-2" style="background-color: #f8f9fa; border-radius: 4px;">
                                                <strong>{{ $role->name }}</strong>
                                                @if($role->is_default)
                                                    <span class="badge badge-secondary">Padrão</span>
                                                @endif
                                                @if($role->description)
                                                    <br><small class="text-muted">{{ $role->description }}</small>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted text-center">Nenhum cargo definido</p>
                                @endif
                            </div>
                        </section>
                    </div>
                </div>

                <div class="mt-3">
                    <small class="text-muted">
                        <strong>Cadastrado em:</strong> {{ $department->created_at->format('d/m/Y H:i') }}<br>
                        <strong>Última atualização:</strong> {{ $department->updated_at->format('d/m/Y H:i') }}
                    </small>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

