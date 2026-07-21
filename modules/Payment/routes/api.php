<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Contracts\Payment\PaymentQueryServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Payment\Http\Resources\PaymentResource;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::get('/payments/{uuid}', function (PaymentQueryServiceInterface $payments, string $uuid) {
        $payment = $payments->findByUuid($uuid);

        if ($payment === null) {
            return ApiResponse::error('payment.not_found', 'Payment not found.', status: 404);
        }

        return ApiResponse::success(new PaymentResource($payment));
    })->name('api.v1.payments.show');

    Route::post('/payments/{uuid}/pay', function (PaymentServiceInterface $payments, string $uuid) {
        try {
            if (! config('payment.simulate_gateway', true)) {
                return ApiResponse::error('payment.gateway_unavailable', 'Payment gateway is not configured.', status: 503);
            }

            $payment = $payments->markPaid($uuid);

            return ApiResponse::success(new PaymentResource($payment));
        } catch (DomainException|EntityNotFoundException $exception) {
            return ApiResponse::error('payment.failed', $exception->getMessage(), status: 422);
        }
    })->name('api.v1.payments.pay');
});
