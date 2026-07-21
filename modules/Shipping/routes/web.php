<?php

declare(strict_types=1);

use Commerce\Shipping\Http\Controllers\Admin\ShippingMethodController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:shipping.method.view'])
        ->prefix('admin/shipping')
        ->name('admin.shipping.')
        ->group(function (): void {
            Route::get('/', [ShippingMethodController::class, 'index'])->name('index');

            Route::middleware('permission:shipping.method.manage')->group(function (): void {
                Route::get('/create', [ShippingMethodController::class, 'create'])->name('create');
                Route::post('/', [ShippingMethodController::class, 'store'])->name('store');
                Route::get('/{method}/edit', [ShippingMethodController::class, 'edit'])->name('edit');
                Route::put('/{method}', [ShippingMethodController::class, 'update'])->name('update');
                Route::delete('/{method}', [ShippingMethodController::class, 'destroy'])->name('destroy');
            });
        });
});
