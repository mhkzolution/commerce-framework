<?php

declare(strict_types=1);

use Commerce\Currency\Http\Controllers\Admin\CurrencyController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:currency.currency.view'])
        ->prefix('admin/currencies')
        ->name('admin.currencies.')
        ->group(function (): void {
            Route::get('/', [CurrencyController::class, 'index'])->name('index');

            Route::middleware('permission:currency.currency.manage')->group(function (): void {
                Route::get('/create', [CurrencyController::class, 'create'])->name('create');
                Route::post('/', [CurrencyController::class, 'store'])->name('store');
                Route::get('/{currency}/edit', [CurrencyController::class, 'edit'])->name('edit');
                Route::put('/{currency}', [CurrencyController::class, 'update'])->name('update');
                Route::delete('/{currency}', [CurrencyController::class, 'destroy'])->name('destroy');
            });
        });
});
