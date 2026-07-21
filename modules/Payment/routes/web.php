<?php

declare(strict_types=1);

use Commerce\Payment\Http\Controllers\Admin\PaymentController;
use Commerce\Payment\Http\Controllers\StorefrontPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:payment.payment.view'])
        ->prefix('admin/payments')
        ->name('admin.payments.')
        ->group(function (): void {
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
        });

    Route::get('/payment/{payment}', [StorefrontPaymentController::class, 'show'])->name('storefront.payment.show');
    Route::post('/payment/{payment}/pay', [StorefrontPaymentController::class, 'pay'])->name('storefront.payment.pay');
    Route::post('/payment/{payment}/fail', [StorefrontPaymentController::class, 'fail'])->name('storefront.payment.fail');
});
