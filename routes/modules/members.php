<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberCustomFieldController;
use App\Http\Controllers\MemberRoleController;
use App\Http\Controllers\PublicMemberRegistrationController;
use Illuminate\Support\Facades\Route;

// Cadastro público (sem login)
Route::get('cadastro-membro/{token}', [PublicMemberRegistrationController::class, 'create'])->name('members.public.create');
Route::post('cadastro-membro/{token}', [PublicMemberRegistrationController::class, 'store'])->name('members.public.store');

// Rotas do modulo de Membros
Route::middleware('module.access:members')->group(function () {
    Route::get('members/export/pdf', [MemberController::class, 'exportPdf'])->name('members.export.pdf');
    Route::get('members/export/excel', [MemberController::class, 'exportExcel'])->name('members.export.excel');
    Route::get('members/export/ficha-branco', [MemberController::class, 'blankFormPdf'])->name('members.export.blank');
    Route::post('members/public-link', [MemberController::class, 'publicLink'])->name('members.public-link');
    Route::get('members/import/tutorial', [MemberController::class, 'importTutorial'])->name('members.import.tutorial');
    Route::get('members/import/template', [MemberController::class, 'downloadTemplate'])->name('members.import.template');
    Route::post('members/import', [MemberController::class, 'import'])->name('members.import');

    Route::get('members/campos-personalizados', [MemberCustomFieldController::class, 'index'])->name('members.custom-fields.index');
    Route::post('members/campos-personalizados', [MemberCustomFieldController::class, 'store'])->name('members.custom-fields.store');
    Route::put('members/campos-personalizados/{customField}', [MemberCustomFieldController::class, 'update'])->name('members.custom-fields.update');
    Route::delete('members/campos-personalizados/{customField}', [MemberCustomFieldController::class, 'destroy'])->name('members.custom-fields.destroy');

    Route::resource('members', MemberController::class);

    Route::resource('member-roles', MemberRoleController::class);
    Route::get('member-roles/import/template', [MemberRoleController::class, 'downloadTemplate'])->name('member-roles.import.template');
    Route::post('member-roles/import', [MemberRoleController::class, 'import'])->name('member-roles.import');
});
