<?php

declare(strict_types=1);

use Commerce\Pos\Http\Controllers\Admin\RegisterController;
use Commerce\Pos\Http\Controllers\Admin\SessionController;
use Commerce\Pos\Http\Controllers\Admin\TerminalController;
use Commerce\Pos\Http\Controllers\PosApiController;
use Commerce\Pos\Http\Controllers\PosController;
use Commerce\Pos\Http\Controllers\PosOrderController;
use Commerce\Pos\Http\Controllers\PosReceiptController;
use Commerce\Pos\Http\Controllers\PosReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'module:pos', 'permission:pos.terminal.use'])
        ->prefix('pos')
        ->name('pos.')
        ->group(function (): void {
            Route::get('/', [PosController::class, 'index'])->name('index');
            Route::get('/orders', [PosOrderController::class, 'index'])->name('orders.index');
            Route::middleware('feature:pos-returns')->group(function (): void {
                Route::get('/returns', [PosReturnController::class, 'index'])->name('returns.index');
                Route::post('/returns/refund', [PosReturnController::class, 'refund'])->name('returns.refund');
            });
            Route::post('/session/open', [PosController::class, 'openSession'])->name('session.open');
            Route::get('/receipt/{orderUuid}', [PosReceiptController::class, 'show'])->name('receipt.show');

            Route::prefix('api')->name('api.')->group(function (): void {
                Route::get('/state', [PosApiController::class, 'state'])->name('state');
                Route::get('/search', [PosApiController::class, 'search'])->name('search');
                Route::post('/sync', [PosApiController::class, 'sync'])->name('sync');
                Route::post('/cart/items', [PosApiController::class, 'addItem'])->name('cart.items.store');
                Route::patch('/cart/items/{purchasable}', [PosApiController::class, 'updateItem'])->name('cart.items.update');
                Route::patch('/cart/items/{purchasable}/price', [PosApiController::class, 'setLinePrice'])->name('cart.items.price');
                Route::delete('/cart/items/{purchasable}', [PosApiController::class, 'removeItem'])->name('cart.items.destroy');
                Route::delete('/cart', [PosApiController::class, 'clearCart'])->name('cart.clear');
                Route::post('/coupon', [PosApiController::class, 'applyCoupon'])->name('coupon.apply');
                Route::delete('/coupon', [PosApiController::class, 'removeCoupon'])->name('coupon.remove');
                Route::post('/customer', [PosApiController::class, 'attachCustomer'])->name('customer.attach');
                Route::get('/customers/search', [PosApiController::class, 'searchCustomers'])->name('customers.search');
                Route::patch('/notes', [PosApiController::class, 'updateNotes'])->name('notes');
                Route::patch('/payment-method', [PosApiController::class, 'updatePaymentMethod'])->name('payment-method');
                Route::patch('/payments', [PosApiController::class, 'updateMixedPayments'])->name('payments');
                Route::middleware('feature:pos-hold')->group(function (): void {
                    Route::post('/hold', [PosApiController::class, 'hold'])->name('hold');
                    Route::post('/holds/{holdId}/resume', [PosApiController::class, 'resume'])->name('holds.resume');
                });
                Route::post('/checkout', [PosApiController::class, 'checkout'])->name('checkout');
                Route::get('/receipt/{orderUuid}', [PosApiController::class, 'receiptData'])->name('receipt');
            });
        });

    Route::middleware(['auth', 'module:pos', 'permission:pos.register.view'])
        ->prefix('admin/pos')
        ->name('admin.pos.')
        ->group(function (): void {
            Route::get('/registers', [RegisterController::class, 'index'])->name('registers.index');

            Route::middleware('permission:pos.register.manage')->group(function (): void {
                Route::get('/registers/create', [RegisterController::class, 'create'])->name('registers.create');
                Route::post('/registers', [RegisterController::class, 'store'])->name('registers.store');
                Route::get('/registers/{register}/edit', [RegisterController::class, 'edit'])->name('registers.edit');
                Route::put('/registers/{register}', [RegisterController::class, 'update'])->name('registers.update');
                Route::delete('/registers/{register}', [RegisterController::class, 'destroy'])->name('registers.destroy');
            });

            Route::middleware('permission:pos.terminal.use')->group(function (): void {
                Route::get('/terminal/{register}', [TerminalController::class, 'show'])->name('terminal.show');
                Route::post('/terminal/{register}/open', [TerminalController::class, 'open'])->name('terminal.open');
                Route::get('/terminal/{register}/search', [TerminalController::class, 'search'])->name('terminal.search');
                Route::post('/terminal/{register}/items', [TerminalController::class, 'addItem'])->name('terminal.items.store');
                Route::patch('/terminal/{register}/items/{purchasable}', [TerminalController::class, 'updateItem'])->name('terminal.items.update');
                Route::delete('/terminal/{register}/items/{purchasable}', [TerminalController::class, 'removeItem'])->name('terminal.items.destroy');
                Route::post('/terminal/{register}/complete', [TerminalController::class, 'complete'])->name('terminal.complete');
                Route::post('/terminal/{register}/close', [TerminalController::class, 'closeSession'])->name('terminal.close');
            });

            Route::middleware('permission:pos.session.view')->group(function (): void {
                Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
                Route::get('/sessions/create', [SessionController::class, 'create'])->name('sessions.create');
                Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
                Route::get('/sessions/{session}/edit', [SessionController::class, 'edit'])->name('sessions.edit');
                Route::put('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
                Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');
            });
        });
});
