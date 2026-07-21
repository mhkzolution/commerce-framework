<?php

declare(strict_types=1);

use Commerce\Settings\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:settings.setting.view'])
        ->prefix('admin/settings')
        ->name('admin.settings.')
        ->group(function (): void {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/{group}', [SettingsController::class, 'update'])
                ->middleware('permission:settings.setting.update')
                ->name('update');
            Route::post('/{group}/reset', [SettingsController::class, 'reset'])
                ->middleware('permission:settings.setting.update')
                ->name('reset');
        });
});
