<!doctype html>
<html class="fixed">
<head>
    <!-- Basic -->
    <meta charset="UTF-8">
    <title>@yield('title', 'ADELSS Sistema Web')</title>
    <meta name="keywords" content="Sistema ADELSS, Gestão, Membros" />
    <meta name="description" content="ADELSS Sistema Web - Sistema de gestão e administração">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Mobile Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Web Fonts  -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800|Shadows+Into+Light" rel="stylesheet" type="text/css">

    <!-- Vendor CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/vendor/bootstrap/css/bootstrap.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/animate/animate.compat.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/vendor/font-awesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/boxicons/css/boxicons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/magnific-popup/magnific-popup.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/jquery-ui/jquery-ui.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/jquery-ui/jquery-ui.theme.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/bootstrap-multiselect/css/bootstrap-multiselect.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/morris/morris.css') }}" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/css/theme.css') }}" />

    <!-- Skin CSS -->
    <link rel="stylesheet" href="{{ asset('css/css/skins/default.css') }}" />

    <!-- Theme Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/css/custom.css') }}?v={{ @filemtime(public_path('css/css/custom.css')) ?: '1' }}">

    <!-- Head Libs -->
    <script src="{{ asset('vendor/vendor/modernizr/modernizr.js') }}"></script>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/img/LOG SS AZUL.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/img/LOG SS AZUL.png') }}" />
    <link rel="shortcut icon" type="image/png" href="{{ asset('img/img/LOG SS AZUL.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('img/img/LOG SS AZUL.png') }}" />

    @stack('styles')
</head>
<body>
    <section class="body">
        <!-- start: header -->
        <header class="header">
            <div class="logo-container">
                <a href="{{ route('dashboard') }}" class="logo" style="display: flex; align-items: center; padding: 10px 0;">
                    <img src="{{ asset('img/img/LOG SS preta.png') }}" alt="ADELSS" style="max-height: 45px; width: auto; object-fit: contain;" />
                </a>

                <div class="d-md-none toggle-sidebar-left" data-toggle-class="sidebar-left-opened" data-target="html" data-fire-event="sidebar-left-opened">
                    <i class="fas fa-bars" aria-label="Toggle sidebar"></i>
                </div>
            </div>

            <!-- start: search & user box -->
            <div class="header-right">
                <form action="#" class="search nav-form">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" id="q" placeholder="Buscar...">
                        <button class="btn btn-default" type="submit"><i class="bx bx-search"></i></button>
                    </div>
                </form>

                <span class="separator"></span>

                @php
                    $loggedUser = Auth::user();
                    $loggedMember = $loggedUser?->member;
                    $profilePhoto = ($loggedMember && $loggedMember->photo_url)
                        ? $loggedMember->photo_url
                        : asset('img/img/!logged-user.jpg');
                    
                    // Determinar o cargo/role a ser exibido
                    $displayRole = 'Usuário';
                    if ($loggedUser && $loggedUser->is_admin) {
                        $displayRole = 'Administrador';
                    } elseif ($loggedMember) {
                        // Carregar o relacionamento role se ainda não estiver carregado
                        if (!$loggedMember->relationLoaded('role')) {
                            $loggedMember->load('role');
                        }
                        if ($loggedMember->role) {
                            $displayRole = $loggedMember->role->name;
                        }
                    }
                @endphp

                <div id="userbox" class="userbox">
                    <a href="#" data-bs-toggle="dropdown">
                        <figure class="profile-picture">
                            <img src="{{ $profilePhoto }}" alt="{{ $loggedUser->name ?? 'Usuário' }}" class="rounded-circle" data-lock-picture="{{ $profilePhoto }}" />
                        </figure>
                        <div class="profile-info" data-lock-name="{{ $loggedUser->name ?? 'Usuário' }}" data-lock-email="{{ $loggedUser->email ?? 'usuario@adelss.com' }}">
                            <span class="name">{{ $loggedUser->name ?? 'Usuário' }}</span>
                            <span class="role">{{ $displayRole }}</span>
                        </div>

                        <i class="fa custom-caret"></i>
                    </a>

                    <div class="dropdown-menu">
                        <ul class="list-unstyled mb-2">
                            <li class="divider"></li>
                            <li>
                                <a role="menuitem" tabindex="-1" href="{{ $loggedMember ? route('members.show', $loggedMember) : '#' }}">
                                    <i class="bx bx-user-circle"></i> Meu Perfil
                                </a>
                            </li>
                            <li>
                                <a role="menuitem" tabindex="-1" href="#"><i class="bx bx-lock"></i> Bloquear Tela</a>
                            </li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger" style="border: none; background: none; width: 100%; text-align: left; padding: 0.5rem 1rem; color: inherit;">
                                        <i class="bx bx-power-off"></i> Sair
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- end: search & user box -->
        </header>
        <!-- end: header -->

        <div class="inner-wrapper">
            <!-- start: sidebar -->
            <aside id="sidebar-left" class="sidebar-left">
                <div class="sidebar-header">
                    <div class="sidebar-title">
                        Navegação
                    </div>
                    <div class="sidebar-toggle d-none d-md-block" data-toggle-class="sidebar-left-collapsed" data-target="html" data-fire-event="sidebar-left-toggle">
                        <i class="fas fa-bars" aria-label="Toggle sidebar"></i>
                    </div>
                </div>

                <div class="nano">
                    <div class="nano-content">
                        <nav id="menu" class="nav-main" role="navigation">
                            <ul class="nav nav-main">
                                <li class="{{ request()->routeIs('dashboard') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ route('dashboard') }}">
                                        <i class="bx bx-home-alt" aria-hidden="true"></i>
                                        <span>Visão Geral</span>
                                    </a>
                                </li>
                                
                                @php
                                    $user = Auth::user();
                                    $isAdmin = $user?->is_admin ?? false;
                                    $member = $user?->member;
                                    $hasPgi = $member && $member->pgi_id;
                                    
                                    // Verificar se é líder ou líder em treinamento de algum PGI
                                    $isLeaderOfPgi = false;
                                    if ($member) {
                                        $isLeaderOfPgi = \App\Models\Pgi::where(function($q) use ($member) {
                                            $q->where('leader_1_id', $member->id)
                                              ->orWhere('leader_2_id', $member->id)
                                              ->orWhere('leader_training_1_id', $member->id)
                                              ->orWhere('leader_training_2_id', $member->id);
                                        })->exists();
                                    }
                                    
                                    // Verificar permissões específicas
                                    $canViewMembers = false;
                                    $canCreateMembers = false;
                                    $canManageRoles = false;
                                    $canViewPgis = false;
                                    $canManagePermissions = $isAdmin; // Apenas admin pode gerenciar permissões
                                    
                                    // Permissões do módulo Ensino
                                    $canViewEstudos = false;
                                    $canViewEscolas = false;
                                    $canViewTurmas = false;
                                    $isStudentOfAnyClass = false; // Verificar se é aluno de alguma turma
                                    $isTeacherOfAnyClass = false; // Verificar se é professor de alguma turma
                                    
                                    // Permissões do módulo Financeiro
                                    $canViewReceitas = false;
                                    $canViewDespesas = false;
                                    $canViewCategories = false;
                                    $canViewAccounts = false;
                                    $canViewContacts = false;
                                    $canViewCostCenters = false;
                                    $canViewReports = false;
                                    
                                    // Permissões do módulo Agenda
                                    $canViewEvents = false;
                                    $canViewEventCategories = false;
                                    
                                    // Permissões do módulo Serviço
                                    $canViewDepartments = false;
                                    $canViewVoluntariosCadastro = false;
                                    $canViewVoluntariosAreas = false;
                                    $canViewVoluntariosEscalas = false;
                                    $canViewVoluntariosHistorico = false;
                                    $canViewVoluntariosRelatorios = false;
                                    
                                    // Permissões do módulo Notificações (WhatsApp)
                                    $canViewNotificacoes = false;
                                    // Permissões do módulo Rifas
                                    $canViewRifas = false;
                                    $canManageRifaSales = false;
                                    $canViewRifaReports = false;
                                    // Permissões do módulo Discipulado
                                    $canViewDiscipulado = false;
                                    $canViewCiclos = false;
                                    $canViewMembrosDiscipulado = false;
                                    $canViewEncontros = false;
                                    $canViewIndicadores = false;
                                    $canViewPropositos = false;
                                    $canViewFeedbacks = false;
                                    $canManageHomePage = false;
                                    $canViewCultosRelatorios = false;
                                    $canManageCultosConfig = false;
                                    $canViewMidiaArquivos = false;
                                    $canViewMidiaInstagram = false;
                                    $canManageMidiaConfig = false;
                                    $canManageMidiaInstagramConfig = false;
                                    
                                    if ($user) {
                                        if ($isAdmin) {
                                            $canViewMembers = true;
                                            $canCreateMembers = true;
                                            $canManageRoles = true;
                                            $canViewPgis = true;
                                            $canViewEstudos = true;
                                            $canViewEscolas = true;
                                            $canViewTurmas = true;
                                            $canViewReceitas = true;
                                            $canViewDespesas = true;
                                            $canViewCategories = true;
                                            $canViewAccounts = true;
                                            $canViewContacts = true;
                                            $canViewCostCenters = true;
                                            $canViewReports = true;
                                            $canViewEvents = true;
                                            $canViewEventCategories = true;
                                            $canViewDepartments = true;
                                            $canViewVoluntariosCadastro = true;
                                            $canViewVoluntariosAreas = true;
                                            $canViewVoluntariosEscalas = true;
                                            $canViewVoluntariosHistorico = true;
                                            $canViewVoluntariosRelatorios = true;
                                            $canViewNotificacoes = true;
                                            $canViewRifas = true;
                                            $canManageRifaSales = true;
                                            $canViewRifaReports = true;
                                            $canViewDiscipulado = true;
                                            $canViewCiclos = true;
                                            $canViewMembrosDiscipulado = true;
                                            $canViewEncontros = true;
                                            $canViewIndicadores = true;
                                            $canViewPropositos = true;
                                            $canViewFeedbacks = true;
                                            $canManageHomePage = true;
                                            $canViewCultosRelatorios = true;
                                            $canManageCultosConfig = true;
                                            $canViewMidiaArquivos = true;
                                            $canViewMidiaInstagram = true;
                                            $canManageMidiaConfig = true;
                                            $canManageMidiaInstagramConfig = true;
                                        } else {
                                            try {
                                                // Verificar permissões específicas do menu
                                                $canViewMembers = $user->hasPermission('members.index.view') || 
                                                                  $user->hasPermission('members.view') ||
                                                                  $user->hasPermission('members.index.manage');
                                                $canCreateMembers = $user->hasPermission('members.index.create') || 
                                                                    $user->hasPermission('members.create') ||
                                                                    $user->hasPermission('members.index.manage');
                                                $canManageRoles = $user->hasPermission('members.roles.view') || 
                                                                  $user->hasPermission('members.roles.create') ||
                                                                  $user->hasPermission('members.roles.edit') ||
                                                                  $user->hasPermission('members.roles.delete') ||
                                                                  $user->hasPermission('members.roles.manage');
                                                $canViewPgis = $user->hasPermission('pgis.index.view') || 
                                                               $user->hasPermission('pgis.index.manage');
                                                
                                                // Verificar permissões do módulo Ensino
                                                $canViewEstudos = $user->hasPermission('ensino.estudos.view') || 
                                                                  $user->hasPermission('ensino.estudos.manage');
                                                $canViewEscolas = $user->hasPermission('ensino.escolas.view') || 
                                                                  $user->hasPermission('ensino.escolas.manage');
                                                $canViewTurmas = $user->hasPermission('ensino.turmas.view') || 
                                                                  $user->hasPermission('ensino.turmas.manage');
                                                
                                                // Verificar se é aluno ou professor de alguma turma
                                                if ($member) {
                                                    $isStudentOfAnyClass = $member->turmas()->exists();
                                                    $isTeacherOfAnyClass = $member->isTeacherOfAnyClass();
                                                }
                                                
                                                // Verificar permissões do módulo Financeiro
                                                $canViewReceitas = $user->hasPermission('financial.receitas.view') || 
                                                                   $user->hasPermission('financial.receitas.manage');
                                                $canViewDespesas = $user->hasPermission('financial.despesas.view') || 
                                                                   $user->hasPermission('financial.despesas.manage');
                                                $canViewCategories = $user->hasPermission('financial.categories.view') || 
                                                                     $user->hasPermission('financial.categories.manage');
                                                $canViewAccounts = $user->hasPermission('financial.accounts.view') || 
                                                                    $user->hasPermission('financial.accounts.manage');
                                                $canViewContacts = $user->hasPermission('financial.contacts.view') || 
                                                                    $user->hasPermission('financial.contacts.manage');
                                                $canViewCostCenters = $user->hasPermission('financial.cost-centers.view') || 
                                                                      $user->hasPermission('financial.cost-centers.manage');
                                                $canViewReports = $user->hasPermission('financial.reports.view') || 
                                                                  $user->hasPermission('financial.reports.manage');
                                                
                                                // Verificar permissões do módulo Agenda
                                                $canViewEvents = $user->hasPermission('agenda.events.view') || 
                                                                 $user->hasPermission('agenda.events.manage');
                                                $canViewEventCategories = $user->hasPermission('agenda.categories.view') || 
                                                                           $user->hasPermission('agenda.categories.manage');
                                                
                                                // Verificar permissões do módulo Serviço
                                                $canViewDepartments = $user->hasPermission('servico.departments.view') || 
                                                                       $user->hasPermission('servico.departments.manage');
                                                $canViewVoluntariosCadastro = $user->hasPermission('servico.voluntarios.cadastro.view') || 
                                                                              $user->hasPermission('servico.voluntarios.cadastro.manage');
                                                $canViewVoluntariosAreas = $user->hasPermission('servico.voluntarios.areas.view') || 
                                                                            $user->hasPermission('servico.voluntarios.areas.manage');
                                                $canViewVoluntariosEscalas = $user->hasPermission('servico.voluntarios.escalas.view') || 
                                                                              $user->hasPermission('servico.voluntarios.escalas.manage');
                                                $canViewVoluntariosHistorico = $user->hasPermission('servico.voluntarios.historico.view') || 
                                                                               $user->hasPermission('servico.voluntarios.historico.manage');
                                                $canViewVoluntariosRelatorios = $user->hasPermission('servico.voluntarios.relatorios.view') || 
                                                                                $user->hasPermission('servico.voluntarios.relatorios.manage');
                                                
                                                // Verificar permissões do módulo Notificações
                                                $canViewNotificacoes = $user->hasPermission('notificacoes.view') ||
                                                                      $user->hasPermission('notificacoes.manage');

                                                // Verificar permissões do módulo Rifas
                                                $canViewRifas = $user->hasPermission('rifas.index.view') ||
                                                                $user->hasPermission('rifas.index.manage');
                                                $canManageRifaSales = $user->hasPermission('rifas.sales.create') ||
                                                                      $user->hasPermission('rifas.sales.manage') ||
                                                                      $user->hasPermission('rifas.index.manage');
                                                $canViewRifaReports = $user->hasPermission('rifas.reports.view') ||
                                                                      $user->hasPermission('rifas.reports.manage');
                                                // Verificar permissões do módulo Discipulado
                                                $canViewDiscipulado = $user->hasPermission('discipleship.view') || 
                                                                      $user->hasPermission('discipleship.manage');
                                                $canViewCiclos = $user->hasPermission('discipleship.cycles.view') || 
                                                                 $user->hasPermission('discipleship.cycles.manage');
                                                $canViewMembrosDiscipulado = $user->hasPermission('discipleship.members.view') || 
                                                                             $user->hasPermission('discipleship.members.manage');
                                                $canViewEncontros = $user->hasPermission('discipleship.meetings.view') || 
                                                                    $user->hasPermission('discipleship.meetings.manage');
                                                $canViewIndicadores = $user->hasPermission('discipleship.indicators.view') || 
                                                                      $user->hasPermission('discipleship.indicators.manage');
                                                $canViewPropositos = $user->hasPermission('discipleship.goals.view') || 
                                                                     $user->hasPermission('discipleship.goals.manage');
                                                $canViewFeedbacks = $user->hasPermission('discipleship.feedbacks.view') || 
                                                                    $user->hasPermission('discipleship.feedbacks.manage');
                                                $canManageHomePage = $user->hasPermission('pagina-principal.manage');
                                                $canViewCultosRelatorios = $user->hasPermission('cultos.relatorios.view');
                                                $canManageCultosConfig = $user->hasPermission('cultos.configuracoes.manage');
                                                $canViewMidiaArquivos = $user->hasPermission('midia.arquivos.view');
                                                $canViewMidiaInstagram = $user->hasPermission('midia.instagram.view');
                                                $canManageMidiaConfig = $user->hasPermission('midia.configuracoes.manage');
                                                $canManageMidiaInstagramConfig = $user->hasPermission('midia.instagram.configuracoes.manage');
                                            } catch (\Exception $e) {
                                                // Em caso de erro, não exibir menu
                                                $canViewMembers = false;
                                                $canCreateMembers = false;
                                                $canManageRoles = false;
                                                $canViewPgis = false;
                                                $canViewEstudos = false;
                                                $canViewEscolas = false;
                                                $canViewTurmas = false;
                                                $canViewReceitas = false;
                                                $canViewDespesas = false;
                                                $canViewCategories = false;
                                                $canViewAccounts = false;
                                                $canViewContacts = false;
                                                $canViewCostCenters = false;
                                                $canViewReports = false;
                                                $canViewEvents = false;
                                                $canViewEventCategories = false;
                                                $canViewDepartments = false;
                                                $canViewVoluntariosCadastro = false;
                                                $canViewVoluntariosAreas = false;
                                                $canViewVoluntariosEscalas = false;
                                                $canViewVoluntariosHistorico = false;
                                                $canViewVoluntariosRelatorios = false;
                                                $canViewNotificacoes = false;
                                                $canViewRifas = false;
                                                $canManageRifaSales = false;
                                                $canViewRifaReports = false;
                                                $canViewDiscipulado = false;
                                                $canViewCiclos = false;
                                                $canViewMembrosDiscipulado = false;
                                                $canViewEncontros = false;
                                                $canViewIndicadores = false;
                                                $canViewPropositos = false;
                                                $canViewFeedbacks = false;
                                                $canManageHomePage = false;
                                                $canViewCultosRelatorios = false;
                                                $canManageCultosConfig = false;
                                                $canViewMidiaArquivos = false;
                                                $canViewMidiaInstagram = false;
                                                $canManageMidiaConfig = false;
                                                $canManageMidiaInstagramConfig = false;
                                            }
                                        }
                                    }
                                @endphp

                                @if($canManageHomePage)
                                <li class="{{ request()->routeIs('pagina-principal.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ route('pagina-principal.edit') }}">
                                        <i class="bx bx-globe" aria-hidden="true"></i>
                                        <span>Página Principal</span>
                                    </a>
                                </li>
                                @endif

                                {{-- MENU MEMBROS — submenus no grid da página --}}
                                @if($canViewMembers || $canManageRoles)
                                @php
                                    $membersHomeRoute = $canViewMembers
                                        ? route('members.index')
                                        : ($canManageRoles ? route('member-roles.index') : route('permissions.index'));
                                @endphp
                                <li class="{{ request()->routeIs('members.*') || request()->routeIs('member-roles.*') || request()->routeIs('permissions.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ $membersHomeRoute }}">
                                        <i class="bx bx-user" aria-hidden="true"></i>
                                        <span>Membros</span>
                                    </a>
                                </li>
                                @endif

                                {{-- MENU PGIs — submenus no grid da página --}}
                                @if($canViewPgis || $hasPgi || $isLeaderOfPgi)
                                <li class="{{ request()->routeIs('pgis.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ route('pgis.index') }}">
                                        <i class="bx bx-group" aria-hidden="true"></i>
                                        <span>PGIs</span>
                                    </a>
                                </li>
                                @endif

                                {{-- MENU ENSINO — submenus no grid da página --}}
                                <li class="{{ request()->routeIs('ensino.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ route('ensino.estudos.index') }}">
                                        <i class="bx bx-book-reader" aria-hidden="true"></i>
                                        <span>Ensino</span>
                                    </a>
                                </li>

                                {{-- MENU FINANCEIRO — submenus ficam no grid da página principal --}}
                                @if($isAdmin || $canViewReceitas || $canViewDespesas || $canViewCategories || $canViewAccounts || $canViewContacts || $canViewCostCenters || $canViewReports)
                                @php
                                    if ($isAdmin || $canViewReceitas || $canViewDespesas) {
                                        $financialHomeRoute = route('financial.summary');
                                    } elseif ($canViewReports) {
                                        $financialHomeRoute = route('financial.reports.index');
                                    } elseif ($canViewAccounts) {
                                        $financialHomeRoute = route('financial.accounts.index');
                                    } elseif ($canViewCategories) {
                                        $financialHomeRoute = route('financial.categories.index');
                                    } elseif ($canViewContacts) {
                                        $financialHomeRoute = route('financial.contacts.index');
                                    } else {
                                        $financialHomeRoute = route('financial.cost-centers.index');
                                    }
                                @endphp
                                <li class="{{ request()->routeIs('financial.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ $financialHomeRoute }}">
                                        <i class="bx bx-dollar" aria-hidden="true"></i>
                                        <span>Financeiro</span>
                                    </a>
                                </li>
                                @endif

                                {{-- MENU RELATÓRIOS DE CULTO --}}
                                @if($isAdmin || $canViewCultosRelatorios || $canManageCultosConfig)
                                <li class="{{ request()->routeIs('cultos.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ ($isAdmin || $canViewCultosRelatorios) ? route('cultos.index') : route('cultos.settings.edit') }}">
                                        <i class="bx bx-church" aria-hidden="true"></i>
                                        <span>Relatórios de Culto</span>
                                    </a>
                                </li>
                                @endif

                                {{-- MENU MÍDIA — submenus no grid da página --}}
                                @if($isAdmin || $canViewMidiaArquivos || $canViewMidiaInstagram || $canManageMidiaConfig || $canManageMidiaInstagramConfig)
                                @php
                                    if ($isAdmin || $canViewMidiaArquivos) {
                                        $midiaHomeRoute = route('midia.index');
                                    } elseif ($canViewMidiaInstagram) {
                                        $midiaHomeRoute = route('midia.instagram.posts.index');
                                    } else {
                                        $midiaHomeRoute = route('midia.settings');
                                    }
                                @endphp
                                <li class="{{ request()->routeIs('midia.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ $midiaHomeRoute }}">
                                        <i class="bx bx-images" aria-hidden="true"></i>
                                        <span>Mídia</span>
                                    </a>
                                </li>
                                @endif

                                {{-- MENU AGENDA — submenus no grid da página --}}
                                <li class="{{ request()->routeIs('agenda.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ route('agenda.calendario.index') }}">
                                        <i class="bx bx-calendar" aria-hidden="true"></i>
                                        <span>Agenda</span>
                                    </a>
                                </li>

                                {{-- MENU SERVIÇO — submenus no grid da página --}}
                                @if($isAdmin || $canViewDepartments || $canViewVoluntariosCadastro || $canViewVoluntariosAreas || $canViewVoluntariosEscalas || $canViewVoluntariosHistorico || $canViewVoluntariosRelatorios)
                                @php
                                    if ($isAdmin || $canViewDepartments) {
                                        $servicoHomeRoute = route('departments.index');
                                    } elseif ($canViewVoluntariosAreas) {
                                        $servicoHomeRoute = route('voluntarios.areas.index');
                                    } elseif ($canViewVoluntariosEscalas) {
                                        $servicoHomeRoute = route('voluntarios.escalas-mensais.index');
                                    } elseif ($canViewVoluntariosHistorico) {
                                        $servicoHomeRoute = route('voluntarios.historico.index');
                                    } else {
                                        $servicoHomeRoute = route('voluntarios.relatorios.dashboard');
                                    }
                                @endphp
                                <li class="{{ request()->routeIs('servico.*') || request()->routeIs('departments.*') || request()->routeIs('voluntarios.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ $servicoHomeRoute }}">
                                        <i class="bx bx-cog" aria-hidden="true"></i>
                                        <span>Serviço</span>
                                    </a>
                                </li>
                                @endif

                                {{-- MENU RIFAS — submenus no grid da página --}}
                                @if($isAdmin || $canViewRifas || $canManageRifaSales || $canViewRifaReports)
                                @php
                                    $rifasHomeRoute = ($isAdmin || $canViewRifas || $canManageRifaSales)
                                        ? route('rifas.index')
                                        : route('rifas.relatorios.dashboard');
                                @endphp
                                <li class="{{ request()->routeIs('rifas.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ $rifasHomeRoute }}">
                                        <i class="bx bx-grid-alt" aria-hidden="true"></i>
                                        <span>Rifas</span>
                                    </a>
                                </li>
                                @endif

                                {{-- MENU MORIAH — submenus no grid da página --}}
                                <li class="{{ request()->routeIs('moriah.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ route('moriah.schedules.index') }}">
                                        <i class="fa-solid fa-music" aria-hidden="true"></i>
                                        <span>Moriah</span>
                                    </a>
                                </li>

                                {{-- MENU NOTIFICAÇÕES — submenus no grid da página --}}
                                @if($isAdmin || ($canViewNotificacoes ?? false))
                                <li class="{{ request()->routeIs('notificacoes.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ route('notificacoes.painel.index') }}">
                                        <i class="bx bx-bell" aria-hidden="true"></i>
                                        <span>Notificações</span>
                                    </a>
                                </li>
                                @endif

                                {{-- MENU DISCIPULADO — submenus no grid da página --}}
                                @if($canViewDiscipulado || $canViewCiclos || $canViewMembrosDiscipulado || $canViewEncontros || $canViewIndicadores || $canViewPropositos || $canViewFeedbacks)
                                @php
                                    if ($canViewCiclos) {
                                        $discipuladoHomeRoute = route('discipleship.cycles.index');
                                    } elseif ($canViewMembrosDiscipulado) {
                                        $discipuladoHomeRoute = route('discipleship.members.index');
                                    } elseif ($canViewEncontros) {
                                        $discipuladoHomeRoute = route('discipleship.meetings.index');
                                    } else {
                                        $discipuladoHomeRoute = route('discipleship.dashboard.discipulador');
                                    }
                                @endphp
                                <li class="{{ request()->routeIs('discipleship.*') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{ $discipuladoHomeRoute }}">
                                        <i class="bx bx-group" aria-hidden="true"></i>
                                        <span>Discipulado</span>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </nav>

                        <hr class="separator" />

                        <div class="sidebar-widget widget-tasks">
                            <div class="widget-header">
                                <h6>Estatísticas</h6>
                                <div class="widget-toggle">+</div>
                            </div>
                            <div class="widget-content">
                                    <ul class="list-unstyled m-0">
                                    <li>Membros Ativos: <strong>@if(class_exists('App\Models\Member')){{ \App\Models\Member::active()->count() }}@else 0 @endif</strong></li>
                                    <li>Total de Membros: <strong>@if(class_exists('App\Models\Member')){{ \App\Models\Member::count() }}@else 0 @endif</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <script>
                        // Maintain Scroll Position
                        if (typeof localStorage !== 'undefined') {
                            if (localStorage.getItem('sidebar-left-position') !== null) {
                                var initialPosition = localStorage.getItem('sidebar-left-position'),
                                    sidebarLeft = document.querySelector('#sidebar-left .nano-content');

                                sidebarLeft.scrollTop = initialPosition;
                            }
                        }
                    </script>
                </div>
            </aside>
            <!-- end: sidebar -->

            <section role="main" class="content-body">
                @php
                    $isFinancialModule = request()->routeIs('financial.*')
                        && !request()->routeIs('financial.transactions.receipt*')
                        && !request()->routeIs('financial.checkout.*');
                    $isNotificacoesModule = request()->routeIs('notificacoes.*');
                    $isMidiaModule = request()->routeIs('midia.*');
                    $isMembersModule = (request()->routeIs('members.*') && !request()->routeIs('members.public.*'))
                        || request()->routeIs('member-roles.*')
                        || request()->routeIs('permissions.*');
                    $isPgisModule = request()->routeIs('pgis.*');
                    $isEnsinoModule = request()->routeIs('ensino.*');
                    $isCultosModule = request()->routeIs('cultos.*');
                    $isAgendaModule = request()->routeIs('agenda.*');
                    $isServicoModule = request()->routeIs('servico.*')
                        || request()->routeIs('departments.*')
                        || request()->routeIs('voluntarios.*');
                    $isRifasModule = request()->routeIs('rifas.*');
                    $isMoriahModule = request()->routeIs('moriah.*');
                    $isDiscipleshipModule = request()->routeIs('discipleship.*');
                @endphp
                <header class="page-header">
                    <h2>@yield('page-title', 'Página')</h2>

                    <div class="right-wrapper text-end">
                        <ol class="breadcrumbs">
                            <li>
                                <a href="{{ route('dashboard') }}">
                                    <i class="bx bx-home-alt"></i>
                                </a>
                            </li>
                            @yield('breadcrumbs')
                        </ol>

                        <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fas fa-chevron-left"></i></a>
                    </div>
                </header>

                <!-- start: page -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Sucesso!</strong> {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Erro!</strong>
                        {{ session('error') ?: 'Ocorreu um problema ao processar sua solicitação. Verifique os campos destacados abaixo ou tente novamente.' }}
                    </div>
                @endif

                @if(session('access_denied'))
                    <!-- Modal de Acesso Negado -->
                    <div class="modal fade" id="accessDeniedModal" tabindex="-1" aria-labelledby="accessDeniedModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="accessDeniedModalLabel">
                                        <i class="bx bx-error-circle me-2"></i>Acesso Negado
                                    </h5>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-0">{{ session('access_denied') }}</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="window.history.back()">
                                        <i class="bx bx-check me-1"></i>OK
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var modal = new bootstrap.Modal(document.getElementById('accessDeniedModal'));
                            modal.show();
                            
                            // Quando o modal for fechado, voltar para a página anterior
                            document.getElementById('accessDeniedModal').addEventListener('hidden.bs.modal', function () {
                                if (window.history.length > 1) {
                                    window.history.back();
                                } else {
                                    window.location.href = '{{ route("dashboard") }}';
                                }
                            });
                        });
                    </script>
                @endif

                @if(isset($errors))
                    @php
                        $hasErrors = false;
                        if (is_object($errors) && method_exists($errors, 'any')) {
                            $hasErrors = $errors->any();
                        } elseif (is_array($errors)) {
                            $hasErrors = count($errors) > 0;
                        }
                    @endphp
                    @if($hasErrors)
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Corrija os seguintes pontos:</strong>
                            <ul class="mb-0 mt-2">
                                @if(is_object($errors) && method_exists($errors, 'all'))
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                @elseif(is_array($errors))
                                    @foreach($errors as $key => $error)
                                        <li>
                                            @if(is_array($error))
                                                {{ $key }}: {{ implode(', ', $error) }}
                                            @else
                                                {{ $error }}
                                            @endif
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    @endif
                @endif

                @if($isMembersModule)
                    @include('members.partials.module-nav')
                @endif
                @if($isPgisModule)
                    @include('pgis.partials.module-nav')
                @endif
                @if($isEnsinoModule)
                    @include('ensino.partials.module-nav')
                @endif
                @if($isFinancialModule)
                    @include('financial.partials.module-nav')
                @endif
                @if($isCultosModule)
                    @include('cultos.partials.module-nav')
                @endif
                @if($isMidiaModule)
                    @include('midia.partials.module-nav')
                @endif
                @if($isAgendaModule)
                    @include('agenda.partials.module-nav')
                @endif
                @if($isServicoModule)
                    @include('servico.partials.module-nav')
                @endif
                @if($isRifasModule)
                    @include('rifas.partials.module-nav')
                @endif
                @if($isMoriahModule)
                    @include('moriah.partials.module-nav')
                @endif
                @if($isNotificacoesModule)
                    @include('notificacoes.partials.module-nav')
                @endif
                @if($isDiscipleshipModule)
                    @include('discipleship.partials.module-nav')
                @endif

                @yield('content')
                <!-- end: page -->
            </section>
        </div>

        <aside id="sidebar-right" class="sidebar-right">
            <div class="nano">
                <div class="nano-content">
                    <a href="#" class="mobile-close d-md-none">
                        Collapse <i class="fas fa-chevron-right"></i>
                    </a>

                    <div class="sidebar-right-wrapper">
                        <div class="sidebar-widget widget-calendar">
                            @php
                                $today = \Carbon\Carbon::today();
                                $weekStart = $today->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
                                $weekEnd = $today->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

                                $weeklyEvents = collect();
                                if (class_exists(\App\Models\Event::class)) {
                                    $weeklyEvents = \App\Models\Event::query()
                                        ->where(function ($query) use ($weekStart, $weekEnd) {
                                            $query->whereBetween('start_date', [$weekStart, $weekEnd])
                                                ->orWhereBetween('end_date', [$weekStart, $weekEnd])
                                                ->orWhere(function ($q) use ($weekStart, $weekEnd) {
                                                    $q->where('start_date', '<=', $weekStart)
                                                        ->where('end_date', '>=', $weekEnd);
                                                });
                                        })
                                        ->orderBy('start_date')
                                        ->limit(8)
                                        ->get();
                                }
                            @endphp
                            <h6>Próximos Eventos</h6>
                            <div id="sidebar-datepicker" data-plugin-datepicker data-plugin-skin="dark"></div>
                            <ul>
                                @forelse($weeklyEvents as $event)
                                    <li>
                                        <time datetime="{{ optional($event->start_date)->format('Y-m-d') }}">
                                            {{ optional($event->start_date)->format('d/m/Y') }}
                                        </time>
                                        <span>{{ $event->title }}</span>
                                    </li>
                                @empty
                                    <li>
                                        <time datetime="{{ $today->format('Y-m-d') }}">{{ $today->format('d/m/Y') }}</time>
                                        <span>Nenhum evento nesta semana</span>
                                    </li>
                                @endforelse
                            </ul>
                            <a href="{{ route('agenda.eventos.index') }}" class="btn btn-xs btn-primary mt-2">Ver agenda</a>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </section>

    <!-- Vendor -->
    <script src="{{ asset('vendor/vendor/jquery/jquery.js') }}"></script>
    <script src="{{ asset('vendor/vendor/jquery-browser-mobile/jquery.browser.mobile.js') }}"></script>
    <!-- Popper está incluído no bootstrap.bundle.min.js, não precisa carregar separadamente -->
    <script src="{{ asset('vendor/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/vendor/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('vendor/vendor/bootstrap-datepicker/locales/bootstrap-datepicker.pt-BR.min.js') }}"></script>
    <script src="{{ asset('vendor/vendor/common/common.js') }}"></script>
    <script src="{{ asset('vendor/vendor/nanoscroller/nanoscroller.js') }}"></script>
    <script src="{{ asset('vendor/vendor/magnific-popup/jquery.magnific-popup.js') }}"></script>
    <script src="{{ asset('vendor/vendor/jquery-placeholder/jquery.placeholder.js') }}"></script>

    <!-- Theme Base, Components and Settings -->
    <script src="{{ asset('js/js/theme.js') }}"></script>

    <!-- Theme Custom -->
    <script src="{{ asset('js/js/custom.js') }}"></script>

    <!-- Theme Initialization Files -->
    <script src="{{ asset('js/js/theme.init.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var $datepicker = $('#sidebar-datepicker');
            if ($datepicker.length && $.fn.datepicker) {
                $datepicker.datepicker('destroy');
                $datepicker.datepicker({
                    language: 'pt-BR',
                    format: 'dd/mm/yyyy',
                    todayHighlight: true,
                    autoclose: true
                });
            }
        });
    </script>

    <!-- Tratamento de erros de extensões do navegador -->
    <script>
        // Capturar e suprimir erros de extensões do navegador que não afetam o funcionamento do site
        window.addEventListener('error', function(event) {
            // Verificar se o erro é de uma extensão do navegador (contentScript.js)
            if (event.filename && event.filename.includes('contentScript.js')) {
                // Suprimir o erro no console para não poluir
                event.preventDefault();
                return true;
            }
            // Verificar se o erro está relacionado a propriedades undefined de extensões
            if (event.message && (
                event.message.includes("Cannot read properties of undefined") ||
                event.message.includes("reading 'sentence'")
            )) {
                // Se for um erro de extensão, suprimir
                if (event.filename && (
                    event.filename.includes('contentScript') ||
                    event.filename.includes('extension') ||
                    event.filename.includes('chrome-extension') ||
                    event.filename.includes('moz-extension')
                )) {
                    event.preventDefault();
                    return true;
                }
            }
        }, true);

        // Tratamento de erros não capturados em Promises
        window.addEventListener('unhandledrejection', function(event) {
            // Verificar se o erro é de uma extensão do navegador
            const errorMessage = event.reason?.message || event.reason?.toString() || '';
            if (errorMessage.includes("Cannot read properties of undefined") && 
                errorMessage.includes("sentence")) {
                // Suprimir erros de extensões relacionados a 'sentence'
                event.preventDefault();
                return true;
            }
        });
    </script>

    <script>
    (function () {
        const mobileQuery = window.matchMedia('(max-width: 767.98px)');

        function getActionLabel(el) {
            if (el.title) return el.title;
            if (el.tagName === 'FORM') {
                const btn = el.querySelector('button[type="submit"]');
                return btn?.title || btn?.textContent?.trim() || 'Excluir';
            }
            const icon = el.querySelector('i');
            if (icon?.classList.contains('bx-edit')) return 'Editar';
            if (icon?.classList.contains('bx-trash')) return 'Excluir';
            if (icon?.classList.contains('bx-printer')) return 'Imprimir';
            if (icon?.classList.contains('bx-copy')) return 'Duplicar';
            if (icon?.classList.contains('bx-show') || icon?.classList.contains('bx-search')) return 'Ver';
            return el.textContent?.trim() || 'Ação';
        }

        function restoreGroup(group) {
            const mobile = group.previousElementSibling;
            if (mobile?.classList?.contains('table-actions-mobile')) {
                mobile.remove();
            }
            group.classList.remove('d-none', 'd-md-inline-flex', 'table-actions-processed');
        }

        function convertGroup(group) {
            if (group.classList.contains('table-actions-processed')) {
                return;
            }

            const directActions = Array.from(group.children).filter((child) => {
                return child.matches('button, a.btn, form');
            });

            if (directActions.length < 3) {
                return;
            }

            const dropdown = document.createElement('div');
            dropdown.className = 'dropdown table-actions-mobile d-md-none';
            dropdown.innerHTML = '<button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded"></i></button><ul class="dropdown-menu dropdown-menu-end"></ul>';

            const menu = dropdown.querySelector('.dropdown-menu');
            directActions.forEach((el) => {
                const item = document.createElement('li');
                const action = document.createElement('button');
                action.type = 'button';
                action.className = 'dropdown-item';
                action.textContent = getActionLabel(el);
                action.addEventListener('click', () => {
                    if (el.tagName === 'FORM') {
                        const submit = el.querySelector('button[type="submit"]');
                        if (submit) submit.click();
                        return;
                    }
                    el.click();
                });
                item.appendChild(action);
                menu.appendChild(item);
            });

            group.classList.add('d-none', 'd-md-inline-flex', 'table-actions-processed');
            group.parentNode.insertBefore(dropdown, group);
        }

        function processTableActions() {
            document.querySelectorAll('table tbody td .btn-group[role="group"]').forEach((group) => {
                if (!mobileQuery.matches) {
                    restoreGroup(group);
                    return;
                }
                convertGroup(group);
            });
        }

        document.addEventListener('DOMContentLoaded', processTableActions);
        mobileQuery.addEventListener('change', processTableActions);
    })();
    </script>

    @stack('scripts')
</body>
</html>

