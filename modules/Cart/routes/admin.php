<?php

declare(strict_types=1);

use Commerce\Cart\Http\Controllers\Admin\StorefrontNavigationController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:settings.setting.view'])
        ->prefix('admin/storefront/navigation')
        ->name('admin.storefront.navigation.')
        ->group(function (): void {
            Route::get('/', [StorefrontNavigationController::class, 'show'])->name('show');

            Route::put('/', [StorefrontNavigationController::class, 'update'])
                ->middleware('permission:settings.setting.update')
                ->name('update');
        });
});
