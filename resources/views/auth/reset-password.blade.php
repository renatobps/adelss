<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Redefinir senha - ADELSS Sistema Web</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800|Shadows+Into+Light" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ asset('vendor/vendor/bootstrap/css/bootstrap.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/font-awesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/boxicons/css/boxicons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/css/theme.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/css/skins/default.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/css/custom.css') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/img/LOG SS AZUL.png') }}" />
</head>
<body class="bg-dark">
    <section class="body-sign">
        <div class="center-sign">
            <a href="{{ route('login') }}" class="logo float-start">
                <img src="{{ asset('img/img/LOG SS branca.png') }}" height="60" alt="ADELSS" />
            </a>
            <div class="panel card-sign">
                <div class="card-title-sign mt-3 text-end">
                    <h2 class="title text-uppercase text-dark fw-bold m-0">
                        <i class="bx bx-lock-alt me-1"></i> Nova senha
                    </h2>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('password.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <div class="form-group mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $email) }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Nova senha</label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Confirmar senha</label>
                            <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Salvar senha</button>
                    </form>
                    <p class="mt-3 mb-0 text-center">
                        <a href="{{ route('login') }}">Voltar ao login</a>
                    </p>
                </div>
            </div>
        </div>
    </section>
    <script src="{{ asset('vendor/vendor/jquery/jquery.js') }}"></script>
    <script src="{{ asset('vendor/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
