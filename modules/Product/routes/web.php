<?php

declare(strict_types=1);

use Commerce\Product\Http\Controllers\Admin\ProductController;
use Commerce\Product\Http\Controllers\Admin\ProductImportController;
use Commerce\Product\Http\Controllers\Admin\ProductSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:product.product.view'])
        ->prefix('admin/products')
        ->name('admin.products.')
        ->group(function (): void {
            Route::get('/', [ProductController::class, 'index'])->name('index');

            Route::middleware('permission:product.product.view')->group(function (): void {
                Route::get('/settings', [ProductSettingsController::class, 'show'])->name('settings.show');
                Route::get('/export', [ProductImportController::class, 'export'])->name('export');
            });

            Route::middleware('permission:product.product.update')->group(function (): void {
                Route::put('/settings', [ProductSettingsController::class, 'update'])->name('settings.update');
            });

            Route::middleware('permission:product.product.create')->group(function (): void {
                Route::get('/create', [ProductController::class, 'create'])->name('create');
                Route::post('/', [ProductController::class, 'store'])->name('store');
                Route::get('/import', [ProductImportController::class, 'show'])->name('import.show');
                Route::post('/import', [ProductImportController::class, 'store'])->name('import.store');
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

            Route::middleware('permission:product.product.delete')->group(function (): void {
                Route::post('/bulk-delete', [ProductController::class, 'bulkDestroy'])->name('bulk-destroy');
                Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
            });
        });
});
