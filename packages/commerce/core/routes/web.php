<?php

declare(strict_types=1);

use Commerce\Core\Http\Controllers\Admin\SystemModuleController;
use Commerce\Core\Http\Controllers\Admin\TenantController;
use Commerce\Core\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

    Route::middleware(['auth', 'permission:system.module.view'])
        ->prefix('admin/system/modules')
        ->name('admin.system.modules.')
        ->group(function (): void {
            Route::get('/', [SystemModuleController::class, 'index'])->name('index');
            Route::put('/{systemModule}', [SystemModuleController::class, 'update'])
                ->middleware('permission:system.module.update')
                ->name('update');
        });

    Route::middleware(['auth', 'permission:platform.tenant.view'])
        ->prefix('admin/platform/tenants')
        ->name('admin.platform.tenants.')
        ->group(function (): void {
            Route::get('/', [TenantController::class, 'index'])->name('index');

            Route::middleware('permission:platform.tenant.manage')->group(function (): void {
                Route::get('/create', [TenantController::class, 'create'])->name('create');
                Route::post('/', [TenantController::class, 'store'])->name('store');
                Route::get('/{tenant}/edit', [TenantController::class, 'edit'])->name('edit');
                Route::put('/{tenant}', [TenantController::class, 'update'])->name('update');
            });
        });
});
