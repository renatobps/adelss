<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DepartmentEventController;
use App\Http\Controllers\DepartmentAdministrationController;
use App\Http\Controllers\DepartmentProjectController;
use App\Http\Controllers\DepartmentProjectActionController;
use App\Http\Controllers\DepartmentEventNotificationController;
use Illuminate\Support\Facades\Route;

// Preserve existing URLs and permission keys when moving the navigation.
Route::middleware('module.access:servico')->group(function () {
    Route::resource('departments', DepartmentController::class);
    Route::resource('departments.events', DepartmentEventController::class)->scoped();
    Route::get('departments/{department}/notifications', [DepartmentEventNotificationController::class, 'index'])->name('departments.notifications.index');
    Route::scopeBindings()->group(function () {
        Route::get('departments/{department}/events/{event}/notifications', [DepartmentEventNotificationController::class, 'edit'])->name('departments.events.notifications.edit');
        Route::put('departments/{department}/events/{event}/notifications', [DepartmentEventNotificationController::class, 'update'])->name('departments.events.notifications.update');
        Route::post('departments/{department}/events/{event}/notifications/cancel', [DepartmentEventNotificationController::class, 'cancel'])->name('departments.events.notifications.cancel');
    });
    Route::resource('departments.administration', DepartmentAdministrationController::class)
        ->parameters(['administration' => 'role'])->only(['index', 'store', 'update', 'destroy'])->scoped();
    Route::resource('departments.projects', DepartmentProjectController::class)->scoped();
    Route::resource('departments.projects.actions', DepartmentProjectActionController::class)
        ->except(['index', 'show'])->scoped();
});
