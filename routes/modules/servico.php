<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\VolunteerController;
use App\Http\Controllers\ServiceAreaController;
use App\Http\Controllers\ServiceScheduleController;
use App\Http\Controllers\MonthlyCultoScheduleController;
use App\Http\Controllers\ServiceHistoryController;
use App\Http\Controllers\VolunteerReportController;


// Rotas do módulo de Departamentos (somente admin)
Route::resource('departments', DepartmentController::class)->middleware('module.access:servico');

// Rotas do módulo de Serviço - Voluntários (somente admin)
Route::prefix('servico/voluntarios')->name('voluntarios.')->middleware('module.access:servico')->group(function () {
Route::resource('cadastro', VolunteerController::class)->parameters([
    'cadastro' => 'volunteer'
])->names([
    'index' => 'cadastro.index',
    'create' => 'cadastro.create',
    'store' => 'cadastro.store',
    'show' => 'cadastro.show',
    'edit' => 'cadastro.edit',
    'update' => 'cadastro.update',
    'destroy' => 'cadastro.destroy',
]);

Route::resource('areas', ServiceAreaController::class)->names([
    'index' => 'areas.index',
    'create' => 'areas.create',
    'store' => 'areas.store',
    'show' => 'areas.show',
    'edit' => 'areas.edit',
    'update' => 'areas.update',
    'destroy' => 'areas.destroy',
]);


// Histórico de Serviço
Route::get('historico', [ServiceHistoryController::class, 'index'])->name('historico.index');
Route::get('historico/{history}', [ServiceHistoryController::class, 'show'])->name('historico.show');
Route::get('historico/voluntario/{volunteer}', [ServiceHistoryController::class, 'showByVolunteer'])->name('historico.volunteer');

// Escalas
Route::get('escalas', [ServiceScheduleController::class, 'index'])->name('escalas.index');
Route::get('escalas/create', [ServiceScheduleController::class, 'create'])->name('escalas.create');
Route::post('escalas/step1', [ServiceScheduleController::class, 'storeStep1'])->name('escalas.store.step1');
Route::post('escalas/step2', [ServiceScheduleController::class, 'storeStep2'])->name('escalas.store.step2');
Route::post('escalas/step3', [ServiceScheduleController::class, 'storeStep3'])->name('escalas.store.step3');
Route::post('escalas', [ServiceScheduleController::class, 'store'])->name('escalas.store');
Route::get('escalas/{escala}', [ServiceScheduleController::class, 'show'])->name('escalas.show');
Route::get('escalas/{escala}/edit', [ServiceScheduleController::class, 'edit'])->name('escalas.edit');
Route::put('escalas/{escala}', [ServiceScheduleController::class, 'update'])->name('escalas.update');
Route::delete('escalas/{escala}', [ServiceScheduleController::class, 'destroy'])->name('escalas.destroy');
Route::post('escalas/{escala}/duplicate', [ServiceScheduleController::class, 'duplicate'])->name('escalas.duplicate');
Route::put('escalas/{escala}/cancel', [ServiceScheduleController::class, 'cancel'])->name('escalas.cancel');
Route::put('escalas/{escala}/publish', [ServiceScheduleController::class, 'publish'])->name('escalas.publish');
Route::put('escalas/{escala}/status', [ServiceScheduleController::class, 'updateStatus'])->name('escalas.update-status');
Route::get('escalas/api/suggested-volunteers', [ServiceScheduleController::class, 'getSuggestedVolunteers'])->name('escalas.api.suggested-volunteers');
Route::put('escalas/volunteers/{volunteer}/confirm', [ServiceScheduleController::class, 'confirmVolunteer'])->name('escalas.volunteers.confirm');
Route::put('escalas/volunteers/{volunteer}/substitute', [ServiceScheduleController::class, 'substituteVolunteer'])->name('escalas.volunteers.substitute');
Route::delete('escalas/volunteers/{volunteer}', [ServiceScheduleController::class, 'removeVolunteer'])->name('escalas.volunteers.remove');
Route::get('escalas/{escala}/pdf', [ServiceScheduleController::class, 'generatePdf'])->name('escalas.pdf');

// Escalas Mensais de Cultos
Route::get('escalas-mensais', [MonthlyCultoScheduleController::class, 'index'])->name('escalas-mensais.index');
Route::get('escalas-mensais/create', [MonthlyCultoScheduleController::class, 'create'])->name('escalas-mensais.create');
Route::post('escalas-mensais', [MonthlyCultoScheduleController::class, 'store'])->name('escalas-mensais.store');
Route::post('escalas-mensais/generate-monthly', [MonthlyCultoScheduleController::class, 'generateMonthly'])->name('escalas-mensais.generate-monthly');
Route::post('escalas-mensais/preletor/manual', [MonthlyCultoScheduleController::class, 'storeManualPreletor'])->name('escalas-mensais.preletor.manual');
Route::get('escalas-mensais/{escala}', [MonthlyCultoScheduleController::class, 'show'])->name('escalas-mensais.show');
Route::get('escalas-mensais/{escala}/edit', [MonthlyCultoScheduleController::class, 'edit'])->name('escalas-mensais.edit');
Route::put('escalas-mensais/{escala}', [MonthlyCultoScheduleController::class, 'update'])->name('escalas-mensais.update');
Route::put('escalas-mensais/{escala}/status', [MonthlyCultoScheduleController::class, 'updateStatus'])->name('escalas-mensais.update-status');
Route::put('escalas-mensais/{escala}/publish', [MonthlyCultoScheduleController::class, 'publish'])->name('escalas-mensais.publish');
Route::put('escalas-mensais/{escala}/cancel', [MonthlyCultoScheduleController::class, 'cancel'])->name('escalas-mensais.cancel');
Route::get('escalas-mensais/{escala}/pdf', [MonthlyCultoScheduleController::class, 'generatePdf'])->name('escalas-mensais.pdf');
Route::get('escalas-mensais/volunteers/available', [MonthlyCultoScheduleController::class, 'getAvailableVolunteers'])->name('escalas-mensais.volunteers.available');
Route::post('escalas-mensais/{escala}/volunteers/add', [MonthlyCultoScheduleController::class, 'addVolunteer'])->name('escalas-mensais.volunteers.add');
Route::post('escalas-mensais/volunteers/notify', [MonthlyCultoScheduleController::class, 'notifyVolunteer'])->name('escalas-mensais.volunteers.notify');
Route::post('escalas-mensais/{escala}/volunteers/notify-all', [MonthlyCultoScheduleController::class, 'notifyAllVolunteers'])->name('escalas-mensais.volunteers.notify-all');
Route::put('escalas-mensais/volunteers/{pivot}/confirm', [MonthlyCultoScheduleController::class, 'confirmVolunteer'])->name('escalas-mensais.volunteers.confirm');
Route::put('escalas-mensais/volunteers/{pivot}/substitute', [MonthlyCultoScheduleController::class, 'substituteVolunteer'])->name('escalas-mensais.volunteers.substitute');
Route::delete('escalas-mensais/volunteers/{pivot}', [MonthlyCultoScheduleController::class, 'removeVolunteer'])->name('escalas-mensais.volunteers.remove');
Route::delete('escalas-mensais/{escala}', [MonthlyCultoScheduleController::class, 'destroy'])->name('escalas-mensais.destroy');

// Relatórios
Route::prefix('relatorios')->name('relatorios.')->group(function () {
    Route::get('/', [VolunteerReportController::class, 'dashboard'])->name('dashboard');
    Route::get('ativos-por-area', [VolunteerReportController::class, 'activeByArea'])->name('active-by-area');
    Route::get('mais-servem', [VolunteerReportController::class, 'topVolunteers'])->name('top-volunteers');
    Route::get('inativos', [VolunteerReportController::class, 'inactiveVolunteers'])->name('inactive');
    Route::get('deficit', [VolunteerReportController::class, 'deficitByArea'])->name('deficit');
    Route::get('por-escala', [VolunteerReportController::class, 'bySchedule'])->name('by-schedule');
});
});
