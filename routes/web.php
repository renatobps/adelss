<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Agenda\PublicEventController;
use App\Http\Controllers\Webhooks\MercadoPagoWebhookController;
use App\Http\Controllers\Webhooks\EvolutionWebhookController;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Rotas de autenticação
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/evento/{slug}', [PublicEventController::class, 'show'])->name('events.public.show');
Route::post('/evento/{slug}/inscricao', [PublicEventController::class, 'register'])->name('events.public.register');
Route::post('/webhooks/mercado-pago', [MercadoPagoWebhookController::class, 'handle'])->name('webhooks.mercadopago');
Route::post('/webhook', [EvolutionWebhookController::class, 'handle'])->name('webhooks.evolution');
Route::post('/webhook/{event}', [EvolutionWebhookController::class, 'handle'])
    ->where('event', '.*')
    ->name('webhooks.evolution.event');

Route::middleware('auth')->group(function () {
    require __DIR__.'/modules/dashboard.php';
    require __DIR__.'/modules/members.php';
    require __DIR__.'/modules/servico.php';
    require __DIR__.'/modules/permissions.php';
    require __DIR__.'/modules/moriah.php';
    require __DIR__.'/modules/notificacoes.php';
    require __DIR__.'/modules/rifas.php';
    require __DIR__.'/modules/pgis.php';
    require __DIR__.'/modules/financial.php';
    require __DIR__.'/modules/discipleship.php';
    require __DIR__.'/modules/ensino.php';
    require __DIR__.'/modules/agenda.php';
});
