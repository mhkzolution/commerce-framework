<?php

declare(strict_types=1);

use Commerce\Notification\Http\Controllers\Admin\NotificationTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:notification.template.view'])
        ->prefix('admin/notifications')
        ->name('admin.notification.')
        ->group(function (): void {
            Route::get('/templates', [NotificationTemplateController::class, 'index'])->name('templates.index');

            Route::middleware('permission:notification.template.update')->group(function (): void {
                Route::get('/templates/{template}/edit', [NotificationTemplateController::class, 'edit'])->name('templates.edit');
                Route::put('/templates/{template}', [NotificationTemplateController::class, 'update'])->name('templates.update');
            });
        });
});
