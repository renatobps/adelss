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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

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
    <link rel="stylesheet" href="{{ asset('css/css/custom.css') }}">

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

                <div id="userbox" class="userbox">
                    <a href="#" data-bs-toggle="dropdown">
                        <figure class="profile-picture">
                            <img src="{{ asset('img/img/!logged-user.jpg') }}" alt="Usuário" class="rounded-circle" data-lock-picture="{{ asset('img/img/!logged-user.jpg') }}" />
                        </figure>
                        <div class="profile-info" data-lock-name="Usuário" data-lock-email="usuario@adelss.com">
                            <span class="name">Usuário</span>
                            <span class="role">Administrador</span>
                        </div>

                        <i class="fa custom-caret"></i>
                    </a>

                    <div class="dropdown-menu">
                        <ul class="list-unstyled mb-2">
                            <li class="divider"></li>
                            <li>
                                <a role="menuitem" tabindex="-1" href="#"><i class="bx bx-user-circle"></i> Meu Perfil</a>
                            </li>
                            <li>
                                <a role="menuitem" tabindex="-1" href="#"><i class="bx bx-lock"></i> Bloquear Tela</a>
                            </li>
                            <li>
                                <a role="menuitem" tabindex="-1" href="#"><i class="bx bx-power-off"></i> Sair</a>
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
                                <li class="nav-parent {{ request()->routeIs('members.*') || request()->routeIs('member-roles.*') ? 'nav-expanded nav-active' : '' }}">
                                    <a class="nav-link" href="#">
                                        <i class="bx bx-user" aria-hidden="true"></i>
                                        <span>Membros</span>
                                    </a>
                                    <ul class="nav nav-children">
                                        <li class="{{ request()->routeIs('members.index') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('members.index') }}">
                                                Ver Todos
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('members.create') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('members.create') }}">
                                                Novo Membro
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('member-roles.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('member-roles.index') }}">
                                                Cargos
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="nav-parent">
                                    <a class="nav-link" href="#">
                                        <i class="bx bx-group" aria-hidden="true"></i>
                                        <span>PGIs</span>
                                    </a>
                                    <ul class="nav nav-children">
                                        <li>
                                            <a class="nav-link" href="{{ route('pgis.index') }}">
                                                Listar PGIs
                                            </a>
                                        </li>
                                        <li>
                                            <a class="nav-link" href="{{ route('pgis.create') }}">
                                                Novo PGI
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="nav-parent {{ request()->routeIs('ensino.*') ? 'nav-expanded nav-active' : '' }}">
                                    <a class="nav-link" href="#">
                                        <i class="bx bx-book-reader" aria-hidden="true"></i>
                                        <span>Ensino</span>
                                    </a>
                                    <ul class="nav nav-children">
                                        <li class="{{ request()->routeIs('ensino.estudos.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('ensino.estudos.index') }}">
                                                <i class="bx bx-file"></i>Estudos
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('ensino.escolas.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('ensino.escolas.index') }}">
                                                <i class="bx bx-list-ul"></i>Escolas
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('ensino.turmas.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('ensino.turmas.index') }}">
                                                <i class="fa-solid fa-graduation-cap"></i>Turmas
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="nav-parent {{ request()->routeIs('financial.*') ? 'nav-expanded nav-active' : '' }}">
                                    <a class="nav-link" href="#">
                                        <i class="bx bx-dollar" aria-hidden="true"></i>
                                        <span>Financeiro</span>
                                    </a>
                                    <ul class="nav nav-children">
                                        <li class="{{ request()->routeIs('financial.summary') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('financial.summary') }}">
                                                <i class="bx bx-right-arrow-alt"></i>Resumo
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('financial.transactions.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('financial.transactions.index') }}">
                                                <i class="bx bx-refresh"></i>Transações
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('financial.reports.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('financial.reports.index') }}">
                                                <i class="bx bx-line-chart"></i>Relatórios
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('financial.categories.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('financial.categories.index') }}">
                                                <i class="bx bx-purchase-tag"></i>Categorias
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('financial.accounts.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('financial.accounts.index') }}">
                                                <i class="bx bx-file"></i>Contas
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('financial.contacts.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('financial.contacts.index') }}">
                                                <i class="bx bx-user"></i>Contatos
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('financial.cost-centers.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('financial.cost-centers.index') }}">
                                                <i class="bx bx-folder"></i>Centros de custos
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="nav-parent {{ request()->routeIs('agenda.*') ? 'nav-expanded nav-active' : '' }}">
                                    <a class="nav-link" href="#">
                                        <i class="bx bx-calendar" aria-hidden="true"></i>
                                        <span>Agenda</span>
                                    </a>
                                    <ul class="nav nav-children">
                                        <li class="{{ request()->routeIs('agenda.calendario.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('agenda.calendario.index') }}">
                                                <i class="bx bx-calendar"></i>Calendário
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('agenda.eventos.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('agenda.eventos.index') }}">
                                                <i class="bx bx-calendar-event"></i>Eventos
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="nav-parent {{ request()->routeIs('servico.*') || request()->routeIs('departments.*') || request()->routeIs('voluntarios.*') || request()->routeIs('escalas.*') || request()->routeIs('disponibilidade.*') || request()->routeIs('voluntarios.cadastro.*') || request()->routeIs('voluntarios.areas.*') || request()->routeIs('voluntarios.disponibilidade.*') || request()->routeIs('voluntarios.escalas.*') || request()->routeIs('voluntarios.historico.*') || request()->routeIs('voluntarios.relatorios.*') ? 'nav-expanded nav-active' : '' }}">
                                    <a class="nav-link" href="#">
                                        <i class="bx bx-cog" aria-hidden="true"></i>
                                        <span>Serviço</span>
                                    </a>
                                    <ul class="nav nav-children">
                                        <li class="{{ request()->routeIs('departments.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="{{ route('departments.index') }}">
                                                <i class="bx bx-building"></i>Departamentos
                                            </a>
                                        </li>
                                        <li class="nav-parent {{ request()->routeIs('voluntarios.*') ? 'nav-expanded nav-active' : '' }}">
                                            <a class="nav-link" href="#">
                                                <i class="bx bx-user"></i>Voluntários
                                            </a>
                                            <ul class="nav nav-children">
                                                <li class="{{ request()->routeIs('voluntarios.cadastro.*') ? 'nav-active' : '' }}">
                                                    <a class="nav-link" href="{{ route('voluntarios.cadastro.index') }}">
                                                        <i class="bx bx-user-plus"></i>Cadastro de Voluntários
                                                    </a>
                                                </li>
                                                <li class="{{ request()->routeIs('voluntarios.areas.*') ? 'nav-active' : '' }}">
                                                    <a class="nav-link" href="{{ route('voluntarios.areas.index') }}">
                                                        <i class="bx bx-category"></i>Áreas de Serviço
                                                    </a>
                                                </li>
                                                <li class="{{ request()->routeIs('voluntarios.disponibilidade.*') ? 'nav-active' : '' }}">
                                                    <a class="nav-link" href="{{ route('voluntarios.disponibilidade.index') }}">
                                                        <i class="bx bx-time"></i>Disponibilidade
                                                    </a>
                                                </li>
                                                <li class="{{ request()->routeIs('voluntarios.escalas.*') ? 'nav-active' : '' }}">
                                                    <a class="nav-link" href="#">
                                                        <i class="bx bx-calendar-check"></i>Escalas
                                                    </a>
                                                </li>
                                                <li class="{{ request()->routeIs('voluntarios.historico.*') ? 'nav-active' : '' }}">
                                                    <a class="nav-link" href="#">
                                                        <i class="bx bx-history"></i>Histórico de Serviço
                                                    </a>
                                                </li>
                                                <li class="{{ request()->routeIs('voluntarios.relatorios.*') ? 'nav-active' : '' }}">
                                                    <a class="nav-link" href="#">
                                                        <i class="bx bx-bar-chart-alt-2"></i>Relatórios
                                                    </a>
                                                </li>
                                            </ul>
                                        </li>
                                        <li class="{{ request()->routeIs('escalas.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="#">
                                                <i class="bx bx-calendar-check"></i>Escalas
                                            </a>
                                        </li>
                                        <li class="{{ request()->routeIs('disponibilidade.*') ? 'nav-active' : '' }}">
                                            <a class="nav-link" href="#">
                                                <i class="bx bx-time"></i>Disponibilidade
                                            </a>
                                        </li>
                                    </ul>
                                </li>
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
                        <strong>Erro!</strong> {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Erro!</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
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
                            <h6>Próximos Eventos</h6>
                            <div data-plugin-datepicker data-plugin-skin="dark"></div>
                            <ul>
                                <li>
                                    <time datetime="{{ date('Y-m-d') }}">{{ date('d/m/Y') }}</time>
                                    <span>Nenhum evento</span>
                                </li>
                            </ul>
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

    @stack('scripts')
</body>
</html>

