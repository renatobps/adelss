<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomePageSettingController;

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::prefix('pagina-principal')->name('pagina-principal.')->middleware('module.access:pagina-principal')->group(function () {
    Route::get('/', [HomePageSettingController::class, 'edit'])->name('edit');
    Route::put('/', [HomePageSettingController::class, 'update'])->name('update');
});
