<?php

use Illuminate\Support\Facades\Route;
use App\Models\Department;
use App\Models\NotificacaoGrupo;
use App\Http\Controllers\Notificacoes\GrupoController;
use App\Http\Controllers\Notificacoes\EnqueteController;
use App\Http\Controllers\Notificacoes\PainelController;
use App\Http\Controllers\Notificacoes\ConfigController;
use App\Http\Controllers\Notificacoes\TemplateController;


// Módulo Notificações (WhatsApp: grupos, enquetes, painel, configuração, templates)
Route::prefix('notificacoes')->name('notificacoes.')->middleware('module.access:notificacoes')->group(function () {
    Route::resource('grupos', GrupoController::class)->parameters(['grupos' => 'grupo'])->names('grupos');
    Route::get('grupos-lista-json', function () {
        return response()->json(
            NotificacaoGrupo::where('ativo', true)->orderBy('nome')->get(['id', 'nome'])
        );
    })->name('grupos.lista-json');
    Route::get('departamentos-lista-json', function () {
        return response()->json(
            Department::active()->orderBy('name')->get(['id', 'name'])
        );
    })->name('departamentos.lista-json');
    Route::resource('enquetes', EnqueteController::class)->parameters(['enquetes' => 'enquete'])->names('enquetes');
    Route::post('enquetes/{enquete}/enviar', [EnqueteController::class, 'enviar'])->name('enquetes.enviar');
    Route::get('painel', [PainelController::class, 'index'])->name('painel.index');
    Route::post('painel/enviar', [PainelController::class, 'enviar'])->name('painel.enviar');
    Route::post('painel/historico/limpar', [PainelController::class, 'limparHistorico'])->name('painel.historico.limpar');
    Route::get('config', [ConfigController::class, 'index'])->name('config.index');
    Route::get('config/status', [ConfigController::class, 'status'])->name('config.status');
    Route::get('config/conectar', [ConfigController::class, 'conectar'])->name('config.conectar');
    Route::get('config/instances', [ConfigController::class, 'listarInstancias'])->name('config.instances');
    Route::post('config/instances', [ConfigController::class, 'criarInstancia'])->name('config.instances.store');
    Route::post('config/instances/{instanceName}/select', [ConfigController::class, 'selecionarInstancia'])->name('config.instances.select');
    Route::post('config/instances/{instanceName}/restart', [ConfigController::class, 'reiniciarInstancia'])->name('config.instances.restart');
    Route::delete('config/instances/{instanceName}', [ConfigController::class, 'deletarInstancia'])->name('config.instances.destroy');
    Route::get('config/instances/{instanceName}/status', [ConfigController::class, 'statusInstancia'])->name('config.instances.status');
    Route::put('config/webhook-received', [ConfigController::class, 'configurarWebhookReceived'])->name('config.webhook-received');
    Route::put('config/webhook-delivery', [ConfigController::class, 'configurarWebhookDelivery'])->name('config.webhook-delivery');
    Route::post('config/teste', [ConfigController::class, 'enviarTeste'])->name('config.teste');
    Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::post('templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::put('templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
    Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
});
