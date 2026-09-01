<?php

declare(strict_types=1);

use Commerce\Cart\Http\Controllers\Api\V1\StorefrontNotificationFeedController;
use Commerce\Cart\Http\Controllers\Api\V1\StorefrontQuickViewController;
use Commerce\Cart\Http\Controllers\Api\V1\StorefrontSearchApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/storefront')->middleware(['api'])->name('api.v1.storefront.')->group(function (): void {
    Route::get('/search', StorefrontSearchApiController::class)->name('search');
    Route::get('/products/{uuid}/quick-view', [StorefrontQuickViewController::class, 'show'])->name('products.quick-view');
    Route::get('/customer-experience/notifications', [StorefrontNotificationFeedController::class, 'index'])->name('customer-experience.notifications');
});

Route::middleware(['api'])->group(function (): void {
    Route::get('/api/products/{uuid}/quick-view', [StorefrontQuickViewController::class, 'show'])->name('api.products.quick-view');
});
