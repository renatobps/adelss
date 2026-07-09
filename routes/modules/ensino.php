<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ensino\EstudosController;
use App\Http\Controllers\Ensino\EscolasController;
use App\Http\Controllers\Ensino\TurmasController;


// Rotas do módulo de Ensino (admin total, outros só ver estudos)
Route::prefix('ensino')->name('ensino.')->middleware('module.access:ensino')->group(function () {
    // Estudos
    Route::resource('estudos', EstudosController::class)->names([
    'index' => 'estudos.index',
    'create' => 'estudos.create',
    'store' => 'estudos.store',
    'show' => 'estudos.show',
    'edit' => 'estudos.edit',
    'update' => 'estudos.update',
    'destroy' => 'estudos.destroy',
    ]);

    // Escolas
    Route::resource('escolas', EscolasController::class)->names([
    'index' => 'escolas.index',
    'create' => 'escolas.create',
    'store' => 'escolas.store',
    'show' => 'escolas.show',
    'edit' => 'escolas.edit',
    'update' => 'escolas.update',
    'destroy' => 'escolas.destroy',
    ]);

    // Turmas
    Route::resource('turmas', TurmasController::class)->names([
    'index' => 'turmas.index',
    'create' => 'turmas.create',
    'store' => 'turmas.store',
    'show' => 'turmas.show',
    'edit' => 'turmas.edit',
    'update' => 'turmas.update',
    'destroy' => 'turmas.destroy',
    ]);

    // Rotas aninhadas para turmas (dentro do prefixo 'ensino')
Route::prefix('turmas/{turma}')->name('turmas.')->group(function () {
    // Alunos
    Route::post('students', [TurmasController::class, 'storeStudents'])->name('students.store');
    Route::delete('students/{member}', [TurmasController::class, 'removeStudent'])->name('students.destroy');
    
    // Disciplinas
    Route::post('disciplines', [TurmasController::class, 'storeDiscipline'])->name('disciplines.store');
    Route::put('disciplines/{discipline}', [TurmasController::class, 'updateDiscipline'])->name('disciplines.update');
    Route::delete('disciplines/{discipline}', [TurmasController::class, 'destroyDiscipline'])->name('disciplines.destroy');
    
    // Aulas
    Route::post('lessons', [TurmasController::class, 'storeLesson'])->name('lessons.store');
    Route::get('lessons/{lesson}', [TurmasController::class, 'showLesson'])->name('lessons.show');
    Route::put('lessons/{lesson}', [TurmasController::class, 'updateLesson'])->name('lessons.update');
    Route::delete('lessons/{lesson}', [TurmasController::class, 'destroyLesson'])->name('lessons.destroy');
    
    // Arquivos
    Route::post('files', [TurmasController::class, 'storeFile'])->name('files.store');
    Route::delete('files/{file}', [TurmasController::class, 'destroyFile'])->name('files.destroy');
    
    // Relatórios
    Route::get('reports/frequency-monthly', [TurmasController::class, 'frequencyMonthly'])->name('reports.frequency-monthly');
    });

    });
