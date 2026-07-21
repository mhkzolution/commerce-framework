<?php

declare(strict_types=1);

use Commerce\Reports\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:reports.dashboard.view'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard', [DashboardController::class, 'index']);
            Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');
        });
});
