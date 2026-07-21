<?php

declare(strict_types=1);

use Commerce\Orders\Http\Controllers\Admin\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:orders.order.view'])
        ->prefix('admin/orders')
        ->name('admin.orders.')
        ->group(function (): void {
            Route::get('/', [OrderController::class, 'index'])->name('index');

            Route::middleware('permission:orders.order.create')->group(function (): void {
                Route::get('/create', [OrderController::class, 'create'])->name('create');
                Route::post('/', [OrderController::class, 'store'])->name('store');
            });

            Route::get('/{order}', [OrderController::class, 'show'])->name('show');

            Route::middleware('permission:orders.order.confirm')->group(function (): void {
                Route::post('/{order}/confirm', [OrderController::class, 'confirm'])->name('confirm');
            });

            Route::middleware('permission:orders.order.complete')->group(function (): void {
                Route::post('/{order}/complete', [OrderController::class, 'complete'])->name('complete');
            });

            Route::middleware('permission:orders.order.cancel')->group(function (): void {
                Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
            });
        });
});
