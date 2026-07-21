<?php

declare(strict_types=1);

use Commerce\Tax\Http\Controllers\Admin\TaxRateController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:tax.rate.view'])->prefix('admin/tax')->name('admin.tax.')->group(function (): void {
        Route::get('/', [TaxRateController::class, 'index'])->name('index');
        Route::middleware('permission:tax.rate.manage')->group(function (): void {
            Route::get('/create', [TaxRateController::class, 'create'])->name('create');
            Route::post('/', [TaxRateController::class, 'store'])->name('store');
            Route::get('/{rate}/edit', [TaxRateController::class, 'edit'])->name('edit');
            Route::put('/{rate}', [TaxRateController::class, 'update'])->name('update');
            Route::delete('/{rate}', [TaxRateController::class, 'destroy'])->name('destroy');
        });
    });
});
