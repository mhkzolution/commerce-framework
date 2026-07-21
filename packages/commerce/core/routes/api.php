<?php

declare(strict_types=1);

use Commerce\Core\Http\Controllers\Api\TenantApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::prefix('tenants')->name('api.v1.tenants.')->group(function (): void {
        Route::get('/', [TenantApiController::class, 'index'])->name('index');
        Route::post('/', [TenantApiController::class, 'store'])->name('store');
        Route::get('/current', [TenantApiController::class, 'current'])->name('current');
        Route::post('/switch/{uuid}', [TenantApiController::class, 'switch'])->name('switch');
    });
});
