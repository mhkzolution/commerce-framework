<?php

declare(strict_types=1);

use Commerce\Catalog\Http\Controllers\Api\V1\StorefrontCatalogApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/storefront/catalog')->middleware(['api'])->name('api.v1.storefront.catalog.')->group(function (): void {
    Route::get('/brands', [StorefrontCatalogApiController::class, 'brands'])->name('brands.index');
    Route::get('/brands/{slug}', [StorefrontCatalogApiController::class, 'showBrand'])->name('brands.show');
    Route::get('/categories', [StorefrontCatalogApiController::class, 'categories'])->name('categories.index');
    Route::get('/categories/{slug}', [StorefrontCatalogApiController::class, 'showCategory'])->name('categories.show');
    Route::get('/collections', [StorefrontCatalogApiController::class, 'collections'])->name('collections.index');
    Route::get('/collections/{slug}', [StorefrontCatalogApiController::class, 'showCollection'])->name('collections.show');
});
