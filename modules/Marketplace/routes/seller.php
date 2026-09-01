<?php

declare(strict_types=1);

use Commerce\Marketplace\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use Commerce\Marketplace\Http\Middleware\EnsureSeller;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', EnsureSeller::class])
        ->prefix('seller')
        ->name('seller.')
        ->group(function (): void {
            Route::get('/', [SellerDashboardController::class, 'index'])->name('dashboard');
            Route::get('/commissions', [SellerDashboardController::class, 'commissions'])->name('commissions.index');
            Route::post('/payouts/request', [SellerDashboardController::class, 'requestPayout'])->name('payouts.request');
        });
});
