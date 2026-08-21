<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Agenda\PublicEventController;
use App\Http\Controllers\Ensino\PublicStudyFormController;
use App\Http\Controllers\Midia\PublicMediaFormController;
use App\Http\Controllers\Webhooks\MercadoPagoWebhookController;
use App\Http\Controllers\Webhooks\EvolutionWebhookController;
use App\Http\Controllers\PublicMemberRegistrationController;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Rotas de autenticação
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/evento/{slug}', [PublicEventController::class, 'show'])->name('events.public.show');
Route::post('/evento/{slug}/inscricao', [PublicEventController::class, 'register'])->name('events.public.register');
Route::post('/evento/{slug}/inscricao/reenviar-comprovante', [PublicEventController::class, 'resendReceipt'])
    ->middleware('throttle:5,10')
    ->name('events.public.resend-receipt');

Route::get('/estudo-formulario/{slug}', [PublicStudyFormController::class, 'show'])->name('study.forms.public.show');
Route::post('/estudo-formulario/{slug}/responder', [PublicStudyFormController::class, 'submit'])->name('study.forms.public.submit');

// Formularios de coleta de dados (modulo Midia), abertos sem login
Route::get('/formulario/{slug}', [PublicMediaFormController::class, 'show'])->name('formularios.public.show');
Route::post('/formulario/{slug}', [PublicMediaFormController::class, 'submit'])
    ->middleware('throttle:10,1')
    ->name('formularios.public.submit');

// Cadastro público de membro (sem login)
Route::get('/cadastro-membro/{token}', [PublicMemberRegistrationController::class, 'create'])->name('members.public.create');
Route::post('/cadastro-membro/{token}', [PublicMemberRegistrationController::class, 'store'])->name('members.public.store');
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
    require __DIR__.'/modules/cultos.php';
    require __DIR__.'/modules/midia.php';
});
