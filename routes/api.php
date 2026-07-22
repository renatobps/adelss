<?php

use App\Http\Controllers\Webhooks\EvolutionWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Alias usado pelo Ultrahook local (ex.: ... -> http://127.0.0.1:8001/api/whatsapp/webhook)
Route::post('/whatsapp/webhook', [EvolutionWebhookController::class, 'handle'])
    ->name('webhooks.evolution.api');
Route::post('/whatsapp/webhook/{event}', [EvolutionWebhookController::class, 'handle'])
    ->where('event', '.*')
    ->name('webhooks.evolution.api.event');


