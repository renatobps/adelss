<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PermissionController;


Route::get('/permissoes', [PermissionController::class, 'index'])->name('permissions.index');
Route::put('/permissoes/{member}', [PermissionController::class, 'update'])->name('permissions.update');
Route::put('/permissoes/funcoes/{role}', [PermissionController::class, 'updateRole'])->name('permissions.update-role');
