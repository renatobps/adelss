@include('departments.partials.styles')
@php
    $editing = $department->exists;
    $rawMembers = old('members', $editing ? $department->members->modelKeys() : []);
    if (is_string($rawMembers)) $rawMembers = json_decode($rawMembers, true) ?: [];
    $protectedIds = $editing ? $department->roles->flatMap(fn ($role) => $role->assigned_members->pluck('id'))->map(fn ($id) => (int) $id)->unique()->values()->all() : [];
    $selectedMemberIds = array_values(array_unique(array_merge(array_map('intval', is_array($rawMembers) ? $rawMembers : []), $protectedIds)));
    $defaultLeaders = $editing ? $department->leaders->modelKeys() : [];
    if (!$defaultLeaders && $editing && $department->leader) $defaultLeaders = [$department->leader->id];
    $rawLeaders = session()->hasOldInput('leaders_present') ? old('leaders', []) : old('leaders', $defaultLeaders);
    $selectedLeaderIds = array_values(array_unique(array_map('intval', is_array($rawLeaders) ? $rawLeaders : [])));
    $color = old('color', $department->color ?: '#3458bd');
    if (!preg_match('/^#[0-9a-f]{6}$/i', $color)) $color = '#3458bd';
    $icons = ['bx-group' => 'Pessoas', 'bx-music' => 'Louvor', 'bx-video-recording' => 'Mídia', 'bx-heart' => 'Serviço', 'bx-book-open' => 'Ensino', 'bx-globe' => 'Missões', 'bx-money' => 'Finanças', 'bx-happy-heart-eyes' => 'Acolhimento'];
    $icon = old('icon', $department->icon ?: 'bx-group');
    if (!array_key_exists($icon, $icons)) $icons[$icon] = 'Ícone atual';
    $logo = $department->logo_url ? asset('storage/'.$department->logo_url) : null;
@endphp
<div class="dept-page" style="--dept-accent: {{ $color }}">
    <header class="dept-hero">
        <span class="dept-avatar">
            <img src="{{ $logo }}" alt="Logo do departamento" data-department-logo data-original="{{ $logo }}" @if(!$logo) hidden @endif>
            <i class="{{ str_starts_with($icon, 'bx-') ? 'bx '.$icon : $icon }}" data-department-icon @if($logo) hidden @endif aria-hidden="true"></i>
        </span>
        <div class="dept-hero-copy">
            <div class="dept-eyebrow">{{ $editing ? 'Configurações do departamento' : 'Comece uma nova equipe' }}</div>
            <h1 data-department-title data-title-default="{{ $department->name ?: 'Novo departamento' }}">{{ old('name') ?: ($department->name ?: 'Novo departamento') }}</h1>
            <p>{{ $editing ? 'Atualize a identidade e organize as pessoas da sua equipe.' : 'Defina os dados do departamento e escolha quem fará parte dele.' }}</p>
        </div>
        @if($editing)
            <a class="btn btn-outline-secondary" href="{{ route('departments.show', $department) }}"><i class="bx bx-show" aria-hidden="true"></i>Visualizar departamento</a>
        @endif
    </header>
    <form id="departmentForm" method="POST" action="{{ $editing ? route('departments.update', $department) : route('departments.store') }}" enctype="multipart/form-data">
        @csrf
        @if($editing) @method('PUT') @endif
        <input type="hidden" name="leaders_present" value="1">
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <section class="dept-panel" aria-labelledby="department-data-title">
                    <div class="dept-panel-heading"><div><h2 id="department-data-title">Dados do departamento</h2><p>As informações que apresentam sua equipe.</p></div></div>
                    <div class="dept-panel-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label">Nome do departamento *</label>
                                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $department->name) }}" maxlength="191" placeholder="Como seu departamento se chama?" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Situação *</label>
                                <select id="status" name="status" class="form-select" required>
                                    <option value="ativo" @selected(old('status', $department->status ?: 'ativo') === 'ativo')>Ativo</option>
                                    <option value="arquivado" @selected(old('status', $department->status) === 'arquivado')>Arquivado</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Sobre o departamento</label>
                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Conte o propósito e o trabalho desta equipe.">{{ old('description', $department->description) }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </section>
                <section class="dept-panel" aria-label="Participantes do departamento">
                    @include('departments.partials.people-picker', ['kind' => 'members', 'pickerTitle' => 'Participantes', 'pickerDescription' => 'As pessoas que fazem parte do departamento.', 'pickerAction' => 'Adicionar ou ajustar participantes', 'emptyText' => 'Sua equipe ainda está vazia. Escolha pessoas no cadastro de membros.', 'selectedIds' => $selectedMemberIds, 'protectedIds' => $protectedIds])
                    @error('members.*')<p class="text-danger px-4">{{ $message }}</p>@enderror
                </section>
                <section class="dept-panel" aria-label="Liderança do departamento">
                    @include('departments.partials.people-picker', ['kind' => 'leaders', 'pickerTitle' => 'Liderança', 'pickerDescription' => 'Escolha os membros que acompanham e lideram a equipe.', 'pickerAction' => 'Escolher líderes', 'emptyText' => 'Nenhum líder selecionado. Você pode escolher mais de uma pessoa.', 'selectedIds' => $selectedLeaderIds, 'protectedIds' => []])
                    @error('leaders.*')<p class="text-danger px-4">{{ $message }}</p>@enderror
                </section>
                <details class="dept-panel dept-details" @if(old('show_on_homepage', $department->show_on_homepage)) open @endif>
                    <summary><i class="bx bx-globe me-2" aria-hidden="true"></i>Visibilidade no site da igreja</summary>
                    <div class="dept-panel-body"><div class="row">@include('departments._homepage-fields', ['department' => $department, 'compact' => true])</div></div>
                </details>
            </div>
            <aside class="col-12 col-xl-4">
                <div class="dept-editor-aside">
                    <section class="dept-panel">
                        <div class="dept-panel-heading"><div><h2>Identidade visual</h2><p>Uma imagem e uma cor para reconhecer a equipe.</p></div></div>
                        <div class="dept-panel-body">
                            <label for="logo" class="form-label">Logo do departamento</label>
                            <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/gif,image/svg+xml" class="form-control @error('logo') is-invalid @enderror">
                            <p class="form-text">JPEG, PNG, GIF ou SVG, com até 2 MB. {{ $editing ? 'Sem enviar uma imagem, o logo atual será mantido.' : '' }}</p>
                            @error('logo')<div class="text-danger small">{{ $message }}</div>@enderror
                            <label for="icon" class="form-label mt-3">Símbolo</label>
                            <select id="icon" name="icon" class="form-select">
                                @foreach($icons as $value => $label)<option value="{{ $value }}" @selected($icon === $value)>{{ $label }}</option>@endforeach
                            </select>
                            <label for="color" class="form-label mt-3">Cor do departamento</label>
                            <input id="color" name="color" type="color" value="{{ $color }}" class="form-control form-control-color w-100">
                            <p class="form-text mb-0">As alterações aparecem na prévia acima.</p>
                        </div>
                    </section>
                    <section class="dept-panel">
                        <div class="dept-panel-heading"><h2>{{ $editing ? 'Organize o departamento' : 'Depois do cadastro' }}</h2></div>
                        <div class="dept-panel-body">
                            @if($editing)
                                <a class="dept-link-card" href="{{ route('departments.administration.index', $department) }}"><i class="bx bx-group" aria-hidden="true"></i><span><strong>Administração</strong><small>Cargos e seus responsáveis</small></span><i class="bx bx-chevron-right" aria-hidden="true"></i></a>
                                <a class="dept-link-card" href="{{ route('departments.projects.index', $department) }}"><i class="bx bx-folder" aria-hidden="true"></i><span><strong>Projetos</strong><small>Iniciativas e ações da equipe</small></span><i class="bx bx-chevron-right" aria-hidden="true"></i></a>
                                <a class="dept-link-card mb-0" href="{{ route('departments.events.index', $department) }}"><i class="bx bx-calendar" aria-hidden="true"></i><span><strong>Calendário anual</strong><small>Eventos e planejamento</small></span><i class="bx bx-chevron-right" aria-hidden="true"></i></a>
                            @else
                                <p class="dept-muted mb-0">Após salvar, você poderá definir a administração, planejar projetos e organizar os eventos do departamento.</p>
                            @endif
                        </div>
                    </section>
                </div>
            </aside>
        </div>
        <footer class="dept-save-bar">
            <span class="dept-dirty" data-dirty-note hidden><i class="bx bx-info-circle" aria-hidden="true"></i> Alterações ainda não salvas</span>
            <div class="dept-save-actions"><a class="btn btn-outline-secondary" href="{{ $editing ? route('departments.show', $department) : route('departments.index') }}">Cancelar</a><button class="btn btn-primary" type="submit"><i class="bx bx-check" aria-hidden="true"></i>{{ $editing ? 'Salvar alterações' : 'Criar departamento' }}</button></div>
        </footer>
    </form>
</div>
@push('scripts')
<script src="{{ asset('js/departments.js') }}?v={{ filemtime(public_path('js/departments.js')) }}"></script>
@endpush
