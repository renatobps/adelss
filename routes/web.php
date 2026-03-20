<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\MemberRoleController;
use App\Http\Controllers\VolunteerController;
use App\Http\Controllers\ServiceAreaController;
use App\Http\Controllers\ServiceScheduleController;
use App\Http\Controllers\MonthlyCultoScheduleController;
use App\Http\Controllers\ServiceHistoryController;
use App\Http\Controllers\VolunteerReportController;
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
use App\Http\Controllers\MoriahController;
use App\Http\Controllers\MoriahFunctionController;
use App\Http\Controllers\RepertorioController;
use App\Http\Controllers\MoriahScheduleController;
use App\Http\Controllers\MoriahUnavailabilityController;
use App\Http\Controllers\Discipleship\DiscipleshipCycleController;
use App\Http\Controllers\Discipleship\DiscipleshipMemberController;
use App\Http\Controllers\Discipleship\DiscipleshipMeetingController;
use App\Http\Controllers\Discipleship\DiscipleshipIndicatorController;
use App\Http\Controllers\Discipleship\DiscipleshipGoalController;
use App\Http\Controllers\Discipleship\DiscipleshipFeedbackController;
use App\Http\Controllers\Discipleship\DiscipleshipDashboardController;

// Rotas de autenticação
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

// Rotas do módulo de Membros (somente admin)
Route::resource('members', MemberController::class)->middleware('module.access:members');

// Rotas do módulo de Departamentos (somente admin)
Route::resource('departments', DepartmentController::class)->middleware('module.access:servico');

// Rotas do módulo de Cargos de Membros (somente admin)
Route::resource('member-roles', MemberRoleController::class)->middleware('module.access:members');
Route::get('member-roles/import/template', [MemberRoleController::class, 'downloadTemplate'])->name('member-roles.import.template');
Route::post('member-roles/import', [MemberRoleController::class, 'import'])->name('member-roles.import');

// Rotas do módulo de Serviço - Voluntários (somente admin)
Route::prefix('servico/voluntarios')->name('voluntarios.')->middleware('module.access:servico')->group(function () {
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
    
    
    // Histórico de Serviço
    Route::get('historico', [ServiceHistoryController::class, 'index'])->name('historico.index');
    Route::get('historico/{history}', [ServiceHistoryController::class, 'show'])->name('historico.show');
    Route::get('historico/voluntario/{volunteer}', [ServiceHistoryController::class, 'showByVolunteer'])->name('historico.volunteer');
    
    // Escalas
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
    Route::put('escalas/volunteers/{volunteer}/substitute', [ServiceScheduleController::class, 'substituteVolunteer'])->name('escalas.volunteers.substitute');
    Route::delete('escalas/volunteers/{volunteer}', [ServiceScheduleController::class, 'removeVolunteer'])->name('escalas.volunteers.remove');
    Route::get('escalas/{escala}/pdf', [ServiceScheduleController::class, 'generatePdf'])->name('escalas.pdf');
    
    // Escalas Mensais de Cultos
    Route::get('escalas-mensais', [MonthlyCultoScheduleController::class, 'index'])->name('escalas-mensais.index');
    Route::get('escalas-mensais/create', [MonthlyCultoScheduleController::class, 'create'])->name('escalas-mensais.create');
    Route::post('escalas-mensais', [MonthlyCultoScheduleController::class, 'store'])->name('escalas-mensais.store');
    Route::get('escalas-mensais/{escala}', [MonthlyCultoScheduleController::class, 'show'])->name('escalas-mensais.show');
    Route::get('escalas-mensais/{escala}/edit', [MonthlyCultoScheduleController::class, 'edit'])->name('escalas-mensais.edit');
    Route::put('escalas-mensais/{escala}', [MonthlyCultoScheduleController::class, 'update'])->name('escalas-mensais.update');
    Route::put('escalas-mensais/{escala}/status', [MonthlyCultoScheduleController::class, 'updateStatus'])->name('escalas-mensais.update-status');
    Route::put('escalas-mensais/{escala}/publish', [MonthlyCultoScheduleController::class, 'publish'])->name('escalas-mensais.publish');
    Route::put('escalas-mensais/{escala}/cancel', [MonthlyCultoScheduleController::class, 'cancel'])->name('escalas-mensais.cancel');
    Route::get('escalas-mensais/{escala}/pdf', [MonthlyCultoScheduleController::class, 'generatePdf'])->name('escalas-mensais.pdf');
    Route::get('escalas-mensais/volunteers/available', [MonthlyCultoScheduleController::class, 'getAvailableVolunteers'])->name('escalas-mensais.volunteers.available');
    Route::put('escalas-mensais/volunteers/{pivot}/confirm', [MonthlyCultoScheduleController::class, 'confirmVolunteer'])->name('escalas-mensais.volunteers.confirm');
    Route::put('escalas-mensais/volunteers/{pivot}/substitute', [MonthlyCultoScheduleController::class, 'substituteVolunteer'])->name('escalas-mensais.volunteers.substitute');
    Route::delete('escalas-mensais/volunteers/{pivot}', [MonthlyCultoScheduleController::class, 'removeVolunteer'])->name('escalas-mensais.volunteers.remove');
    Route::delete('escalas-mensais/{escala}', [MonthlyCultoScheduleController::class, 'destroy'])->name('escalas-mensais.destroy');
    
    // Relatórios
    Route::prefix('relatorios')->name('relatorios.')->group(function () {
        Route::get('/', [VolunteerReportController::class, 'dashboard'])->name('dashboard');
        Route::get('ativos-por-area', [VolunteerReportController::class, 'activeByArea'])->name('active-by-area');
        Route::get('mais-servem', [VolunteerReportController::class, 'topVolunteers'])->name('top-volunteers');
        Route::get('inativos', [VolunteerReportController::class, 'inactiveVolunteers'])->name('inactive');
        Route::get('deficit', [VolunteerReportController::class, 'deficitByArea'])->name('deficit');
        Route::get('por-escala', [VolunteerReportController::class, 'bySchedule'])->name('by-schedule');
    });
});

// Gestão de permissões (apenas para administradores - checado na view/controller)
Route::get('/permissoes', [PermissionController::class, 'index'])->name('permissions.index');
Route::put('/permissoes/{member}', [PermissionController::class, 'update'])->name('permissions.update');
Route::put('/permissoes/funcoes/{role}', [PermissionController::class, 'updateRole'])->name('permissions.update-role');

    // Rotas do módulo Moriah
    Route::prefix('moriah')->name('moriah.')->group(function () {
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

    // Módulo Notificações (WhatsApp: grupos, enquetes, painel, configuração, templates)
    Route::prefix('notificacoes')->name('notificacoes.')->group(function () {
        Route::resource('grupos', \App\Http\Controllers\Notificacoes\GrupoController::class)->parameters(['grupos' => 'grupo'])->names('grupos');
        Route::get('grupos-lista-json', function () {
            return response()->json(
                \App\Models\NotificacaoGrupo::where('ativo', true)->orderBy('nome')->get(['id', 'nome'])
            );
        })->name('grupos.lista-json');
        Route::get('departamentos-lista-json', function () {
            return response()->json(
                \App\Models\Department::active()->orderBy('name')->get(['id', 'name'])
            );
        })->name('departamentos.lista-json');
        Route::resource('enquetes', \App\Http\Controllers\Notificacoes\EnqueteController::class)->parameters(['enquetes' => 'enquete'])->names('enquetes');
        Route::post('enquetes/{enquete}/enviar', [\App\Http\Controllers\Notificacoes\EnqueteController::class, 'enviar'])->name('enquetes.enviar');
        Route::get('painel', [\App\Http\Controllers\Notificacoes\PainelController::class, 'index'])->name('painel.index');
        Route::post('painel/enviar', [\App\Http\Controllers\Notificacoes\PainelController::class, 'enviar'])->name('painel.enviar');
        Route::get('config', [\App\Http\Controllers\Notificacoes\ConfigController::class, 'index'])->name('config.index');
        Route::get('config/status', [\App\Http\Controllers\Notificacoes\ConfigController::class, 'status'])->name('config.status');
        Route::get('config/conectar', [\App\Http\Controllers\Notificacoes\ConfigController::class, 'conectar'])->name('config.conectar');
        Route::get('config/instances', [\App\Http\Controllers\Notificacoes\ConfigController::class, 'listarInstancias'])->name('config.instances');
        Route::post('config/instances', [\App\Http\Controllers\Notificacoes\ConfigController::class, 'criarInstancia'])->name('config.instances.store');
        Route::delete('config/instances/{instanceName}', [\App\Http\Controllers\Notificacoes\ConfigController::class, 'deletarInstancia'])->name('config.instances.destroy');
        Route::get('config/instances/{instanceName}/status', [\App\Http\Controllers\Notificacoes\ConfigController::class, 'statusInstancia'])->name('config.instances.status');
        Route::put('config/webhook-received', [\App\Http\Controllers\Notificacoes\ConfigController::class, 'configurarWebhookReceived'])->name('config.webhook-received');
        Route::put('config/webhook-delivery', [\App\Http\Controllers\Notificacoes\ConfigController::class, 'configurarWebhookDelivery'])->name('config.webhook-delivery');
        Route::post('config/teste', [\App\Http\Controllers\Notificacoes\ConfigController::class, 'enviarTeste'])->name('config.teste');
        Route::get('templates', [\App\Http\Controllers\Notificacoes\TemplateController::class, 'index'])->name('templates.index');
        Route::put('templates', [\App\Http\Controllers\Notificacoes\TemplateController::class, 'update'])->name('templates.update');
    });

}); // fim do grupo auth

        // Rotas de importação de membros (somente admin)
        Route::prefix('members')->name('members.')->middleware('module.access:members')->group(function () {
    Route::get('import/tutorial', [MemberController::class, 'importTutorial'])->name('import.tutorial');
    Route::get('import/template', [MemberController::class, 'downloadTemplate'])->name('import.template');
    Route::post('import', [MemberController::class, 'import'])->name('import');
});

// Rotas do módulo de PGIs (admin total, outros só ver se fizer parte de PGI)
Route::resource('pgis', PgiController::class)->middleware('module.access:pgis');

        // Rotas do módulo de Reuniões (dentro de um PGI) - admin total, outros só ver se fizer parte
        Route::prefix('pgis/{pgi}')->name('pgis.')->middleware('module.access:pgis')->group(function () {
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

        // Rotas do módulo de Discipulado
        Route::prefix('discipleship')->name('discipleship.')->middleware('module.access:discipleship')->group(function () {
    // Dashboard
    Route::get('dashboard/discipulador', [DiscipleshipDashboardController::class, 'discipulador'])->name('dashboard.discipulador');
    Route::get('dashboard/lideranca', [DiscipleshipDashboardController::class, 'lideranca'])->name('dashboard.lideranca');
    
    // Ciclos
    Route::resource('cycles', DiscipleshipCycleController::class)->names([
        'index' => 'cycles.index',
        'create' => 'cycles.create',
        'store' => 'cycles.store',
        'show' => 'cycles.show',
        'edit' => 'cycles.edit',
        'update' => 'cycles.update',
        'destroy' => 'cycles.destroy',
    ]);
    
    // Membros (vinculação ao ciclo)
    Route::resource('members', DiscipleshipMemberController::class)->names([
        'index' => 'members.index',
        'create' => 'members.create',
        'store' => 'members.store',
        'show' => 'members.show',
        'edit' => 'members.edit',
        'update' => 'members.update',
        'destroy' => 'members.destroy',
    ]);
    
    // Encontros
    Route::resource('meetings', DiscipleshipMeetingController::class)->names([
        'index' => 'meetings.index',
        'create' => 'meetings.create',
        'store' => 'meetings.store',
        'show' => 'meetings.show',
        'edit' => 'meetings.edit',
        'update' => 'meetings.update',
        'destroy' => 'meetings.destroy',
    ]);
    
    // Indicadores
    Route::resource('indicators', DiscipleshipIndicatorController::class)->names([
        'index' => 'indicators.index',
        'create' => 'indicators.create',
        'store' => 'indicators.store',
        'edit' => 'indicators.edit',
        'update' => 'indicators.update',
        'destroy' => 'indicators.destroy',
    ]);
    Route::post('indicators/value', [DiscipleshipIndicatorController::class, 'storeValue'])->name('indicators.value.store');
    
    // Propósitos/Metas
    Route::resource('goals', DiscipleshipGoalController::class)->names([
        'index' => 'goals.index',
        'create' => 'goals.create',
        'store' => 'goals.store',
        'show' => 'goals.show',
        'edit' => 'goals.edit',
        'update' => 'goals.update',
        'destroy' => 'goals.destroy',
    ]);
    Route::get('goals/{goal}/pdf', [DiscipleshipGoalController::class, 'generatePdf'])->name('goals.pdf');
    
    // Ajuda
    Route::get('help', [DiscipleshipDashboardController::class, 'help'])->name('help');

    // Feedbacks
    Route::resource('feedbacks', DiscipleshipFeedbackController::class)->names([
        'index' => 'feedbacks.index',
        'create' => 'feedbacks.create',
        'store' => 'feedbacks.store',
        'edit' => 'feedbacks.edit',
        'update' => 'feedbacks.update',
        'destroy' => 'feedbacks.destroy',
    ]);
});

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

        // Rotas do módulo de Agenda (admin total, outros só ver)
        Route::prefix('agenda')->name('agenda.')->middleware('module.access:agenda')->group(function () {
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

    // Rotas do módulo Moriah
    Route::prefix('moriah')->name('moriah.')->group(function () {
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
    });
