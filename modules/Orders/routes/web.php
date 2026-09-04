<?php

declare(strict_types=1);

use Commerce\Orders\Http\Controllers\Admin\OrderController;
use Commerce\Orders\Http\Controllers\Admin\OrderLookupController;
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
                Route::get('/lookup/customers', [OrderLookupController::class, 'customers'])->name('lookup.customers');
                Route::get('/lookup/products', [OrderLookupController::class, 'products'])->name('lookup.products');
            });

            Route::get('/{order}', [OrderController::class, 'show'])->name('show');

            Route::middleware('permission:orders.order.update')->group(function (): void {
                Route::post('/{order}/notes', [OrderController::class, 'updateNotes'])->name('notes.update');
                Route::post('/{order}/shipments', [OrderController::class, 'storeShipment'])->name('shipments.store');
                Route::post('/{order}/shipments/{shipment}/tracking', [OrderController::class, 'updateTracking'])->name('shipments.tracking');
                Route::post('/{order}/shipments/{shipment}/cancel', [OrderController::class, 'cancelShipment'])->name('shipments.cancel');
            });

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
