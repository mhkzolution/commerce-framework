<?php

declare(strict_types=1);

use Commerce\Marketplace\Http\Controllers\Admin\CommissionController;
use Commerce\Marketplace\Http\Controllers\Admin\SellerController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:marketplace.seller.view'])
        ->prefix('admin/marketplace')
        ->name('admin.marketplace.')
        ->group(function (): void {
            Route::get('/commissions', [CommissionController::class, 'index'])
                ->middleware('permission:marketplace.commission.view')
                ->name('commissions.index');

            Route::get('/sellers', [SellerController::class, 'index'])->name('sellers.index');

            Route::middleware('permission:marketplace.seller.manage')->group(function (): void {
                Route::get('/sellers/create', [SellerController::class, 'create'])->name('sellers.create');
                Route::post('/sellers', [SellerController::class, 'store'])->name('sellers.store');
                Route::get('/sellers/{seller}/edit', [SellerController::class, 'edit'])->name('sellers.edit');
                Route::put('/sellers/{seller}', [SellerController::class, 'update'])->name('sellers.update');
                Route::delete('/sellers/{seller}', [SellerController::class, 'destroy'])->name('sellers.destroy');
            });
        });
});
