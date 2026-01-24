<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - ADELSS Sistema Web</title>

    <!-- Web Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800|Shadows+Into+Light" rel="stylesheet" type="text/css">

    <!-- Vendor CSS (mesmos arquivos do layout Porto) -->
    <link rel="stylesheet" href="{{ asset('vendor/vendor/bootstrap/css/bootstrap.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/animate/animate.compat.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/vendor/font-awesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/boxicons/css/boxicons.min.css') }}" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/css/theme.css') }}" />

    <!-- Skin CSS -->
    <link rel="stylesheet" href="{{ asset('css/css/skins/default.css') }}" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/css/custom.css') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/img/LOG SS AZUL.png') }}" />
</head>
<body class="bg-dark">
    <section class="body-sign">
        <div class="center-sign">
            <a href="#" class="logo float-start">
                <img src="{{ asset('img/img/LOG SS branca.png') }}" height="60" alt="ADELSS" />
            </a>

            <div class="panel card-sign">
                <div class="card-title-sign mt-3 text-end">
                    <h2 class="title text-uppercase text-dark fw-bold m-0">
                        <i class="bx bx-lock-alt me-1"></i> Login
                    </h2>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Ocorreram erros:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('login.attempt') }}" method="POST">
                        @csrf

                        <div class="form-group mb-3">
                            <label class="form-label">E-mail</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bx bx-user text-muted"></i>
                                </span>
                                <input type="email"
                                       name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}"
                                       required
                                       autofocus
                                       placeholder="seuemail@exemplo.com">
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bx bx-lock text-muted"></i>
                                </span>
                                <input type="password"
                                       name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       required
                                       placeholder="Digite sua senha">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">
                                        Manter-me conectado
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bx bx-log-in me-1"></i> Entrar
                                </button>
                            </div>
                        </div>
                    </form>

                    <p class="mt-3 mb-0 text-center text-muted small">
                        ADELSS Sistema Web - Acesso restrito.
                    </p>
                </div>
            </div>

            <p class="text-center text-muted mt-3 mb-0 small">
                &copy; {{ date('Y') }} ADELSS. Todos os direitos reservados.
            </p>
        </div>
    </section>

    <!-- Vendor -->
    <script src="{{ asset('vendor/vendor/jquery/jquery.js') }}"></script>
    <script src="{{ asset('vendor/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>

