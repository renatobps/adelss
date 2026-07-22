<?php

use App\Http\Controllers\Cultos\ServiceReportController;
use App\Http\Controllers\Cultos\ServiceReportSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('relatorios-culto')->name('cultos.')->middleware('module.access:cultos')->group(function () {
    Route::get('/', [ServiceReportController::class, 'index'])->name('index');
    Route::get('/analises', [ServiceReportController::class, 'analyses'])->name('analyses');
    Route::get('/alertas', [ServiceReportController::class, 'alerts'])->name('alerts');
    Route::get('/novo', [ServiceReportController::class, 'create'])->name('create');
    Route::post('/', [ServiceReportController::class, 'store'])->name('store');
    Route::get('/pdf-consolidado', [ServiceReportController::class, 'pdfConsolidated'])->name('pdf.consolidated');
    Route::get('/financeiro-resumo', [ServiceReportController::class, 'financialSummary'])->name('financial-summary');
    Route::get('/configuracoes', [ServiceReportSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/configuracoes', [ServiceReportSettingController::class, 'update'])->name('settings.update');
    Route::get('/{serviceReport}', [ServiceReportController::class, 'show'])->name('show');
    Route::get('/{serviceReport}/pdf', [ServiceReportController::class, 'pdf'])->name('pdf');
    Route::get('/{serviceReport}/editar', [ServiceReportController::class, 'edit'])->name('edit');
    Route::put('/{serviceReport}', [ServiceReportController::class, 'update'])->name('update');
    Route::post('/{serviceReport}/finalizar', [ServiceReportController::class, 'finalize'])->name('finalize');
    Route::delete('/{serviceReport}', [ServiceReportController::class, 'destroy'])->name('destroy');
});
