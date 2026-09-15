#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Diagnóstico ADELSS ===\n\n";

$checks = [
    'APP_KEY definida' => fn () => (string) config('app.key') !== '',
    'storage/logs gravável' => fn () => is_dir(storage_path('logs')) && is_writable(storage_path('logs')),
    'storage/framework/sessions gravável' => fn () => is_dir(storage_path('framework/sessions')) && is_writable(storage_path('framework/sessions')),
    'storage/framework/cache gravável' => function () {
        $dir = storage_path('framework/cache/data');
        if (! is_dir($dir) || ! is_writable($dir)) {
            return false;
        }
        $probe = $dir.DIRECTORY_SEPARATOR.'.adelss-write-probe';
        $ok = @file_put_contents($probe, 'ok') !== false;
        if ($ok) {
            @unlink($probe);
        }

        return $ok;
    },
    'bootstrap/cache gravável' => fn () => is_dir(base_path('bootstrap/cache')) && is_writable(base_path('bootstrap/cache')),
    'public/storage existe' => fn () => file_exists(public_path('storage')) || is_link(public_path('storage')),
];

foreach ($checks as $label => $check) {
    $ok = $check();
    echo ($ok ? '[OK] ' : '[FALHA] ') . $label . "\n";
}

echo "\nBanco de dados:\n";

try {
    Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "[OK] Conexão com o banco\n";

    $tables = ['users', 'home_page_settings', 'departments', 'financial_transactions'];
    foreach ($tables as $table) {
        $exists = Illuminate\Support\Facades\Schema::hasTable($table);
        echo ($exists ? '[OK] ' : '[FALHA] ') . "Tabela {$table}\n";
    }
} catch (Throwable $e) {
    echo '[FALHA] Conexão com o banco: ' . $e->getMessage() . "\n";
}

echo "\nRotas:\n";

try {
    $home = app(App\Http\Controllers\HomeController::class);
    echo "[OK] HomeController carregado\n";
} catch (Throwable $e) {
    echo '[FALHA] HomeController: ' . $e->getMessage() . "\n";
}

echo "\nÚltimas linhas do log (se existir):\n";
$logFile = storage_path('logs/laravel.log');
if (is_file($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES) ?: [];
    foreach (array_slice($lines, -15) as $line) {
        echo $line . "\n";
    }
} else {
    echo "Arquivo de log ainda não existe.\n";
}

echo "\n=== Fim ===\n";
