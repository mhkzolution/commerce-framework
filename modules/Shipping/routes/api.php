<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Contracts\Shipping\ShippingQuoteServiceInterface;
use Commerce\Shipping\Http\Resources\ShippingMethodResource;
use Commerce\Shipping\Http\Resources\ShippingQuoteResource;
use Commerce\Shipping\Services\ShippingMethodQueryService;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::get('/shipping/methods', function (ShippingMethodQueryService $methods) {
        return ApiResponse::success(
            ShippingMethodResource::collection($methods->activeOrdered()),
        );
    })->name('api.v1.shipping.methods.index');

    Route::get('/shipping/quotes', function (ShippingQuoteServiceInterface $quotes) {
        $subtotal = (int) request()->integer('subtotal', 0);
        $countryCode = request()->string('country_code')->toString() ?: null;
        $currency = request()->string('currency')->toString() ?: 'USD';

        return ApiResponse::success(
            ShippingQuoteResource::collection(
                collect($quotes->availableQuotes($subtotal, $countryCode, $currency)),
            ),
        );
    })->name('api.v1.shipping.quotes');
});
