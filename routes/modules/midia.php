<?php

use App\Http\Controllers\Midia\GoogleDriveAuthController;
use App\Http\Controllers\Midia\InstagramAuthController;
use App\Http\Controllers\Midia\MediaController;
use App\Http\Controllers\Midia\ScheduledPostController;
use Illuminate\Support\Facades\Route;

Route::prefix('midia')->name('midia.')->middleware('module.access:midia')->group(function () {
    Route::get('google/redirect', [GoogleDriveAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('google/callback', [GoogleDriveAuthController::class, 'callback'])->name('google.callback');
    Route::post('google/disconnect', [GoogleDriveAuthController::class, 'disconnect'])->name('google.disconnect');

    Route::get('instagram/redirect', [InstagramAuthController::class, 'redirect'])->name('instagram.redirect');
    Route::get('instagram/callback', [InstagramAuthController::class, 'callback'])->name('instagram.callback');
    Route::post('instagram/disconnect', [InstagramAuthController::class, 'disconnect'])->name('instagram.disconnect');

    Route::get('configuracoes', [MediaController::class, 'settings'])->name('settings');

    Route::get('instagram/publicacoes', [ScheduledPostController::class, 'index'])->name('instagram.posts.index');
    Route::get('instagram/publicacoes/criar', [ScheduledPostController::class, 'create'])->name('instagram.posts.create');
    Route::post('instagram/publicacoes', [ScheduledPostController::class, 'store'])->name('instagram.posts.store');
    Route::delete('instagram/publicacoes/{scheduledPost}', [ScheduledPostController::class, 'destroy'])->name('instagram.posts.destroy');
    Route::post('instagram/publicacoes/destinos/{destination}/retry', [ScheduledPostController::class, 'retryDestination'])
        ->name('instagram.posts.destinations.retry');

    Route::get('/', [MediaController::class, 'index'])->name('index');
    Route::get('/arquivos/browse', [MediaController::class, 'browse'])->name('files.browse');
    Route::post('/upload', [MediaController::class, 'store'])->name('upload');
    Route::post('/pastas', [MediaController::class, 'createFolder'])->name('folders.store');
    Route::get('/{mediaFile}/download', [MediaController::class, 'download'])->name('download');
    Route::get('/{mediaFile}/preview', [MediaController::class, 'preview'])->name('preview');
    Route::get('/{mediaFile}/thumbnail', [MediaController::class, 'thumbnail'])->name('thumbnail');
    Route::delete('/{mediaFile}', [MediaController::class, 'destroy'])->name('destroy');
    Route::put('/{mediaFile}/mover', [MediaController::class, 'move'])->name('move');
});
