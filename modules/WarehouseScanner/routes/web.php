<?php

declare(strict_types=1);

use Commerce\WarehouseScanner\Http\Controllers\DashboardController;
use Commerce\WarehouseScanner\Http\Controllers\HistoryController;
use Commerce\WarehouseScanner\Http\Controllers\ScannerApiController;
use Commerce\WarehouseScanner\Http\Controllers\ScannerController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'module:warehouse', 'permission:warehouse.scan'])
        ->prefix('warehouse')
        ->name('warehouse.')
        ->group(function (): void {
            Route::get('/', [ScannerController::class, 'index'])->name('index');

            Route::middleware(['permission:warehouse.reports', 'feature:warehouse-reports'])->group(function (): void {
                Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
                Route::get('/history', [HistoryController::class, 'index'])->name('history');
            });

            Route::prefix('api')->name('api.')->group(function (): void {
                Route::post('/lookup', [ScannerApiController::class, 'lookup'])->name('lookup');
                Route::post('/scan', [ScannerApiController::class, 'scan'])->name('scan');
                Route::middleware('feature:warehouse-reports')->group(function (): void {
                    Route::get('/history', [ScannerApiController::class, 'history'])->name('history');
                    Route::get('/dashboard', [ScannerApiController::class, 'dashboard'])->name('dashboard');
                });
            });
        });
});
