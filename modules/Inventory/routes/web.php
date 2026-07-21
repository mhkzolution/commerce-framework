<?php

declare(strict_types=1);

use Commerce\Inventory\Http\Controllers\Admin\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:inventory.stock.view'])
        ->prefix('admin/inventory')
        ->name('admin.inventory.')
        ->group(function (): void {
            Route::get('/', [InventoryController::class, 'index'])->name('index');
            Route::get('/purchasable/{purchasableUuid}', [InventoryController::class, 'managePurchasable'])->name('purchasable');
            Route::get('/{item}', [InventoryController::class, 'show'])->name('show');

            Route::middleware('permission:inventory.stock.adjust')->group(function (): void {
                Route::post('/{item}/adjust', [InventoryController::class, 'adjust'])->name('adjust');
                Route::post('/{item}/receive', [InventoryController::class, 'receive'])->name('receive');
            });
        });
});
