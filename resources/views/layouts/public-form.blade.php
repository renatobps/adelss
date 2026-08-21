<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Formulário')</title>
    <link rel="stylesheet" href="{{ asset('vendor/vendor/bootstrap/css/bootstrap.css') }}">
    <style>
        :root {
            --primary: #0088CC;
            --text: #2E353E;
            --text-secondary: #6C757D;
            --border: #EEF0F2;
            --success: #1FA855;
            --danger: #DC3545;
        }

        body {
            margin: 0;
            background: #F4F6F8;
            color: var(--text);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        .pf-shell {
            max-width: 720px;
            margin: 0 auto;
            padding: 24px 16px 48px;
        }

        .pf-brand {
            text-align: center;
            margin-bottom: 20px;
        }

        .pf-brand img { max-height: 56px; }

        .pf-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(46, 53, 62, 0.08);
            overflow: hidden;
        }

        .pf-header {
            padding: 24px;
            border-bottom: 1px solid var(--border);
            border-top: 4px solid var(--primary);
        }

        .pf-title {
            font-size: 1.45rem;
            font-weight: 700;
            margin: 0 0 8px;
        }

        .pf-description {
            color: var(--text-secondary);
            margin: 0;
            white-space: pre-line;
        }

        .pf-body { padding: 24px; }

        .pf-field { margin-bottom: 22px; }

        .pf-label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .pf-required { color: var(--danger); }

        .pf-help {
            display: block;
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-top: 4px;
        }

        .pf-option {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
        }

        .pf-option:hover { border-color: var(--primary); }

        .pf-option input { margin-top: 3px; }

        .pf-footer {
            padding: 20px 24px 24px;
            border-top: 1px solid var(--border);
        }

        .pf-submit {
            width: 100%;
            padding: 13px;
            font-size: 1rem;
            font-weight: 600;
        }

        .pf-state {
            text-align: center;
            padding: 40px 24px;
        }

        .pf-state__icon {
            font-size: 3rem;
            line-height: 1;
            margin-bottom: 12px;
        }

        .pf-note {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-top: 18px;
        }

        @media (max-width: 575px) {
            .pf-header, .pf-body { padding: 18px; }
            .pf-footer { padding: 16px 18px 20px; }
            .pf-title { font-size: 1.2rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="pf-shell">
        @yield('content')
        <p class="pf-note">{{ config('app.name') }}</p>
    </div>
    <script src="{{ asset('vendor/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
