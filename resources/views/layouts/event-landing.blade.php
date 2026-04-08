<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Evento')</title>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600;700&family=Oswald:wght@400;600&family=Playfair+Display:wght@500;700&family=Poppins:wght@400;600;700&family=Roboto+Slab:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/vendor/bootstrap/css/bootstrap.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/vendor/font-awesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/css/theme.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/css/landing.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/css/skins/default.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/css/event-landing.css') }}" />
    @stack('styles')
</head>
<body class="event-landing-body">
@yield('content')
<script src="{{ asset('vendor/vendor/jquery/jquery.js') }}"></script>
<script src="{{ asset('vendor/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body>
</html>
