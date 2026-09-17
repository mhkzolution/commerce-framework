<?php

declare(strict_types=1);

use Commerce\Documents\Http\Controllers\Admin\CustomerTaxProfileController;
use Commerce\Documents\Http\Controllers\Admin\DocumentController;
use Commerce\Documents\Http\Controllers\Admin\OrderTaxInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'module:documents', 'permission:documents.document.view'])
        ->prefix('admin/documents')
        ->name('admin.documents.')
        ->group(function (): void {
            Route::get('/', [DocumentController::class, 'index'])->name('index');
            Route::get('/{document}/print', [DocumentController::class, 'print'])->name('print');
            Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
            Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        });
    Route::middleware(['auth', 'module:documents', 'permission:orders.order.view'])
        ->prefix('admin/orders')
        ->name('admin.orders.')
        ->group(function (): void {
            Route::post('/{order}/tax-invoice', [OrderTaxInvoiceController::class, 'store'])
                ->middleware('permission:documents.document.create')
                ->name('tax-invoice.store');
        });

    Route::middleware(['auth', 'module:documents', 'permission:customers.customer.update'])
        ->prefix('admin/customers')
        ->name('admin.customers.')
        ->group(function (): void {
            Route::put('/{customer}/tax-profile', [CustomerTaxProfileController::class, 'update'])
                ->name('tax-profile.update');
        });
});
