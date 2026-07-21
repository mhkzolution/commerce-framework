<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Currency\Http\Resources\CurrencyResource;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::get('/currencies', function (CurrencyConverterInterface $converter) {
        return ApiResponse::success([
            'base_currency' => $converter->baseCurrency(),
            'currencies' => CurrencyResource::collection($converter->activeCurrencies()),
        ]);
    })->name('api.v1.currencies.index');
});
