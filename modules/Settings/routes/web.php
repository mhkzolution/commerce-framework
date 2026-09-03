<?php

declare(strict_types=1);

use Commerce\Settings\Http\Controllers\Admin\FooterController;
use Commerce\Settings\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:settings.setting.view'])
        ->prefix('admin/settings')
        ->name('admin.settings.')
        ->group(function (): void {
            Route::middleware('module:footer-management')->group(function (): void {
                Route::get('/footer', [FooterController::class, 'show'])->name('footer.show');
                Route::put('/footer', [FooterController::class, 'update'])
                    ->middleware('permission:settings.setting.update')
                    ->name('footer.update');
                Route::post('/footer/preview', [FooterController::class, 'preview'])
                    ->middleware('permission:settings.setting.update')
                    ->name('footer.preview');
            });

            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/{group}', [SettingsController::class, 'update'])
                ->middleware('permission:settings.setting.update')
                ->name('update');
            Route::post('/{group}/reset', [SettingsController::class, 'reset'])
                ->middleware('permission:settings.setting.update')
                ->name('reset');
        });
});
