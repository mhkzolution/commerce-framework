<?php

declare(strict_types=1);

use Commerce\Reports\Http\Controllers\Admin\DashboardController;
use Commerce\Reports\Http\Controllers\Admin\OrdersReportController;
use Commerce\Reports\Http\Controllers\Admin\ProductsReportController;
use Commerce\Reports\Http\Controllers\Admin\ReportsHubController;
use Commerce\Reports\Http\Controllers\Admin\SalesReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:reports.dashboard.view'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard', [DashboardController::class, 'index']);
            Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');

            Route::prefix('reports')->name('reports.')->group(function (): void {
                Route::get('/', [ReportsHubController::class, 'index'])->name('index');

                Route::get('/sales', [SalesReportController::class, 'index'])->name('sales.index');
                Route::get('/sales/export', [SalesReportController::class, 'export'])->name('sales.export');
                Route::get('/sales/pdf', [SalesReportController::class, 'pdf'])->name('sales.pdf');
                Route::get('/sales/print', [SalesReportController::class, 'print'])->name('sales.print');

                Route::get('/orders', [OrdersReportController::class, 'index'])->name('orders.index');
                Route::get('/orders/export', [OrdersReportController::class, 'export'])->name('orders.export');
                Route::get('/orders/pdf', [OrdersReportController::class, 'pdf'])->name('orders.pdf');
                Route::get('/orders/print', [OrdersReportController::class, 'print'])->name('orders.print');

                Route::get('/products', [ProductsReportController::class, 'index'])->name('products.index');
                Route::get('/products/export', [ProductsReportController::class, 'export'])->name('products.export');
                Route::get('/products/pdf', [ProductsReportController::class, 'pdf'])->name('products.pdf');
                Route::get('/products/print', [ProductsReportController::class, 'print'])->name('products.print');
            });
        });
});
