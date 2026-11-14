<?php

use App\Http\Controllers\Admin\MercadoPagoAccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/pagamentos/mercado-pago')->name('admin.mercadopago.')->group(function () {
    Route::get('/', [MercadoPagoAccountController::class, 'index'])->name('index');
    Route::get('/{account}/editar', [MercadoPagoAccountController::class, 'edit'])->name('edit');
    Route::post('/', [MercadoPagoAccountController::class, 'store'])->middleware('throttle:5,1')->name('store');
    Route::put('/{account}', [MercadoPagoAccountController::class, 'update'])->middleware('throttle:5,1')->name('update');
});
