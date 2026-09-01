<?php

declare(strict_types=1);

use Commerce\Customers\Http\Controllers\Admin\CustomerAddressController;
use Commerce\Customers\Http\Controllers\Admin\CustomerController;
use Commerce\Customers\Http\Controllers\Storefront\AccountController;
use Commerce\Customers\Http\Controllers\Storefront\OAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:customers.customer.view'])
        ->prefix('admin/customers')
        ->name('admin.customers.')
        ->group(function (): void {
            Route::get('/', [CustomerController::class, 'index'])->name('index');

            Route::middleware('permission:customers.customer.create')->group(function (): void {
                Route::get('/create', [CustomerController::class, 'create'])->name('create');
                Route::post('/', [CustomerController::class, 'store'])->name('store');
            });

            Route::middleware('permission:customers.customer.update')->group(function (): void {
                Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
                Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
                Route::post('/{customer}/addresses', [CustomerAddressController::class, 'store'])->name('addresses.store');
                Route::delete('/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('addresses.destroy');
            });

            Route::delete('/{customer}', [CustomerController::class, 'destroy'])
                ->middleware('permission:customers.customer.delete')
                ->name('destroy');
        });

    Route::prefix('account')->name('storefront.')->group(function (): void {
        Route::middleware('guest:customer')->group(function (): void {
            Route::get('/login', [AccountController::class, 'showLogin'])->name('account.login');
            Route::post('/login', [AccountController::class, 'login'])
                ->middleware('throttle:10,1')
                ->name('account.login.store');
            Route::post('/login/otp/send', [AccountController::class, 'sendOtp'])
                ->middleware('throttle:3,1')
                ->name('account.otp.send');
            Route::get('/forgot-password', [AccountController::class, 'showForgotPassword'])->name('account.password.request');
            Route::post('/forgot-password', [AccountController::class, 'forgotPassword'])
                ->middleware('throttle:5,1')
                ->name('account.password.email');
            Route::get('/reset-password/{token}', [AccountController::class, 'showResetPassword'])->name('account.password.reset');
            Route::post('/reset-password', [AccountController::class, 'resetPassword'])
                ->middleware('throttle:5,1')
                ->name('account.password.update');
            Route::get('/register', [AccountController::class, 'showRegister'])->name('account.register');
            Route::post('/register', [AccountController::class, 'register'])
                ->middleware('throttle:5,1')
                ->name('account.register.store');
            Route::get('/login/oauth/{provider}', [OAuthController::class, 'redirect'])
                ->name('account.oauth.redirect')
                ->where('provider', 'google|line|apple');
            Route::get('/login/oauth/{provider}/callback', [OAuthController::class, 'callback'])
                ->name('account.oauth.callback')
                ->where('provider', 'google|line|apple');
        });

        Route::middleware('auth:customer')->group(function (): void {
            Route::get('/', [AccountController::class, 'show'])->name('account');
            Route::get('/orders', [AccountController::class, 'orders'])->name('account.orders');
            Route::get('/wishlist', [AccountController::class, 'wishlist'])->name('account.wishlist');
            Route::get('/shipping', [AccountController::class, 'shipping'])->name('account.shipping');
            Route::get('/profile', [AccountController::class, 'profile'])->name('account.profile');
            Route::put('/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
            Route::get('/orders/{order}', [AccountController::class, 'showOrder'])->name('account.orders.show');
            Route::post('/logout', [AccountController::class, 'logout'])->name('account.logout');
            Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
            Route::delete('/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('account.addresses.destroy');
        });
    });
});
