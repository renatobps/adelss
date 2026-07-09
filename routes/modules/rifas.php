<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Rifas\CartelaController as RifaCartelaController;
use App\Http\Controllers\Rifas\RelatorioController as RifaRelatorioController;
use App\Http\Controllers\Rifas\RifaController;
use App\Http\Controllers\Rifas\VendaRapidaController;


// Módulo de Rifas
Route::prefix('rifas')->name('rifas.')->middleware('module.access:rifas')->group(function () {
    Route::get('/', [RifaController::class, 'index'])->name('index');
    Route::get('create', [RifaController::class, 'create'])->name('create');
    Route::post('/', [RifaController::class, 'store'])->name('store');
    Route::get('relatorios/dashboard', [RifaRelatorioController::class, 'index'])->name('relatorios.dashboard');
    Route::get('relatorios/export/csv', [RifaRelatorioController::class, 'exportCsv'])->name('relatorios.export.csv');
    Route::get('relatorios/export/pdf', [RifaRelatorioController::class, 'exportPdf'])->name('relatorios.export.pdf');
    Route::get('{rifa}', [RifaController::class, 'show'])->name('show');
    Route::get('{rifa}/sorteios', [RifaController::class, 'sorteios'])->name('sorteios.index');
    Route::delete('{rifa}/sorteios/{sorteio}', [RifaController::class, 'destroySorteio'])->name('sorteios.destroy');
    Route::get('{rifa}/edit', [RifaController::class, 'edit'])->name('edit');
    Route::put('{rifa}', [RifaController::class, 'update'])->name('update');
    Route::delete('{rifa}', [RifaController::class, 'destroy'])->name('destroy');
    Route::put('{rifa}/status', [RifaController::class, 'updateStatus'])->name('status.update');
    Route::post('{rifa}/sortear', [RifaController::class, 'sortear'])->name('sortear');

    Route::get('{rifa}/cartelas', [RifaCartelaController::class, 'index'])->name('cartelas.index');
    Route::get('{rifa}/cartelas/imprimir', [RifaCartelaController::class, 'imprimir'])->name('cartelas.imprimir');

    Route::get('{rifa}/vendas/rapida', [VendaRapidaController::class, 'create'])->name('vendas.rapida.create');
    Route::post('{rifa}/vendas/rapida', [VendaRapidaController::class, 'store'])->name('vendas.rapida.store');
    Route::put('numeros/{numero}/comprador', [VendaRapidaController::class, 'atualizarComprador'])->name('numeros.comprador.update');
    Route::put('{rifa}/numeros/comprador-lote', [VendaRapidaController::class, 'atualizarCompradorEmLote'])->name('numeros.comprador.lote.update');
    Route::put('numeros/{numero}/cancelar', [VendaRapidaController::class, 'cancelarNumero'])->name('numeros.cancelar');
});
