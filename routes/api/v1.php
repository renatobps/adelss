<?php

use App\Http\Controllers\Api\V1\AgendaEventController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\FinancialCatalogApiController;
use App\Http\Controllers\Api\V1\FinancialOpsApiController;
use App\Http\Controllers\Api\V1\FinancialSummaryController;
use App\Http\Controllers\Api\V1\FinancialTransactionApiController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MeHomeController;
use App\Http\Controllers\Api\V1\MePgiController;
use App\Http\Controllers\Api\V1\MeProfileController;
use App\Http\Controllers\Api\V1\MeScheduleController;
use App\Http\Controllers\Api\V1\MembersController;
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
        Route::get('me/profile', [MeProfileController::class, 'show'])->name('me.profile.show');
        Route::put('me/profile', [MeProfileController::class, 'update'])->name('me.profile.update');
        Route::get('me/contributions', [MeProfileController::class, 'contributions'])->name('me.contributions');
        Route::get('me/ensino', [MeProfileController::class, 'ensino'])->name('me.ensino');
        Route::get('me/discipleship', [MeProfileController::class, 'discipleship'])->name('me.discipleship');

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

        Route::get('financial/module', [FinancialSummaryController::class, 'module'])->name('financial.module');
        Route::get('financial/summary', [FinancialSummaryController::class, 'show'])->name('financial.summary');
        Route::get('financial/options', [FinancialSummaryController::class, 'options'])->name('financial.options');
        Route::get('financial/reports', [FinancialSummaryController::class, 'reports'])->name('financial.reports');
        Route::get('financial/receipts', [FinancialTransactionApiController::class, 'receipts'])->name('financial.receipts');
        Route::get('financial/transactions/{transaction}/receipt', [FinancialTransactionApiController::class, 'receiptPdf'])->name('financial.transactions.receipt');
        Route::get('financial/transactions/{transaction}/attachments/{attachment}', [FinancialTransactionApiController::class, 'showAttachment'])->name('financial.transactions.attachments.show');

        Route::get('financial/transactions', [FinancialTransactionApiController::class, 'index'])->name('financial.transactions.index');
        Route::post('financial/transactions', [FinancialTransactionApiController::class, 'store'])->name('financial.transactions.store');
        Route::get('financial/transactions/{transaction}', [FinancialTransactionApiController::class, 'show'])->name('financial.transactions.show');
        Route::put('financial/transactions/{transaction}', [FinancialTransactionApiController::class, 'update'])->name('financial.transactions.update');
        Route::delete('financial/transactions/{transaction}', [FinancialTransactionApiController::class, 'destroy'])->name('financial.transactions.destroy');
        Route::post('financial/transactions/{transaction}/attachments', [FinancialTransactionApiController::class, 'storeAttachments'])->name('financial.transactions.attachments.store');
        Route::delete('financial/transactions/{transaction}/attachments/{attachment}', [FinancialTransactionApiController::class, 'destroyAttachment'])->name('financial.transactions.attachments.destroy');
        Route::post('financial/transactions/{transaction}/pay', [FinancialTransactionApiController::class, 'pay'])->name('financial.transactions.pay');
        Route::post('financial/transactions/{transaction}/duplicate', [FinancialTransactionApiController::class, 'duplicate'])->name('financial.transactions.duplicate');
        Route::put('financial/transactions/{transaction}/correction', [FinancialTransactionApiController::class, 'correct'])->name('financial.transactions.correct');

        Route::get('financial/accounts', [FinancialCatalogApiController::class, 'accounts'])->name('financial.accounts.index');
        Route::post('financial/accounts', [FinancialCatalogApiController::class, 'storeAccount'])->name('financial.accounts.store');
        Route::put('financial/accounts/{account}', [FinancialCatalogApiController::class, 'updateAccount'])->name('financial.accounts.update');
        Route::delete('financial/accounts/{account}', [FinancialCatalogApiController::class, 'destroyAccount'])->name('financial.accounts.destroy');
        Route::post('financial/accounts/{account}/toggle', [FinancialCatalogApiController::class, 'toggleAccount'])->name('financial.accounts.toggle');
        Route::post('financial/transfers', [FinancialCatalogApiController::class, 'storeTransfer'])->name('financial.transfers.store');
        Route::delete('financial/transfers/{transfer}', [FinancialCatalogApiController::class, 'destroyTransfer'])->name('financial.transfers.destroy');

        Route::get('financial/categories', [FinancialCatalogApiController::class, 'categories'])->name('financial.categories.index');
        Route::post('financial/categories', [FinancialCatalogApiController::class, 'storeCategory'])->name('financial.categories.store');
        Route::put('financial/categories/{category}', [FinancialCatalogApiController::class, 'updateCategory'])->name('financial.categories.update');
        Route::delete('financial/categories/{category}', [FinancialCatalogApiController::class, 'destroyCategory'])->name('financial.categories.destroy');

        Route::get('financial/contacts', [FinancialCatalogApiController::class, 'contacts'])->name('financial.contacts.index');
        Route::post('financial/contacts', [FinancialCatalogApiController::class, 'storeContact'])->name('financial.contacts.store');
        Route::put('financial/contacts/{contact}', [FinancialCatalogApiController::class, 'updateContact'])->name('financial.contacts.update');
        Route::delete('financial/contacts/{contact}', [FinancialCatalogApiController::class, 'destroyContact'])->name('financial.contacts.destroy');

        Route::get('financial/cost-centers', [FinancialCatalogApiController::class, 'costCenters'])->name('financial.cost-centers.index');
        Route::post('financial/cost-centers', [FinancialCatalogApiController::class, 'storeCostCenter'])->name('financial.cost-centers.store');
        Route::put('financial/cost-centers/{costCenter}', [FinancialCatalogApiController::class, 'updateCostCenter'])->name('financial.cost-centers.update');
        Route::delete('financial/cost-centers/{costCenter}', [FinancialCatalogApiController::class, 'destroyCostCenter'])->name('financial.cost-centers.destroy');

        Route::get('financial/fixed-expenses', [FinancialOpsApiController::class, 'fixedExpenses'])->name('financial.fixed-expenses.index');
        Route::post('financial/fixed-expenses', [FinancialOpsApiController::class, 'storeFixedExpense'])->name('financial.fixed-expenses.store');
        Route::post('financial/fixed-expenses/generate', [FinancialOpsApiController::class, 'generateFixedExpenses'])->name('financial.fixed-expenses.generate');
        Route::post('financial/fixed-expenses/transactions/{transaction}/pay', [FinancialOpsApiController::class, 'payFixedExpense'])->name('financial.fixed-expenses.pay');
        Route::put('financial/fixed-expenses/{fixedExpense}', [FinancialOpsApiController::class, 'updateFixedExpense'])->name('financial.fixed-expenses.update');
        Route::delete('financial/fixed-expenses/{fixedExpense}', [FinancialOpsApiController::class, 'destroyFixedExpense'])->name('financial.fixed-expenses.destroy');
        Route::post('financial/fixed-expenses/{fixedExpense}/toggle', [FinancialOpsApiController::class, 'toggleFixedExpense'])->name('financial.fixed-expenses.toggle');

        Route::get('financial/campaigns', [FinancialOpsApiController::class, 'campaigns'])->name('financial.campaigns.index');
        Route::post('financial/campaigns', [FinancialOpsApiController::class, 'storeCampaign'])->name('financial.campaigns.store');
        Route::get('financial/campaigns/{campaign}', [FinancialOpsApiController::class, 'showCampaign'])->name('financial.campaigns.show');
        Route::post('financial/installments/{installment}/pay', [FinancialOpsApiController::class, 'payInstallment'])->name('financial.installments.pay');
        Route::post('financial/installments/{installment}/reverse', [FinancialOpsApiController::class, 'reverseInstallment'])->name('financial.installments.reverse');

        Route::get('financial/automations', [FinancialOpsApiController::class, 'automations'])->name('financial.automations.index');
        Route::post('financial/automations/{automation}/toggle', [FinancialOpsApiController::class, 'toggleAutomation'])->name('financial.automations.toggle');

        Route::get('members/options', [MembersController::class, 'options'])->name('members.options');
        Route::get('members', [MembersController::class, 'index'])->name('members.index');
        Route::post('members', [MembersController::class, 'store'])->name('members.store');
        Route::get('members/{member}', [MembersController::class, 'show'])->name('members.show');
        Route::put('members/{member}', [MembersController::class, 'update'])->name('members.update');

        Route::get('agenda/events', [AgendaEventController::class, 'index'])->name('agenda.events.index');
        Route::get('agenda/events/{event}', [AgendaEventController::class, 'show'])->name('agenda.events.show');
    });
});
