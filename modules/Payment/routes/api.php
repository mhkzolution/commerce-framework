<?php

declare(strict_types=1);

use Commerce\Payment\Http\Controllers\Api\V1\PaymentApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::get('/payments/config', [PaymentApiController::class, 'config'])->name('api.v1.payments.config');

    Route::get('/payments/{uuid}', [PaymentApiController::class, 'show'])->name('api.v1.payments.show');
    Route::post('/payments/{uuid}/initiate', [PaymentApiController::class, 'initiate'])->name('api.v1.payments.initiate');
    Route::post('/payments/{uuid}/pay', [PaymentApiController::class, 'pay'])->name('api.v1.payments.pay');

    Route::middleware(['auth', 'permission:payment.payment.manage'])
        ->post('/payments/{uuid}/refund', [PaymentApiController::class, 'refund'])
        ->name('api.v1.payments.refund');
});
