@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canViewEscolas = $isAdmin || $user?->hasPermission('ensino.escolas.view') || $user?->hasPermission('ensino.escolas.manage');
    $canViewTurmas = $isAdmin
        || $user?->hasPermission('ensino.turmas.view')
        || $user?->hasPermission('ensino.turmas.manage');

    $items = array_values(array_filter([
        [
            'label' => 'Estudos',
            'icon' => 'bx bx-file',
            'url' => route('ensino.estudos.index'),
            'active' => request()->routeIs('ensino.estudos.*'),
        ],
        $canViewEscolas ? [
            'label' => 'Escolas',
            'icon' => 'bx bx-buildings',
            'url' => route('ensino.escolas.index'),
            'active' => request()->routeIs('ensino.escolas.*'),
        ] : null,
        // Turmas: permissão ou qualquer usuário autenticado no módulo (alunos/professores)
        ($canViewTurmas || $user) ? [
            'label' => 'Turmas',
            'icon' => 'bx bx-book-reader',
            'url' => route('ensino.turmas.index'),
            'active' => request()->routeIs('ensino.turmas.*'),
        ] : null,
    ]));
@endphp

@include('partials.module-nav', [
    'title' => 'Ensino',
    'subtitle' => 'Estudos, escolas e turmas',
    'items' => $items,
    'columns' => 3,
])
