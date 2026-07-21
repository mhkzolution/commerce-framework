<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function (): void {
    Route::prefix('auth')->name('api.v1.auth.')->group(function (): void {
        // Authentication endpoints — implementation phase
    });
});
