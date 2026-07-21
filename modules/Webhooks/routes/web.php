<?php

declare(strict_types=1);

use Commerce\Webhooks\Http\Controllers\Admin\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:webhooks.webhook.view'])
        ->prefix('admin/webhooks')
        ->name('admin.webhooks.')
        ->group(function (): void {
            Route::get('/', [WebhookController::class, 'index'])->name('index');

            Route::middleware('permission:webhooks.webhook.manage')->group(function (): void {
                Route::get('/create', [WebhookController::class, 'create'])->name('create');
                Route::post('/', [WebhookController::class, 'store'])->name('store');
                Route::get('/{webhook}/edit', [WebhookController::class, 'edit'])->name('edit');
                Route::put('/{webhook}', [WebhookController::class, 'update'])->name('update');
                Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
                Route::post('/{webhook}/deliveries/{delivery}/retry', [WebhookController::class, 'retryDelivery'])->name('deliveries.retry');
            });

            Route::get('/{webhook}', [WebhookController::class, 'show'])->name('show');
        });
});
