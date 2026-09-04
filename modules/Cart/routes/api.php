<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Contracts\CheckoutServiceInterface;
use Commerce\Cart\DTO\CartLineData;
use Commerce\Cart\Http\Controllers\Api\V1\StorefrontNotificationFeedController;
use Commerce\Cart\Http\Controllers\Api\V1\StorefrontQuickViewController;
use Commerce\Cart\Http\Requests\AddCartLineRequest;
use Commerce\Cart\Http\Requests\CheckoutRequest;
use Commerce\Cart\Http\Requests\UpdateCartLineRequest;
use Commerce\Cart\Http\Resources\CartResource;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Orders\Http\Resources\OrderResource;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api', 'web'])->group(function (): void {
    Route::get('/cart', function (CartServiceInterface $cart) {
        return ApiResponse::success(new CartResource($cart->get()));
    })->name('api.v1.cart.show');

    Route::post('/cart/items', function (AddCartLineRequest $request, CartServiceInterface $cart) {
        try {
            $data = $cart->add(new CartLineData(
                purchasableUuid: $request->validated('purchasable_uuid'),
                quantity: (int) $request->validated('quantity'),
            ));

            return ApiResponse::success(new CartResource($data));
        } catch (DomainException|EntityNotFoundException $exception) {
            return ApiResponse::error('cart.invalid_line', $exception->getMessage(), status: 422);
        }
    })->name('api.v1.cart.items.store');

    Route::patch('/cart/items/{purchasableUuid}', function (UpdateCartLineRequest $request, CartServiceInterface $cart, string $purchasableUuid) {
        try {
            $data = $cart->update($purchasableUuid, (int) $request->validated('quantity'));

            return ApiResponse::success(new CartResource($data));
        } catch (DomainException|EntityNotFoundException $exception) {
            return ApiResponse::error('cart.invalid_line', $exception->getMessage(), status: 422);
        }
    })->name('api.v1.cart.items.update');

    Route::delete('/cart/items/{purchasableUuid}', function (CartServiceInterface $cart, string $purchasableUuid) {
        return ApiResponse::success(new CartResource($cart->remove($purchasableUuid)));
    })->name('api.v1.cart.items.destroy');

    Route::delete('/cart', function (CartServiceInterface $cart) {
        $cart->clear();

        return ApiResponse::success(['cleared' => true]);
    })->name('api.v1.cart.clear');

    Route::post('/cart/coupon', function (CartServiceInterface $cart) {
        try {
            return ApiResponse::success(new CartResource($cart->applyCoupon((string) request()->string('code'))));
        } catch (DomainException $exception) {
            return ApiResponse::error('cart.invalid_coupon', $exception->getMessage(), status: 422);
        }
    })->name('api.v1.cart.coupon.apply');

    Route::delete('/cart/coupon', function (CartServiceInterface $cart) {
        return ApiResponse::success(new CartResource($cart->removeCoupon()));
    })->name('api.v1.cart.coupon.remove');

    Route::put('/cart/currency', function (CartServiceInterface $cart) {
        try {
            return ApiResponse::success(new CartResource($cart->setCurrency((string) request()->string('currency'))));
        } catch (DomainException $exception) {
            return ApiResponse::error('cart.invalid_currency', $exception->getMessage(), status: 422);
        }
    })->name('api.v1.cart.currency');

    Route::post('/cart/checkout', function (CheckoutRequest $request, CheckoutServiceInterface $checkout) {
        try {
            $order = $checkout->checkout($request->toCheckoutData());

            return ApiResponse::success(new OrderResource($order), status: 201);
        } catch (DomainException|EntityNotFoundException $exception) {
            return ApiResponse::error('checkout.failed', $exception->getMessage(), status: 422);
        }
    })->name('api.v1.cart.checkout');
});

Route::prefix('api/v1/storefront')->middleware(['api', 'web'])->name('api.v1.storefront.')->group(function (): void {
    Route::get('/products/{uuid}/quick-view', [StorefrontQuickViewController::class, 'show'])->name('products.quick-view');
    Route::get('/customer-experience/notifications', [StorefrontNotificationFeedController::class, 'index'])->name('customer-experience.notifications');
});
