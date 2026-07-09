<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Financial\CategoryController;
use App\Http\Controllers\Financial\AccountController;
use App\Http\Controllers\Financial\CheckoutController;
use App\Http\Controllers\Financial\ContactController;
use App\Http\Controllers\Financial\CostCenterController;
use App\Http\Controllers\Financial\TransactionController;
use App\Http\Controllers\Financial\ReportController;
use App\Http\Controllers\Financial\SummaryController;


// Rotas do módulo Financeiro (somente admin)
Route::prefix('financial')->name('financial.')->middleware('module.access:financial')->group(function () {
    // Resumo/Dashboard
    Route::get('summary', [SummaryController::class, 'index'])->name('summary');

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

    // Relatórios
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/cash-flow/extract', [ReportController::class, 'cashFlowExtract'])->name('reports.cash-flow.extract');
    Route::get('reports/cash-flow/revenues-expenses', [ReportController::class, 'cashFlowRevenuesExpenses'])->name('reports.cash-flow.revenues-expenses');
    Route::get('reports/revenues/daily-extract', [ReportController::class, 'revenuesDailyExtract'])->name('reports.revenues.daily-extract');
    Route::get('reports/revenues-expenses/by-category', [ReportController::class, 'revenuesExpensesByCategory'])->name('reports.revenues-expenses.by-category');
    Route::get('reports/expenses/daily-extract', [ReportController::class, 'expensesDailyExtract'])->name('reports.expenses.daily-extract');
    Route::get('reports/expenses/annual-summary', [ReportController::class, 'expensesAnnualSummary'])->name('reports.expenses.annual-summary');
    Route::get('reports/revenues/annual-summary', [ReportController::class, 'revenuesAnnualSummary'])->name('reports.revenues.annual-summary');

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
    });
