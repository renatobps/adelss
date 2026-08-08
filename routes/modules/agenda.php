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
    Route::get('eventos/{event}/qrcode', [EventosController::class, 'qrcode'])->name('eventos.qrcode');
    Route::get('eventos/{event}/cartaz', [EventosController::class, 'cartaz'])->name('eventos.cartaz');
    Route::get('eventos/{event}/check-in', [EventosController::class, 'checkIn'])->name('eventos.check-in');
    Route::post('eventos/{event}/check-in/validar', [EventosController::class, 'checkInValidate'])->name('eventos.check-in.validate');
    Route::get('eventos/{event}/inscricoes', [EventosController::class, 'registrations'])->name('eventos.registrations');
    Route::get('eventos/{event}/inscricoes/exportar', [EventosController::class, 'exportRegistrations'])->name('eventos.registrations.export');
    Route::get('eventos/{event}/inscricoes/exportar-pdf', [EventosController::class, 'exportRegistrationsPdf'])->name('eventos.registrations.export-pdf');
    Route::post('eventos/{event}/inscricoes/lote', [EventosController::class, 'bulkRegistrations'])->name('eventos.registrations.bulk');
    Route::patch('eventos/{event}/inscricoes/{registration}', [EventosController::class, 'updateRegistrationStatus'])->whereNumber('registration')->name('eventos.registrations.status');
    Route::patch('eventos/{event}/inscricoes/{registration}/dados', [EventosController::class, 'updateRegistrationData'])->whereNumber('registration')->name('eventos.registrations.update');
    Route::delete('eventos/{event}/inscricoes/{registration}', [EventosController::class, 'destroyRegistration'])->whereNumber('registration')->name('eventos.registrations.destroy');
    Route::post('eventos/{event}/inscricoes/{registration}/restaurar', [EventosController::class, 'restoreRegistration'])->whereNumber('registration')->withTrashed()->name('eventos.registrations.restore');
    Route::post('eventos/{event}/inscricoes/{registration}/pix-whatsapp', [EventosController::class, 'sendPixToRegistrationWhatsapp'])->whereNumber('registration')->name('eventos.registrations.pix-whatsapp');
    Route::post('eventos/{event}/inscricoes/{registration}/reenviar-comprovante', [EventosController::class, 'resendRegistrationReceipt'])->whereNumber('registration')->name('eventos.registrations.resend-receipt');
    Route::get('eventos/{event}/inscricoes/{registration}/comprovante', [EventosController::class, 'registrationReceiptPdf'])->whereNumber('registration')->name('eventos.registrations.receipt-pdf');
    Route::post('eventos/editor-upload', [EventosController::class, 'uploadEditorImage'])->name('eventos.editor-upload');
    });
