<?php

declare(strict_types=1);

use Commerce\Navigation\Http\Controllers\Admin\NavigationMenuController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'module:navigation', 'permission:navigation.menu.view'])
        ->prefix('admin/navigation')
        ->group(function (): void {
            Route::get('/', [NavigationMenuController::class, 'index'])->name('admin.navigation.show');
            Route::get('/{menu}', [NavigationMenuController::class, 'edit'])->name('admin.navigation.menus.edit');
            Route::put('/{menu}', [NavigationMenuController::class, 'update'])
                ->middleware('permission:navigation.menu.update')
                ->name('admin.navigation.menus.update');
        });
});
