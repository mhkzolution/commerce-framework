<?php

declare(strict_types=1);

use Commerce\Product\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:product.product.view'])
        ->prefix('admin/products')
        ->name('admin.products.')
        ->group(function (): void {
            Route::get('/', [ProductController::class, 'index'])->name('index');

            Route::middleware('permission:product.product.create')->group(function (): void {
                Route::get('/create', [ProductController::class, 'create'])->name('create');
                Route::post('/', [ProductController::class, 'store'])->name('store');
            });

            Route::middleware('permission:product.product.update')->group(function (): void {
                Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
                Route::put('/{product}', [ProductController::class, 'update'])->name('update');
                Route::post('/{product}/variants', [ProductController::class, 'storeVariant'])->name('variants.store');
                Route::delete('/{product}/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('variants.destroy');
            });

            Route::middleware('permission:product.product.publish')->group(function (): void {
                Route::post('/{product}/publish', [ProductController::class, 'publish'])->name('publish');
                Route::post('/{product}/archive', [ProductController::class, 'archive'])->name('archive');
            });

            Route::delete('/{product}', [ProductController::class, 'destroy'])
                ->middleware('permission:product.product.delete')
                ->name('destroy');
        });
});
