<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PgiController;
use App\Http\Controllers\MeetingController;


// Rotas do módulo de PGIs (admin total, outros só ver se fizer parte de PGI)
Route::resource('pgis', PgiController::class)->middleware('module.access:pgis');

    // Rotas do módulo de Reuniões (dentro de um PGI) - admin total, outros só ver se fizer parte
Route::prefix('pgis/{pgi}')->name('pgis.')->middleware('module.access:pgis')->group(function () {
Route::get('meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
Route::post('meetings', [MeetingController::class, 'store'])->name('meetings.store');
Route::get('meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
Route::put('meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');

// Rotas para gerenciar membros do PGI
Route::post('members/attach', [PgiController::class, 'attachMembers'])->name('members.attach');
Route::delete('members/{member}/detach', [PgiController::class, 'detachMember'])->name('members.detach');

// Rotas para atualizar logo e banner
Route::post('logo', [PgiController::class, 'updateLogo'])->name('logo.update');
Route::post('banner', [PgiController::class, 'updateBanner'])->name('banner.update');
Route::post('notificacoes/enviar', [PgiController::class, 'enviarNotificacao'])->name('notificacoes.enviar');
});
