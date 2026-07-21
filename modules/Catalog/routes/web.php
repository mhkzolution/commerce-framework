<?php

declare(strict_types=1);

use Commerce\Catalog\Http\Controllers\Admin\AttributeController;
use Commerce\Catalog\Http\Controllers\Admin\AttributeSetController;
use Commerce\Catalog\Http\Controllers\Admin\BrandController;
use Commerce\Catalog\Http\Controllers\Admin\CategoryController;
use Commerce\Catalog\Http\Controllers\Admin\DashboardController;
use Commerce\Catalog\Http\Controllers\Admin\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:catalog.category.view'])
        ->prefix('admin/catalog')
        ->name('admin.catalog.')
        ->group(function (): void {
            Route::get('/', [DashboardController::class, 'index'])->name('index');

            Route::prefix('categories')->name('categories.')->group(function (): void {
                Route::get('/', [CategoryController::class, 'index'])->name('index');
                Route::middleware('permission:catalog.category.create')->group(function (): void {
                    Route::get('/create', [CategoryController::class, 'create'])->name('create');
                    Route::post('/', [CategoryController::class, 'store'])->name('store');
                });
                Route::middleware('permission:catalog.category.update')->group(function (): void {
                    Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
                    Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
                    Route::patch('/{category}/reorder', [CategoryController::class, 'reorder'])->name('reorder');
                });
                Route::delete('/{category}', [CategoryController::class, 'destroy'])
                    ->middleware('permission:catalog.category.delete')
                    ->name('destroy');
            });

            Route::prefix('brands')->name('brands.')->group(function (): void {
                Route::get('/', [BrandController::class, 'index'])->name('index');
                Route::middleware('permission:catalog.brand.create')->group(function (): void {
                    Route::get('/create', [BrandController::class, 'create'])->name('create');
                    Route::post('/', [BrandController::class, 'store'])->name('store');
                });
                Route::middleware('permission:catalog.brand.update')->group(function (): void {
                    Route::get('/{brand}/edit', [BrandController::class, 'edit'])->name('edit');
                    Route::put('/{brand}', [BrandController::class, 'update'])->name('update');
                });
                Route::delete('/{brand}', [BrandController::class, 'destroy'])
                    ->middleware('permission:catalog.brand.delete')
                    ->name('destroy');
            });

            Route::prefix('tags')->name('tags.')->group(function (): void {
                Route::get('/', [TagController::class, 'index'])->name('index');
                Route::post('/', [TagController::class, 'store'])
                    ->middleware('permission:catalog.tag.create')
                    ->name('store');
                Route::delete('/{tag}', [TagController::class, 'destroy'])
                    ->middleware('permission:catalog.tag.delete')
                    ->name('destroy');
            });

            Route::prefix('attributes')->name('attributes.')->group(function (): void {
                Route::get('/', [AttributeController::class, 'index'])->name('index');
                Route::middleware('permission:catalog.attribute.create')->group(function (): void {
                    Route::get('/create', [AttributeController::class, 'create'])->name('create');
                    Route::post('/', [AttributeController::class, 'store'])->name('store');
                });
                Route::middleware('permission:catalog.attribute.update')->group(function (): void {
                    Route::get('/{attribute}/edit', [AttributeController::class, 'edit'])->name('edit');
                    Route::put('/{attribute}', [AttributeController::class, 'update'])->name('update');
                });
                Route::delete('/{attribute}', [AttributeController::class, 'destroy'])
                    ->middleware('permission:catalog.attribute.delete')
                    ->name('destroy');
            });

            Route::prefix('attribute-sets')->name('attribute-sets.')->group(function (): void {
                Route::get('/', [AttributeSetController::class, 'index'])->name('index');
                Route::middleware('permission:catalog.attribute_set.create')->group(function (): void {
                    Route::get('/create', [AttributeSetController::class, 'create'])->name('create');
                    Route::post('/', [AttributeSetController::class, 'store'])->name('store');
                });
                Route::middleware('permission:catalog.attribute_set.update')->group(function (): void {
                    Route::get('/{attribute_set}/edit', [AttributeSetController::class, 'edit'])->name('edit');
                    Route::put('/{attribute_set}', [AttributeSetController::class, 'update'])->name('update');
                });
                Route::delete('/{attribute_set}', [AttributeSetController::class, 'destroy'])
                    ->middleware('permission:catalog.attribute_set.delete')
                    ->name('destroy');
            });
        });
});
