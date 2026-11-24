@extends('layouts.porto')
@include('departments.partials.workspace-styles')
@section('title', 'Administração — '.$department->name)
@section('page-title', 'Administração do departamento')
@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><a href="{{ route('departments.show', $department) }}">{{ $department->name }}</a></li>
    <li><span>Administração</span></li>
@endsection
@section('content')
<div class="dept-page department-workspace">
    <header class="dept-hero">
        <span class="dept-avatar"><i class="bx bx-group" aria-hidden="true"></i></span>
        <div class="dept-hero-copy"><div class="dept-eyebrow">Pessoas e responsabilidades</div><h1>Administração de {{ $department->name }}</h1><p>Adicione uma ou mais pessoas a cada cargo. A mesma pessoa pode exercer cargos diferentes.</p></div>
    </header>
    <div class="row g-4">
        @forelse($roles as $role)
            @php
                $selectedIds = (string) old('role_id') === (string) $role->id
                    ? old('responsible_member_ids', []) : $role->assigned_members->pluck('id')->map(fn ($id) => (int) $id)->all();
                $selectedIds = array_map('intval', is_array($selectedIds) ? $selectedIds : []);
            @endphp
            <div class="col-12 col-xl-6">
                <section class="dept-panel">
                    <div class="dept-panel-heading"><div><h2>{{ $role->name }}</h2>@if($role->is_default)<span class="dept-tag">Cargo padrão</span>@endif</div><span class="dept-count">{{ $role->assigned_members->count() }} {{ $role->assigned_members->count() === 1 ? 'pessoa' : 'pessoas' }}</span></div>
                    <div class="dept-panel-body">
                        @if($role->responsible_member_id && !$role->responsibleMember)
                            <p class="dept-muted">Membro excluído ou indisponível: o vínculo histórico foi preservado.</p>
                        @endif
                        @can('update', $department)
                            <form method="POST" action="{{ route('departments.administration.update', [$department, $role]) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="role_id" value="{{ $role->id }}">
                                <input type="hidden" name="responsibles_present" value="1">
                                <label for="role-name-{{ $role->id }}" class="form-label">Cargo *</label>
                                <input id="role-name-{{ $role->id }}" name="name" class="form-control mb-3" required maxlength="191" value="{{ (string) old('role_id') === (string) $role->id ? old('name', $role->name) : $role->name }}" @readonly($role->is_default)>
                                <label for="role-description-{{ $role->id }}" class="form-label">Descrição do cargo</label>
                                <textarea id="role-description-{{ $role->id }}" name="description" class="form-control mb-3" rows="2" maxlength="10000">{{ (string) old('role_id') === (string) $role->id ? old('description', $role->description) : $role->description }}</textarea>
                                @include('departments.partials.people-picker', ['kind' => 'role-'.$role->id, 'fieldName' => 'responsible_member_ids', 'pickerTitle' => 'Pessoas neste cargo', 'pickerDescription' => 'Selecione membros do cadastro geral. Você pode escolher mais de uma pessoa.', 'pickerAction' => 'Adicionar ou ajustar pessoas', 'emptyText' => 'Nenhuma pessoa atribuída. Escolha os membros que exercerão este cargo.', 'protectedIds' => []])
                                <div class="d-flex flex-wrap gap-2 mt-3"><button class="btn btn-primary" type="submit">Salvar cargo e pessoas</button>@if(!$role->is_default)<button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#delete-role-{{ $role->id }}">Excluir cargo</button>@endif</div>
                            </form>
                            @if(!$role->is_default)
                                @include('departments.partials.delete-modal', ['modalId' => 'delete-role-'.$role->id, 'deleteTitle' => 'Excluir cargo', 'deleteMessage' => 'Excluir o cargo '.$role->name.'? Os participantes e seus vínculos com o departamento serão preservados.', 'deleteUrl' => route('departments.administration.destroy', [$department, $role])])
                            @endif
                        @else
                            <div class="dept-person-list">
                                @forelse($role->assigned_members as $person)
                                    <div class="dept-person"><span class="dept-avatar dept-avatar--small" aria-hidden="true">{{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}</span><span class="dept-person-name">{{ $person->name }}</span></div>
                                @empty
                                    <p class="dept-muted">Nenhuma pessoa atribuída.</p>
                                @endforelse
                            </div>
                            @if($role->description)<p class="dept-paragraph mt-3 mb-0">{{ $role->description }}</p>@endif
                        @endcan
                    </div>
                </section>
            </div>
        @empty
            <div class="col-12"><p class="dept-empty">Nenhum cargo cadastrado para este departamento.</p></div>
        @endforelse
    </div>
    @can('update', $department)
        <section class="dept-panel">
            <div class="dept-panel-heading"><h2>Cadastrar cargo</h2></div>
            <form method="POST" action="{{ route('departments.administration.store', $department) }}">
                @csrf
                <input type="hidden" name="role_id" value="new"><input type="hidden" name="responsibles_present" value="1">
                <div class="dept-panel-body">
                    <label for="new-role-name" class="form-label">Cargo *</label>
                    <input id="new-role-name" name="name" class="form-control mb-3" required maxlength="191" value="{{ old('role_id') === 'new' ? old('name') : '' }}">
                    <label for="new-role-description" class="form-label">Descrição do cargo</label>
                    <textarea id="new-role-description" name="description" class="form-control mb-3" rows="3" maxlength="10000">{{ old('role_id') === 'new' ? old('description') : '' }}</textarea>
                    @php
                        $newSelectedIds = old('role_id') === 'new' ? old('responsible_member_ids', []) : [];
                        $newSelectedIds = array_map('intval', is_array($newSelectedIds) ? $newSelectedIds : []);
                    @endphp
                    @include('departments.partials.people-picker', ['kind' => 'new-role', 'fieldName' => 'responsible_member_ids', 'pickerTitle' => 'Pessoas neste cargo', 'pickerDescription' => 'Você pode atribuir uma ou mais pessoas agora ou depois.', 'pickerAction' => 'Escolher pessoas', 'emptyText' => 'Escolha os membros que exercerão este cargo.', 'selectedIds' => $newSelectedIds, 'protectedIds' => []])
                    <button class="btn btn-primary mt-3" type="submit">Cadastrar cargo</button>
                </div>
            </form>
        </section>
    @endcan
</div>
@push('scripts')<script src="{{ asset('js/departments.js') }}?v={{ filemtime(public_path('js/departments.js')) }}"></script>@endpush
@endsection
