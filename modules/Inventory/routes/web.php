<?php

declare(strict_types=1);

use Commerce\Inventory\Http\Controllers\Admin\InventoryController;
use Commerce\Inventory\Http\Controllers\Admin\PurchaseOrderController;
use Commerce\Inventory\Http\Controllers\Admin\PurchaseOrderFailedJobController;
use Commerce\Inventory\Http\Controllers\Admin\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:inventory.stock.view'])
        ->prefix('admin/inventory')
        ->name('admin.inventory.')
        ->group(function (): void {
            Route::get('/', [InventoryController::class, 'index'])->name('index');
            Route::get('/purchasable/{purchasableUuid}', [InventoryController::class, 'managePurchasable'])->name('purchasable');

            Route::middleware('permission:inventory.purchase_order.view')->group(function (): void {
                Route::prefix('purchase-orders')->name('purchase-orders.')->group(function (): void {
                    Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
                    Route::get('/analytics', [PurchaseOrderController::class, 'analytics'])->name('analytics');
                    Route::get('/analytics/export', [PurchaseOrderController::class, 'analyticsExport'])->name('analytics.export');
                    Route::get('/export', [PurchaseOrderController::class, 'export'])->name('export');
                    Route::get('/failed-jobs', [PurchaseOrderFailedJobController::class, 'index'])->name('failed-jobs.index');
                    Route::get('/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])->name('print');
                    Route::get('/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'pdf'])->name('pdf');
                    Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show');
                });
            });

            Route::middleware('permission:inventory.purchase_order.manage')->group(function (): void {
                Route::prefix('suppliers')->name('suppliers.')->group(function (): void {
                    Route::get('/', [SupplierController::class, 'index'])->name('index');
                    Route::get('/create', [SupplierController::class, 'create'])->name('create');
                    Route::post('/', [SupplierController::class, 'store'])->name('store');
                    Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show');
                    Route::get('/{supplier}/export', [SupplierController::class, 'export'])->name('export');
                    Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
                    Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
                    Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('purchase-orders')->name('purchase-orders.')->group(function (): void {
                    Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
                    Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
                    Route::post('/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel');
                    Route::post('/{purchaseOrder}/email', [PurchaseOrderController::class, 'email'])->name('email');
                    Route::post('/failed-jobs/{uuid}/retry', [PurchaseOrderFailedJobController::class, 'retry'])->name('failed-jobs.retry');
                    Route::delete('/failed-jobs/{uuid}', [PurchaseOrderFailedJobController::class, 'destroy'])->name('failed-jobs.destroy');
                    Route::post('/{purchaseOrder}/lines/{line}/receive', [PurchaseOrderController::class, 'receive'])->name('lines.receive');
                    Route::post('/{purchaseOrder}/lines/{line}/cancel', [PurchaseOrderController::class, 'cancelLine'])->name('lines.cancel');
                });
            });

            Route::get('/{item}', [InventoryController::class, 'show'])->name('show');

            Route::middleware('permission:inventory.stock.adjust')->group(function (): void {
                Route::post('/{item}/adjust', [InventoryController::class, 'adjust'])->name('adjust');
                Route::post('/{item}/receive', [InventoryController::class, 'receive'])->name('receive');
            });
        });
});
