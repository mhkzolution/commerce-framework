<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Commerce\Marketplace\Http\Controllers\Admin\SellerController;

Route::middleware(['web', 'auth'])->prefix('admin/marketplace')->name('admin.marketplace.')->group(function (): void {
    Route::get('/sellers', [SellerController::class, 'index'])->name('sellers.index');
    Route::get('/sellers/create', [SellerController::class, 'create'])->name('sellers.create');
    Route::post('/sellers', [SellerController::class, 'store'])->name('sellers.store');
    Route::get('/sellers/{seller}/edit', [SellerController::class, 'edit'])->name('sellers.edit');
    Route::put('/sellers/{seller}', [SellerController::class, 'update'])->name('sellers.update');
    Route::delete('/sellers/{seller}', [SellerController::class, 'destroy'])->name('sellers.destroy');
});
