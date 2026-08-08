<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PgiController;
use App\Http\Controllers\MeetingController;


// Rotas do módulo de PGIs (admin total, outros só ver se fizer parte de PGI)
Route::resource('pgis', PgiController::class)->middleware('module.access:pgis');

    // Rotas do módulo de Reuniões (dentro de um PGI) - admin total, outros só ver se fizer parte
Route::prefix('pgis/{pgi}')->name('pgis.')->middleware('module.access:pgis')->group(function () {
// Reuniões: as rotas com segmento fixo vêm antes de reunioes/{meeting} para não serem capturadas por ele
Route::get('reunioes/nova', [MeetingController::class, 'create'])->name('meetings.create');
Route::post('reunioes', [MeetingController::class, 'store'])->name('meetings.store');
Route::post('reunioes/recorrentes', [MeetingController::class, 'storeRecurring'])->name('meetings.recurring');
Route::get('reunioes/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
Route::get('reunioes/{meeting}/editar', [MeetingController::class, 'edit'])->name('meetings.edit');
Route::put('reunioes/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
Route::delete('reunioes/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');
Route::get('reunioes/{meeting}/presenca', [MeetingController::class, 'attendance'])->name('meetings.attendance');
Route::post('reunioes/{meeting}/presenca', [MeetingController::class, 'storeAttendance'])->name('meetings.attendance.store');

// Rotas para gerenciar membros do PGI
Route::post('members/attach', [PgiController::class, 'attachMembers'])->name('members.attach');
Route::delete('members/{member}/detach', [PgiController::class, 'detachMember'])->name('members.detach');

// Rotas para atualizar logo e banner
Route::post('logo', [PgiController::class, 'updateLogo'])->name('logo.update');
Route::post('banner', [PgiController::class, 'updateBanner'])->name('banner.update');
Route::post('notificacoes/enviar', [PgiController::class, 'enviarNotificacao'])->name('notificacoes.enviar');

// Localização (geocodificação sob demanda) e relatório em PDF
Route::get('localizacao', [PgiController::class, 'localizacao'])->name('localizacao');
Route::get('relatorio', [PgiController::class, 'relatorio'])->name('relatorio');
});
