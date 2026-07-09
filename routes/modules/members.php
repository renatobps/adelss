<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberRoleController;

// Rotas do modulo de Membros (somente admin)
Route::resource('members', MemberController::class)->middleware('module.access:members');

// Rotas do modulo de Cargos de Membros (somente admin)
Route::resource('member-roles', MemberRoleController::class)->middleware('module.access:members');
Route::get('member-roles/import/template', [MemberRoleController::class, 'downloadTemplate'])->name('member-roles.import.template');
Route::post('member-roles/import', [MemberRoleController::class, 'import'])->name('member-roles.import');

// Rotas de importacao de membros (somente admin)
Route::prefix('members')->name('members.')->middleware('module.access:members')->group(function () {
    Route::get('import/tutorial', [MemberController::class, 'importTutorial'])->name('import.tutorial');
    Route::get('import/template', [MemberController::class, 'downloadTemplate'])->name('import.template');
    Route::post('import', [MemberController::class, 'import'])->name('import');
});
