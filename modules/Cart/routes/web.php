<?php

declare(strict_types=1);

use Commerce\Cart\Http\Controllers\ShopController;
use Commerce\Cart\Http\Controllers\StorefrontCartController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/shop', [ShopController::class, 'index'])->name('storefront.shop.index');
    Route::get('/products/{slug}', [ShopController::class, 'show'])->name('storefront.products.show');

    Route::get('/cart', [StorefrontCartController::class, 'index'])->name('storefront.cart.index');
    Route::post('/cart/items', [StorefrontCartController::class, 'store'])->name('storefront.cart.items.store');
    Route::patch('/cart/items/{purchasableUuid}', [StorefrontCartController::class, 'update'])->name('storefront.cart.items.update');
    Route::delete('/cart/items/{purchasableUuid}', [StorefrontCartController::class, 'destroy'])->name('storefront.cart.items.destroy');
    Route::delete('/cart', [StorefrontCartController::class, 'clear'])->name('storefront.cart.clear');
    Route::post('/cart/coupon', [StorefrontCartController::class, 'applyCoupon'])->name('storefront.cart.coupon.apply');
    Route::delete('/cart/coupon', [StorefrontCartController::class, 'removeCoupon'])->name('storefront.cart.coupon.remove');
    Route::post('/cart/currency', [StorefrontCartController::class, 'setCurrency'])->name('storefront.cart.currency');

    Route::get('/checkout', [StorefrontCartController::class, 'checkoutForm'])->name('storefront.checkout');
    Route::post('/checkout', [StorefrontCartController::class, 'checkout'])->name('storefront.checkout.store');
    Route::get('/checkout/confirmation/{order}', [StorefrontCartController::class, 'confirmation'])->name('storefront.checkout.confirmation');
});
