<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Contracts\Tax\TaxQuoteServiceInterface;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::get('/tax/quote', function (TaxQuoteServiceInterface $tax) {
        $subtotal = (int) request()->integer('subtotal', 0);
        $country = request()->string('country_code')->toString() ?: null;
        $currency = request()->string('currency')->toString() ?: 'USD';
        $quote = $tax->calculate($subtotal, $country, $currency);

        return ApiResponse::success([
            'total' => $quote->total,
            'lines' => $quote->lines,
        ]);
    })->name('api.v1.tax.quote');
});
