<?php

declare(strict_types=1);

use Commerce\Customers\Http\Controllers\Admin\CustomerAddressController;
use Commerce\Customers\Http\Controllers\Admin\CustomerController;
use Commerce\Customers\Http\Controllers\Storefront\AccountController;
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
        Route::middleware(['storefront.intended', 'guest:customer'])->group(function (): void {
            Route::get('/login', [AccountController::class, 'showLogin'])->name('account.login');
            Route::post('/login', [AccountController::class, 'login'])->name('account.login.store');
            Route::get('/register', [AccountController::class, 'showRegister'])->name('account.register');
            Route::post('/register', [AccountController::class, 'register'])->name('account.register.store');
        });

        Route::middleware('auth:customer')->group(function (): void {
            Route::get('/', [AccountController::class, 'show'])->name('account');
            Route::get('/orders', [AccountController::class, 'orders'])->name('account.orders');
            Route::get('/orders/{order}', [AccountController::class, 'showOrder'])->name('account.orders.show');
            Route::post('/orders/{order}/reorder', [AccountController::class, 'reorder'])->name('account.orders.reorder');
            Route::get('/addresses', [AccountController::class, 'addresses'])->name('account.addresses');
            Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
            Route::get('/addresses/{address}/edit', [AccountController::class, 'editAddress'])->name('account.addresses.edit');
            Route::put('/addresses/{address}', [AccountController::class, 'updateAddress'])->name('account.addresses.update');
            Route::post('/addresses/{address}/default-shipping', [AccountController::class, 'setDefaultShipping'])->name('account.addresses.default-shipping');
            Route::post('/addresses/{address}/default-billing', [AccountController::class, 'setDefaultBilling'])->name('account.addresses.default-billing');
            Route::delete('/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('account.addresses.destroy');
            Route::get('/wishlist', [AccountController::class, 'wishlist'])->name('account.wishlist');
            Route::delete('/wishlist/items', [AccountController::class, 'destroyWishlistItem'])->name('account.wishlist.items.destroy');
            Route::get('/profile', [AccountController::class, 'profile'])->name('account.profile');
            Route::put('/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
            Route::get('/security', [AccountController::class, 'security'])->name('account.security');
            Route::put('/security/password', [AccountController::class, 'updatePassword'])->name('account.security.password');
            Route::post('/logout', [AccountController::class, 'logout'])->name('account.logout');
        });
    });
});
