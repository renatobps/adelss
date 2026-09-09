<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Financial\CategoryController;
use App\Http\Controllers\Financial\AccountController;
use App\Http\Controllers\Financial\CheckoutController;
use App\Http\Controllers\Financial\ContactController;
use App\Http\Controllers\Financial\CostCenterController;
use App\Http\Controllers\Financial\CorrectionController;
use App\Http\Controllers\Financial\TransactionController;
use App\Http\Controllers\Financial\ReceiptController;
use App\Http\Controllers\Financial\ReportController;
use App\Http\Controllers\Financial\ClosingReportController;
use App\Http\Controllers\Financial\ReportSignatureController;
use App\Http\Controllers\Financial\SummaryController;
use App\Http\Controllers\Financial\AutomationController;
use App\Http\Controllers\Financial\FixedExpenseController;
use App\Http\Controllers\Financial\CampaignController;
use App\Http\Controllers\Financial\CampaignSponsorController;
use App\Http\Controllers\Financial\CampaignInstallmentController;
use App\Http\Controllers\Financial\CampaignReminderController;


// Rotas do módulo Financeiro (somente admin)
Route::prefix('financial')->name('financial.')->middleware('module.access:financial')->group(function () {
    // Resumo/Dashboard
    Route::get('summary', [SummaryController::class, 'index'])->name('summary');

    // Despesas Fixas
    Route::get('fixed-expenses', [FixedExpenseController::class, 'index'])->name('fixed-expenses.index');
    Route::post('fixed-expenses', [FixedExpenseController::class, 'store'])->name('fixed-expenses.store');
    Route::post('fixed-expenses/generate', [FixedExpenseController::class, 'generate'])->name('fixed-expenses.generate');
    Route::post('fixed-expenses/transactions/{transaction}/pay', [FixedExpenseController::class, 'pay'])->name('fixed-expenses.pay');
    Route::put('fixed-expenses/{fixedExpense}', [FixedExpenseController::class, 'update'])->name('fixed-expenses.update');
    Route::delete('fixed-expenses/{fixedExpense}', [FixedExpenseController::class, 'destroy'])->name('fixed-expenses.destroy');
    Route::post('fixed-expenses/{fixedExpense}/toggle', [FixedExpenseController::class, 'toggle'])->name('fixed-expenses.toggle');

    // Automações
    Route::get('automations', [AutomationController::class, 'index'])->name('automations.index');
    Route::get('automations/members/search', [AutomationController::class, 'searchMembers'])->name('automations.members.search');
    Route::post('automations/{automation}/toggle', [AutomationController::class, 'toggle'])->name('automations.toggle');
    Route::post('automations/{automation}/test-treasurer', [AutomationController::class, 'sendTreasurerTest'])->name('automations.test-treasurer');
    Route::post('automations/{automation}/send-smart-summary', [AutomationController::class, 'sendSmartSummaryNow'])->name('automations.send-smart-summary');
    Route::put('automations/{automation}', [AutomationController::class, 'update'])->name('automations.update');

    Route::get('correction', [CorrectionController::class, 'index'])->name('correction.index');
    Route::patch('correction/{transaction}/name', [CorrectionController::class, 'updateName'])->name('correction.update-name');
    Route::patch('correction/{transaction}/description', [CorrectionController::class, 'updateDescription'])->name('correction.update-description');
    Route::patch('correction/{transaction}/amount', [CorrectionController::class, 'updateAmount'])->name('correction.update-amount');

    // Transações
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('transactions/receita', [TransactionController::class, 'storeReceita'])->name('transactions.store.receita');
    Route::post('transactions/despesa', [TransactionController::class, 'storeDespesa'])->name('transactions.store.despesa');
    Route::get('transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
    Route::post('transactions/import', [TransactionController::class, 'import'])->name('transactions.import');
    Route::post('checkout/transactions/{transaction}/pix', [CheckoutController::class, 'createPix'])->name('checkout.pix');
    Route::post('checkout/transactions/{transaction}/card', [CheckoutController::class, 'createCard'])->name('checkout.card');
    Route::get('checkout/payment-transactions/{paymentTransaction}', [CheckoutController::class, 'status'])->name('checkout.status');
    Route::resource('transactions', TransactionController::class)->except(['create', 'store'])->names([
    'index' => 'transactions.index',
    'show' => 'transactions.show',
    'edit' => 'transactions.edit',
    'update' => 'transactions.update',
    'destroy' => 'transactions.destroy',
    ]);
    Route::get('transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt');
    Route::post('transactions/{transaction}/send-receipt', [TransactionController::class, 'sendReceiptWhatsApp'])->name('transactions.send-receipt');
    Route::post('transactions/{transaction}/duplicate', [TransactionController::class, 'duplicate'])->name('transactions.duplicate');
    Route::put('transactions/{transaction}/description', [TransactionController::class, 'updateDescription'])->name('transactions.update-description');

    // Recibos (comprovantes enviados e recibos emitidos pelo sistema)
    Route::get('receipts', [ReceiptController::class, 'index'])->name('receipts.index');
    Route::get('receipts/export', [ReceiptController::class, 'export'])->name('receipts.export');
    Route::get('receipts/{attachment}/arquivo', [ReceiptController::class, 'file'])->name('receipts.file');
    Route::get('receipts/{attachment}/download', [ReceiptController::class, 'download'])->name('receipts.download');

    // Relatórios
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/cash-flow/extract/pdf', [ReportController::class, 'cashFlowExtractPdf'])->name('reports.cash-flow.extract.pdf');
    Route::get('reports/cash-flow/extract', [ReportController::class, 'cashFlowExtract'])->name('reports.cash-flow.extract');
    Route::get('reports/cash-flow/revenues-expenses', [ReportController::class, 'cashFlowRevenuesExpenses'])->name('reports.cash-flow.revenues-expenses');
    Route::get('reports/cash-flow/annual', [ReportController::class, 'annualCashFlow'])->name('reports.cash-flow.annual');
    Route::get('reports/revenues/daily-extract', [ReportController::class, 'revenuesDailyExtract'])->name('reports.revenues.daily-extract');
    Route::get('reports/revenues-expenses/by-category', [ReportController::class, 'revenuesExpensesByCategory'])->name('reports.revenues-expenses.by-category');
    Route::get('reports/expenses/daily-extract', [ReportController::class, 'expensesDailyExtract'])->name('reports.expenses.daily-extract');
    Route::get('reports/expenses/annual-summary', [ReportController::class, 'expensesAnnualSummary'])->name('reports.expenses.annual-summary');
    Route::get('reports/revenues/annual-summary', [ReportController::class, 'revenuesAnnualSummary'])->name('reports.revenues.annual-summary');
    Route::get('reports/cultos', [ClosingReportController::class, 'cultos'])->name('reports.cultos');
    Route::post('reports/cultos/{event}/vincular', [ClosingReportController::class, 'attachToCulto'])->name('reports.cultos.attach');
    Route::post('reports/cultos/{event}/lancamentos/{transaction}/desvincular', [ClosingReportController::class, 'detachFromCulto'])->name('reports.cultos.detach');
    Route::get('reports/cultos/{event}/pdf', [ClosingReportController::class, 'cultosPdf'])->name('reports.cultos.pdf');
    Route::get('reports/fechamento-semanal', [ClosingReportController::class, 'weekly'])->name('reports.weekly-closing');
    Route::post('reports/fechamento-semanal', [ClosingReportController::class, 'weeklyGenerate'])->name('reports.weekly-closing.generate');
    Route::get('reports/fechamento-semanal/{cashClosing}/pdf', [ClosingReportController::class, 'weeklyPdf'])->name('reports.weekly-closing.pdf');
    Route::get('reports/assinaturas', [ReportSignatureController::class, 'edit'])->name('reports.signatures');
    Route::post('reports/assinaturas', [ReportSignatureController::class, 'update'])->name('reports.signatures.update');
    Route::delete('reports/assinaturas', [ReportSignatureController::class, 'destroy'])->name('reports.signatures.destroy');

    // Categorias
    Route::resource('categories', CategoryController::class)->names([
    'index' => 'categories.index',
    'create' => 'categories.create',
    'store' => 'categories.store',
    'show' => 'categories.show',
    'edit' => 'categories.edit',
    'update' => 'categories.update',
    'destroy' => 'categories.destroy',
    ]);

    // Contas
    Route::post('accounts/{account}/toggle-active', [AccountController::class, 'toggleActive'])
        ->name('accounts.toggle-active');
    Route::resource('accounts', AccountController::class)->names([
    'index' => 'accounts.index',
    'create' => 'accounts.create',
    'store' => 'accounts.store',
    'show' => 'accounts.show',
    'edit' => 'accounts.edit',
    'update' => 'accounts.update',
    'destroy' => 'accounts.destroy',
    ]);

    // Contatos
    Route::resource('contacts', ContactController::class)->names([
    'index' => 'contacts.index',
    'create' => 'contacts.create',
    'store' => 'contacts.store',
    'show' => 'contacts.show',
    'edit' => 'contacts.edit',
    'update' => 'contacts.update',
    'destroy' => 'contacts.destroy',
    ]);
    Route::post('contacts/categories', [ContactController::class, 'storeCategory'])->name('contacts.categories.store');

    // Centros de custos
    Route::resource('cost-centers', CostCenterController::class)->names([
    'index' => 'cost-centers.index',
    'create' => 'cost-centers.create',
    'store' => 'cost-centers.store',
    'show' => 'cost-centers.show',
    'edit' => 'cost-centers.edit',
    'update' => 'cost-centers.update',
    'destroy' => 'cost-centers.destroy',
    ]);

    // Campanhas de arrecadação (independentes da contabilidade do módulo)
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
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
