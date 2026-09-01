<?php

declare(strict_types=1);

use Commerce\Wishlist\Http\Controllers\Api\V1\StorefrontWishlistApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/storefront')->middleware(['web', 'api'])->name('api.v1.storefront.')->group(function (): void {
    Route::post('/wishlist/preview', [StorefrontWishlistApiController::class, 'preview'])->name('wishlist.preview');

    Route::middleware('auth:customer')->group(function (): void {
        Route::get('/wishlist', [StorefrontWishlistApiController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist/items', [StorefrontWishlistApiController::class, 'store'])->name('wishlist.items.store');
        Route::delete('/wishlist/items', [StorefrontWishlistApiController::class, 'destroy'])->name('wishlist.items.destroy');
        Route::post('/wishlist/merge', [StorefrontWishlistApiController::class, 'merge'])->name('wishlist.merge');
    });
});
