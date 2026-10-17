<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Eventos\CampaignController;
use App\Http\Controllers\Eventos\CampaignExpenseController;
use App\Http\Controllers\Eventos\CampaignSponsorController;
use App\Http\Controllers\Eventos\CampaignInstallmentController;
use App\Http\Controllers\Eventos\CampaignReminderController;

// Módulo Gestor de Eventos: campanhas de arrecadação e gastos dos eventos
// (independentes da contabilidade do módulo Financeiro).
Route::prefix('eventos')->name('eventos.')->middleware('module.access:eventos')->group(function () {
    Route::get('/', fn () => redirect()->route('eventos.campaigns.index'))->name('index');

    // Gastos de todos os eventos
    Route::get('/gastos', [CampaignExpenseController::class, 'all'])->name('expenses.all');

    Route::prefix('campanhas')->name('campaigns.')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::get('/criar', [CampaignController::class, 'create'])->name('create');
        Route::post('/', [CampaignController::class, 'store'])->name('store');

        Route::get('/membros/buscar', [CampaignSponsorController::class, 'searchMembers'])->name('members.search');

        // Lembretes automáticos de parcelas em atraso
        Route::get('/lembretes', [CampaignReminderController::class, 'globalEdit'])->name('reminders.global');
        Route::put('/lembretes', [CampaignReminderController::class, 'globalUpdate'])->name('reminders.global.update');
        Route::post('/patrocinadores/{sponsor}/lembrete', [CampaignReminderController::class, 'sendNow'])->name('reminders.send-now');
        Route::post('/patrocinadores/{sponsor}/lembretes/alternar', [CampaignReminderController::class, 'toggleSponsor'])->name('reminders.toggle-sponsor');

        Route::post('/{campaign}/patrocinadores', [CampaignSponsorController::class, 'store'])->whereNumber('campaign')->name('sponsors.store');
        Route::put('/patrocinadores/{sponsor}', [CampaignSponsorController::class, 'update'])->name('sponsors.update');
        Route::delete('/patrocinadores/{sponsor}', [CampaignSponsorController::class, 'destroy'])->name('sponsors.destroy');
        Route::get('/patrocinadores/{sponsor}/carne', [CampaignSponsorController::class, 'carne'])->name('sponsors.carne');
        Route::get('/patrocinadores/{sponsor}/parcelas', [CampaignSponsorController::class, 'installments'])->name('sponsors.installments');
        Route::post('/{campaign}/cobrar-atrasados', [CampaignSponsorController::class, 'chargeOverdue'])->whereNumber('campaign')->name('sponsors.charge-overdue');

        Route::post('/parcelas/{installment}/pagar', [CampaignInstallmentController::class, 'pay'])->name('installments.pay');
        Route::post('/parcelas/pagar-lote', [CampaignInstallmentController::class, 'payBatch'])->name('installments.pay-batch');
        Route::post('/parcelas/{installment}/estornar', [CampaignInstallmentController::class, 'reverse'])->name('installments.reverse');
        Route::get('/parcelas/{installment}/recibo', [CampaignInstallmentController::class, 'receipt'])->name('installments.receipt');
        Route::post('/parcelas/{installment}/reenviar', [CampaignInstallmentController::class, 'resendReceipt'])->name('installments.resend');

        // Gastos do evento (com comprovante obrigatório)
        Route::get('/{campaign}/gastos', [CampaignExpenseController::class, 'index'])->whereNumber('campaign')->name('expenses.index');
        Route::post('/{campaign}/gastos', [CampaignExpenseController::class, 'store'])->whereNumber('campaign')->name('expenses.store');
        Route::put('/gastos/{expense}', [CampaignExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/gastos/{expense}', [CampaignExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::get('/gastos/comprovantes/{attachment}', [CampaignExpenseController::class, 'attachment'])->name('expenses.attachment');

        Route::get('/{campaign}', [CampaignController::class, 'show'])->whereNumber('campaign')->name('show');
        Route::get('/{campaign}/editar', [CampaignController::class, 'edit'])->whereNumber('campaign')->name('edit');
        Route::put('/{campaign}', [CampaignController::class, 'update'])->whereNumber('campaign')->name('update');
        Route::delete('/{campaign}', [CampaignController::class, 'destroy'])->whereNumber('campaign')->name('destroy');
        Route::get('/{campaign}/carnes', [CampaignController::class, 'carnesLote'])->whereNumber('campaign')->name('carnes');
        Route::get('/{campaign}/lembretes', [CampaignReminderController::class, 'edit'])->whereNumber('campaign')->name('reminders.edit');
        Route::put('/{campaign}/lembretes', [CampaignReminderController::class, 'update'])->whereNumber('campaign')->name('reminders.update');
        Route::post('/{campaign}/lembretes/previa', [CampaignReminderController::class, 'preview'])->whereNumber('campaign')->name('reminders.preview');
        Route::post('/{campaign}/lembretes/teste', [CampaignReminderController::class, 'test'])->whereNumber('campaign')->name('reminders.test');
        Route::get('/{campaign}/lembretes/lote', [CampaignReminderController::class, 'batch'])->whereNumber('campaign')->name('reminders.batch');

        Route::get('/{campaign}/prestacao-contas', [CampaignController::class, 'report'])->whereNumber('campaign')->name('report');
        Route::get('/{campaign}/prestacao-contas/pdf', [CampaignController::class, 'reportPdf'])->whereNumber('campaign')->name('report.pdf');
        Route::get('/{campaign}/prestacao-contas/excel', [CampaignController::class, 'reportExcel'])->whereNumber('campaign')->name('report.excel');
    });
});

// Links antigos (campanhas ficavam dentro do Financeiro): redireciona para o novo módulo.
Route::get('financial/campaigns/{path?}', function (?string $path = null) {
    $query = request()->getQueryString();

    return redirect('/eventos/campanhas' . ($path ? '/' . $path : '') . ($query ? '?' . $query : ''), 301);
})->where('path', '.*');
