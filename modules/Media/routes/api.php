<?php

declare(strict_types=1);

use Commerce\Media\Http\Controllers\Api\V1\MediaController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api', 'auth'])->group(function (): void {
    Route::middleware('permission:media.media.view')
        ->get('/media', [MediaController::class, 'index'])
        ->name('api.v1.media.index');

    Route::middleware('permission:media.media.view')
        ->get('/media/{uuid}', [MediaController::class, 'show'])
        ->name('api.v1.media.show');

    Route::middleware('permission:media.media.upload')
        ->post('/media', [MediaController::class, 'store'])
        ->name('api.v1.media.store');

    Route::middleware('permission:media.media.update')
        ->put('/media/{uuid}', [MediaController::class, 'update'])
        ->name('api.v1.media.update');

    Route::middleware('permission:media.media.delete')
        ->delete('/media/{uuid}', [MediaController::class, 'destroy'])
        ->name('api.v1.media.destroy');
});
