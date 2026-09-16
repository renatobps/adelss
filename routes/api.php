<?php

use App\Http\Controllers\Webhooks\EvolutionWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->name('api.v1.')->group(base_path('routes/api/v1.php'));

Route::get('/docs', function () {
    return redirect('/api-docs/');
})->name('api.docs');

// Alias usado pelo Ultrahook local (ex.: ... -> http://127.0.0.1:8001/api/whatsapp/webhook)
Route::post('/whatsapp/webhook', [EvolutionWebhookController::class, 'handle'])
    ->name('webhooks.evolution.api');
Route::post('/whatsapp/webhook/{event}', [EvolutionWebhookController::class, 'handle'])
    ->where('event', '.*')
    ->name('webhooks.evolution.api.event');
