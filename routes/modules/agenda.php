<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Agenda\EventosController;
use App\Http\Controllers\Agenda\CalendarioController;
use App\Http\Controllers\Agenda\EventController;
use App\Http\Controllers\Agenda\EventCategoryController;


// Rotas do módulo de Agenda (admin total, outros só ver)
Route::prefix('agenda')->name('agenda.')->middleware('module.access:agenda')->group(function () {
    // Calendário
    Route::get('calendario', [CalendarioController::class, 'index'])->name('calendario.index');

    // Eventos (API para FullCalendar)
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::post('events', [EventController::class, 'store'])->name('events.store');
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

    // Categorias
    Route::post('categories', [EventCategoryController::class, 'store'])->name('categories.store');
    Route::delete('categories/{category}', [EventCategoryController::class, 'destroy'])->name('categories.destroy');

    // Eventos (CRUD + landing pública)
    Route::resource('eventos', EventosController::class)->parameters(['eventos' => 'event'])->except(['show']);
    Route::post('eventos/{event}/duplicate', [EventosController::class, 'duplicate'])->name('eventos.duplicate');
    Route::get('eventos/{event}/inscricoes', [EventosController::class, 'registrations'])->name('eventos.registrations');
    Route::patch('eventos/{event}/inscricoes/{registration}', [EventosController::class, 'updateRegistrationStatus'])->name('eventos.registrations.status');
    Route::post('eventos/{event}/inscricoes/{registration}/pix-whatsapp', [EventosController::class, 'sendPixToRegistrationWhatsapp'])->name('eventos.registrations.pix-whatsapp');
    Route::post('eventos/editor-upload', [EventosController::class, 'uploadEditorImage'])->name('eventos.editor-upload');
    });
