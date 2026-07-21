<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Contracts\Promotion\PromotionServiceInterface;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::post('/promotions/validate', function (PromotionServiceInterface $promotions) {
        $code = request()->string('code')->toString();
        $subtotal = (int) request()->integer('subtotal', 0);
        $quote = $promotions->resolve($code, $subtotal);

        if ($quote === null) {
            return ApiResponse::error('promotion.invalid', 'Invalid or expired promotion code.', status: 422);
        }

        return ApiResponse::success([
            'uuid' => $quote->uuid,
            'code' => $quote->code,
            'name' => $quote->name,
            'discount' => $quote->discount,
        ]);
    })->name('api.v1.promotions.validate');
});
