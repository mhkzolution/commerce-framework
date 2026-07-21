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
        Route::middleware('guest:customer')->group(function (): void {
            Route::get('/login', [AccountController::class, 'showLogin'])->name('account.login');
            Route::post('/login', [AccountController::class, 'login'])->name('account.login.store');
            Route::get('/register', [AccountController::class, 'showRegister'])->name('account.register');
            Route::post('/register', [AccountController::class, 'register'])->name('account.register.store');
        });

        Route::middleware('auth:customer')->group(function (): void {
            Route::get('/', [AccountController::class, 'show'])->name('account');
            Route::post('/logout', [AccountController::class, 'logout'])->name('account.logout');
            Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
            Route::delete('/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('account.addresses.destroy');
        });
    });
});
