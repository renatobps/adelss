<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MoriahController;
use App\Http\Controllers\MoriahFunctionController;
use App\Http\Controllers\RepertorioController;
use App\Http\Controllers\MoriahScheduleController;
use App\Http\Controllers\MoriahUnavailabilityController;


// Rotas do módulo Moriah
Route::prefix('moriah')->name('moriah.')->middleware('module.access:moriah')->group(function () {
    Route::get('ministerio', [MoriahController::class, 'ministerio'])->name('ministerio');
    Route::get('members/{member}/functions', [MoriahController::class, 'getMemberFunctions'])->name('members.functions.get');
    Route::post('members/{member}/functions', [MoriahController::class, 'updateMemberFunctions'])->name('members.functions.update');
    Route::post('members/add', [MoriahController::class, 'addMemberToMinistry'])->name('members.add');
    Route::delete('members/{member}/remove', [MoriahController::class, 'removeMemberFromMinistry'])->name('members.remove');
    Route::post('banner/update', [MoriahController::class, 'updateBanner'])->name('banner.update');
    Route::post('logo/update', [MoriahController::class, 'updateLogo'])->name('logo.update');
    
    // Rotas de Funções
    Route::get('funcoes', [MoriahFunctionController::class, 'index'])->name('funcoes.index');
    Route::post('funcoes', [MoriahFunctionController::class, 'store'])->name('funcoes.store');
    Route::put('funcoes/{funcao}', [MoriahFunctionController::class, 'update'])->name('funcoes.update');
    Route::delete('funcoes/{funcao}', [MoriahFunctionController::class, 'destroy'])->name('funcoes.destroy');
    
    // Rotas de Repertório
    Route::get('repertorio', [RepertorioController::class, 'index'])->name('repertorio.index');
    Route::get('repertorio/import', [RepertorioController::class, 'import'])->name('repertorio.import');
    Route::get('repertorio/import/template', [RepertorioController::class, 'downloadTemplate'])->name('repertorio.import.template');
    Route::post('repertorio/import', [RepertorioController::class, 'processImport'])->name('repertorio.import.process');
    Route::get('repertorio/songs/{song}', [RepertorioController::class, 'showSong'])->name('repertorio.songs.show');
    Route::post('repertorio/preencher-youtube', [RepertorioController::class, 'preencherYoutube'])->name('repertorio.preencher.youtube');
    Route::post('repertorio/songs', [RepertorioController::class, 'storeSong'])->name('repertorio.songs.store');
    Route::put('repertorio/songs/{song}', [RepertorioController::class, 'updateSong'])->name('repertorio.songs.update');
    Route::delete('repertorio/songs/{song}', [RepertorioController::class, 'destroySong'])->name('repertorio.songs.destroy');
    Route::post('repertorio/folders', [RepertorioController::class, 'storeFolder'])->name('repertorio.folders.store');
    Route::put('repertorio/folders/{folder}', [RepertorioController::class, 'updateFolder'])->name('repertorio.folders.update');
    Route::delete('repertorio/folders/{folder}', [RepertorioController::class, 'destroyFolder'])->name('repertorio.folders.destroy');
    
    // Rotas de Escalas do Moriah
    Route::resource('schedules', MoriahScheduleController::class)->parameters([
        'schedules' => 'schedule'
    ])->names([
        'index' => 'schedules.index',
        'create' => 'schedules.create',
        'store' => 'schedules.store',
        'show' => 'schedules.show',
        'edit' => 'schedules.edit',
        'update' => 'schedules.update',
        'destroy' => 'schedules.destroy',
    ]);
    
    // Rotas para confirmação de membros nas escalas do Moriah
    Route::put('schedules/members/{pivotId}/confirm', [MoriahScheduleController::class, 'confirmMember'])->name('schedules.members.confirm');
    Route::put('schedules/members/{pivotId}/reject', [MoriahScheduleController::class, 'rejectMember'])->name('schedules.members.reject');
    Route::put('schedules/members/{pivotId}/status', [MoriahScheduleController::class, 'updateMemberStatus'])->name('schedules.members.updateStatus');
    Route::get('schedules/{id}/pdf', [MoriahScheduleController::class, 'generatePdf'])->name('schedules.pdf');

    // Rotas de Indisponibilidades
    Route::get('unavailabilities', [MoriahUnavailabilityController::class, 'index'])->name('unavailabilities.index');
    Route::post('unavailabilities', [MoriahUnavailabilityController::class, 'store'])->name('unavailabilities.store');
    Route::delete('unavailabilities/{id}', [MoriahUnavailabilityController::class, 'destroy'])->name('unavailabilities.destroy');
    Route::post('unavailabilities/check', [MoriahUnavailabilityController::class, 'checkUnavailabilities'])->name('unavailabilities.check');
});
