<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\MemberRoleController;
use App\Http\Controllers\VolunteerController;
use App\Http\Controllers\ServiceAreaController;
use App\Http\Controllers\VolunteerAvailabilityController;
use App\Http\Controllers\ServiceScheduleController;
use App\Http\Controllers\PgiController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\Financial\CategoryController;
use App\Http\Controllers\Financial\AccountController;
use App\Http\Controllers\Financial\ContactController;
use App\Http\Controllers\Financial\CostCenterController;
use App\Http\Controllers\Financial\TransactionController;
use App\Http\Controllers\Financial\ReportController;
use App\Http\Controllers\Financial\SummaryController;
use App\Http\Controllers\Ensino\EstudosController;
use App\Http\Controllers\Ensino\EscolasController;
use App\Http\Controllers\Ensino\TurmasController;
use App\Http\Controllers\Agenda\EventosController;
use App\Http\Controllers\Agenda\CalendarioController;
use App\Http\Controllers\Agenda\EventController;
use App\Http\Controllers\Agenda\EventCategoryController;

Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

// Rotas do módulo de Membros
Route::resource('members', MemberController::class);

// Rotas do módulo de Departamentos
Route::resource('departments', DepartmentController::class);

// Rotas do módulo de Cargos de Membros
Route::resource('member-roles', MemberRoleController::class);

// Rotas do módulo de Voluntários
Route::prefix('voluntarios')->name('voluntarios.')->group(function () {
    Route::resource('cadastro', VolunteerController::class)->parameters([
        'cadastro' => 'volunteer'
    ])->names([
        'index' => 'cadastro.index',
        'create' => 'cadastro.create',
        'store' => 'cadastro.store',
        'show' => 'cadastro.show',
        'edit' => 'cadastro.edit',
        'update' => 'cadastro.update',
        'destroy' => 'cadastro.destroy',
    ]);
    
    Route::resource('areas', ServiceAreaController::class)->names([
        'index' => 'areas.index',
        'create' => 'areas.create',
        'store' => 'areas.store',
        'show' => 'areas.show',
        'edit' => 'areas.edit',
        'update' => 'areas.update',
        'destroy' => 'areas.destroy',
    ]);
    
    Route::resource('disponibilidade', VolunteerAvailabilityController::class)->parameters([
        'disponibilidade' => 'disponibilidade'
    ])->names([
        'index' => 'disponibilidade.index',
        'create' => 'disponibilidade.create',
        'store' => 'disponibilidade.store',
        'show' => 'disponibilidade.show',
        'edit' => 'disponibilidade.edit',
        'update' => 'disponibilidade.update',
        'destroy' => 'disponibilidade.destroy',
    ]);
});

// Rotas do módulo de Serviço - Escalas
Route::prefix('servico')->name('servico.')->group(function () {
    Route::get('escalas', [ServiceScheduleController::class, 'index'])->name('escalas.index');
    Route::get('escalas/create', [ServiceScheduleController::class, 'create'])->name('escalas.create');
    Route::post('escalas/step1', [ServiceScheduleController::class, 'storeStep1'])->name('escalas.store.step1');
    Route::post('escalas/step2', [ServiceScheduleController::class, 'storeStep2'])->name('escalas.store.step2');
    Route::post('escalas/step3', [ServiceScheduleController::class, 'storeStep3'])->name('escalas.store.step3');
    Route::post('escalas', [ServiceScheduleController::class, 'store'])->name('escalas.store');
    Route::get('escalas/{escala}', [ServiceScheduleController::class, 'show'])->name('escalas.show');
    Route::get('escalas/{escala}/edit', [ServiceScheduleController::class, 'edit'])->name('escalas.edit');
    Route::put('escalas/{escala}', [ServiceScheduleController::class, 'update'])->name('escalas.update');
    Route::delete('escalas/{escala}', [ServiceScheduleController::class, 'destroy'])->name('escalas.destroy');
    Route::post('escalas/{escala}/duplicate', [ServiceScheduleController::class, 'duplicate'])->name('escalas.duplicate');
    Route::put('escalas/{escala}/cancel', [ServiceScheduleController::class, 'cancel'])->name('escalas.cancel');
    Route::put('escalas/{escala}/publish', [ServiceScheduleController::class, 'publish'])->name('escalas.publish');
    Route::put('escalas/{escala}/status', [ServiceScheduleController::class, 'updateStatus'])->name('escalas.update-status');
    Route::get('escalas/api/suggested-volunteers', [ServiceScheduleController::class, 'getSuggestedVolunteers'])->name('escalas.api.suggested-volunteers');
    Route::put('escalas/volunteers/{volunteer}/confirm', [ServiceScheduleController::class, 'confirmVolunteer'])->name('escalas.volunteers.confirm');
    Route::delete('escalas/volunteers/{volunteer}', [ServiceScheduleController::class, 'removeVolunteer'])->name('escalas.volunteers.remove');
    Route::get('escalas/{escala}/pdf', [ServiceScheduleController::class, 'generatePdf'])->name('escalas.pdf');
});

// Rotas de importação de membros
Route::prefix('members')->name('members.')->group(function () {
    Route::get('import/tutorial', [MemberController::class, 'importTutorial'])->name('import.tutorial');
    Route::get('import/template', [MemberController::class, 'downloadTemplate'])->name('import.template');
    Route::post('import', [MemberController::class, 'import'])->name('import');
});

// Rotas do módulo de PGIs
Route::resource('pgis', PgiController::class);

// Rotas do módulo de Reuniões (dentro de um PGI)
Route::prefix('pgis/{pgi}')->name('pgis.')->group(function () {
    Route::get('meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
    Route::post('meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::get('meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
    Route::put('meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
    Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');
    
    // Rotas para gerenciar membros do PGI
    Route::post('members/attach', [PgiController::class, 'attachMembers'])->name('members.attach');
    Route::delete('members/{member}/detach', [PgiController::class, 'detachMember'])->name('members.detach');
    
    // Rotas para atualizar logo e banner
    Route::post('logo', [PgiController::class, 'updateLogo'])->name('logo.update');
    Route::post('banner', [PgiController::class, 'updateBanner'])->name('banner.update');
});

// Rotas do módulo Financeiro
Route::prefix('financial')->name('financial.')->group(function () {
    // Resumo/Dashboard
    Route::get('summary', [SummaryController::class, 'index'])->name('summary');
    
    // Transações
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('transactions/receita', [TransactionController::class, 'storeReceita'])->name('transactions.store.receita');
    Route::post('transactions/despesa', [TransactionController::class, 'storeDespesa'])->name('transactions.store.despesa');
    Route::get('transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
    Route::post('transactions/import', [TransactionController::class, 'import'])->name('transactions.import');
    Route::resource('transactions', TransactionController::class)->except(['create', 'store'])->names([
        'index' => 'transactions.index',
        'show' => 'transactions.show',
        'edit' => 'transactions.edit',
        'update' => 'transactions.update',
        'destroy' => 'transactions.destroy',
    ]);
    Route::get('transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt');
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

// Rotas do módulo de Ensino
Route::prefix('ensino')->name('ensino.')->group(function () {
    // Estudos
    Route::resource('estudos', EstudosController::class)->names([
        'index' => 'estudos.index',
        'create' => 'estudos.create',
        'store' => 'estudos.store',
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

// Rotas do módulo de Agenda
Route::prefix('agenda')->name('agenda.')->group(function () {
    // Calendário
    Route::get('calendario', [CalendarioController::class, 'index'])->name('calendario.index');
    
    // Eventos (API para FullCalendar)
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::post('events', [EventController::class, 'store'])->name('events.store');
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    
    // Categorias
    Route::post('categories', [EventCategoryController::class, 'store'])->name('categories.store');
    Route::delete('categories/{category}', [EventCategoryController::class, 'destroy'])->name('categories.destroy');
    
    // Eventos (página de listagem)
    Route::get('eventos', [EventosController::class, 'index'])->name('eventos.index');
});
