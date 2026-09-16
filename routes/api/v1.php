<?php

use App\Http\Controllers\Api\V1\AgendaEventController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\FinancialSummaryController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MeHomeController;
use App\Http\Controllers\Api\V1\MePgiController;
use App\Http\Controllers\Api\V1\MeScheduleController;
use App\Http\Controllers\Api\V1\PasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api-login')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('password/forgot', [PasswordController::class, 'forgot'])->name('password.forgot');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('me', [MeController::class, 'show'])->name('me');
    Route::post('password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('api.password')->group(function () {
        Route::post('devices', [DeviceTokenController::class, 'store'])->name('devices.store');
        Route::delete('devices', [DeviceTokenController::class, 'destroy'])->name('devices.destroy');

        Route::get('me/home', [MeHomeController::class, 'show'])->name('me.home');

        Route::get('me/schedules', [MeScheduleController::class, 'index'])->name('me.schedules.index');
        Route::post('me/schedules/service/{assignment}/confirm', [MeScheduleController::class, 'confirmService'])
            ->name('me.schedules.service.confirm');
        Route::post('me/schedules/monthly/{assignment}/confirm', [MeScheduleController::class, 'confirmMonthly'])
            ->name('me.schedules.monthly.confirm');
        Route::post('me/schedules/moriah/{assignment}/confirm', [MeScheduleController::class, 'confirmMoriah'])
            ->name('me.schedules.moriah.confirm');
        Route::post('me/schedules/moriah/{assignment}/reject', [MeScheduleController::class, 'rejectMoriah'])
            ->name('me.schedules.moriah.reject');

        Route::get('me/pgi', [MePgiController::class, 'show'])->name('me.pgi.show');
        Route::get('me/pgi/meetings', [MePgiController::class, 'meetings'])->name('me.pgi.meetings');
        Route::post('me/pgi/meetings', [MePgiController::class, 'storeMeeting'])->name('me.pgi.meetings.store');
        Route::get('me/pgi/meetings/{meeting}/attendance', [MePgiController::class, 'attendanceForm'])
            ->name('me.pgi.meetings.attendance');
        Route::put('me/pgi/meetings/{meeting}/attendance', [MePgiController::class, 'storeAttendance'])
            ->name('me.pgi.meetings.attendance.save');
        Route::get('me/pgi/meetings/{meeting}', [MePgiController::class, 'meeting'])->name('me.pgi.meetings.show');

        Route::get('financial/summary', [FinancialSummaryController::class, 'show'])->name('financial.summary');

        Route::get('agenda/events', [AgendaEventController::class, 'index'])->name('agenda.events.index');
        Route::get('agenda/events/{event}', [AgendaEventController::class, 'show'])->name('agenda.events.show');
    });
});
