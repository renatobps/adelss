@php
    $currentDepartment = request()->route('department');
    $items = [];
    if ($currentDepartment instanceof App\Models\Department) {
        if (Auth::user()?->can('view', $currentDepartment)) {
            $items = [
                ['label' => 'Visão geral', 'icon' => 'bx bx-grid-alt', 'url' => route('departments.show', $currentDepartment), 'active' => request()->routeIs('departments.show')],
                ['label' => 'Administração', 'icon' => 'bx bx-group', 'url' => route('departments.administration.index', $currentDepartment), 'active' => request()->routeIs('departments.administration.*')],
                ['label' => 'Projetos', 'icon' => 'bx bx-folder', 'url' => route('departments.projects.index', $currentDepartment), 'active' => request()->routeIs('departments.projects.*')],
                ['label' => 'Calendário anual', 'icon' => 'bx bx-calendar', 'url' => route('departments.events.index', $currentDepartment), 'active' => request()->routeIs('departments.events.*') && !request()->routeIs('departments.events.notifications.*')],
            ];
        }
        if (Auth::user()?->can('update', $currentDepartment)) {
            $items[] = ['label' => 'Notificações', 'icon' => 'bx bx-bell', 'url' => route('departments.notifications.index', $currentDepartment), 'active' => request()->routeIs('departments.notifications.*', 'departments.events.notifications.*')];
            $items[] = ['label' => 'Configurações', 'icon' => 'bx bx-slider-alt', 'url' => route('departments.edit', $currentDepartment), 'active' => request()->routeIs('departments.edit')];
        }
    }
@endphp
@if(count($items))
    <div class="dept-navigation">
        @can('viewAny', App\Models\Department::class)
            <a href="{{ route('departments.index') }}" class="dept-navigation-back"><i class="bx bx-chevron-left" aria-hidden="true"></i>Departamentos</a>
        @endcan
        <nav aria-label="Navegação do departamento">
            @foreach($items as $item)
                <a href="{{ $item['url'] }}" @if($item['active']) aria-current="page" @endif><i class="{{ $item['icon'] }}" aria-hidden="true"></i>{{ $item['label'] }}</a>
            @endforeach
        </nav>
    </div>
@endif
