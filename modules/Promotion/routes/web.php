<?php

declare(strict_types=1);

use Commerce\Promotion\Http\Controllers\Admin\PromotionController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:promotion.promotion.view'])->prefix('admin/promotions')->name('admin.promotions.')->group(function (): void {
        Route::get('/', [PromotionController::class, 'index'])->name('index');
        Route::middleware('permission:promotion.promotion.manage')->group(function (): void {
            Route::get('/create', [PromotionController::class, 'create'])->name('create');
            Route::post('/', [PromotionController::class, 'store'])->name('store');
            Route::get('/{promotion}/edit', [PromotionController::class, 'edit'])->name('edit');
            Route::put('/{promotion}', [PromotionController::class, 'update'])->name('update');
            Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');
        });
    });
});
