<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Discipleship\DiscipleshipCycleController;
use App\Http\Controllers\Discipleship\DiscipleshipMemberController;
use App\Http\Controllers\Discipleship\DiscipleshipMeetingController;
use App\Http\Controllers\Discipleship\DiscipleshipIndicatorController;
use App\Http\Controllers\Discipleship\DiscipleshipGoalController;
use App\Http\Controllers\Discipleship\DiscipleshipFeedbackController;
use App\Http\Controllers\Discipleship\DiscipleshipDashboardController;


// Rotas do módulo de Discipulado
Route::prefix('discipleship')->name('discipleship.')->middleware('module.access:discipleship')->group(function () {
    // Dashboard
    Route::get('dashboard/discipulador', [DiscipleshipDashboardController::class, 'discipulador'])->name('dashboard.discipulador');
    Route::get('dashboard/lideranca', [DiscipleshipDashboardController::class, 'lideranca'])->name('dashboard.lideranca');

    // Ciclos
    Route::resource('cycles', DiscipleshipCycleController::class)->names([
    'index' => 'cycles.index',
    'create' => 'cycles.create',
    'store' => 'cycles.store',
    'show' => 'cycles.show',
    'edit' => 'cycles.edit',
    'update' => 'cycles.update',
    'destroy' => 'cycles.destroy',
    ]);

    // Membros (vinculação ao ciclo)
    Route::resource('members', DiscipleshipMemberController::class)->names([
    'index' => 'members.index',
    'create' => 'members.create',
    'store' => 'members.store',
    'show' => 'members.show',
    'edit' => 'members.edit',
    'update' => 'members.update',
    'destroy' => 'members.destroy',
    ]);

    // Encontros
    Route::resource('meetings', DiscipleshipMeetingController::class)->names([
    'index' => 'meetings.index',
    'create' => 'meetings.create',
    'store' => 'meetings.store',
    'show' => 'meetings.show',
    'edit' => 'meetings.edit',
    'update' => 'meetings.update',
    'destroy' => 'meetings.destroy',
    ]);

    // Indicadores
    Route::resource('indicators', DiscipleshipIndicatorController::class)->names([
    'index' => 'indicators.index',
    'create' => 'indicators.create',
    'store' => 'indicators.store',
    'edit' => 'indicators.edit',
    'update' => 'indicators.update',
    'destroy' => 'indicators.destroy',
    ]);
    Route::post('indicators/value', [DiscipleshipIndicatorController::class, 'storeValue'])->name('indicators.value.store');

    // Propósitos/Metas
    Route::resource('goals', DiscipleshipGoalController::class)->names([
    'index' => 'goals.index',
    'create' => 'goals.create',
    'store' => 'goals.store',
    'show' => 'goals.show',
    'edit' => 'goals.edit',
    'update' => 'goals.update',
    'destroy' => 'goals.destroy',
    ]);
    Route::get('goals/{goal}/pdf', [DiscipleshipGoalController::class, 'generatePdf'])->name('goals.pdf');

    // Ajuda
    Route::get('help', [DiscipleshipDashboardController::class, 'help'])->name('help');

    // Feedbacks
    Route::resource('feedbacks', DiscipleshipFeedbackController::class)->names([
    'index' => 'feedbacks.index',
    'create' => 'feedbacks.create',
    'store' => 'feedbacks.store',
    'edit' => 'feedbacks.edit',
    'update' => 'feedbacks.update',
    'destroy' => 'feedbacks.destroy',
    ]);
    });
