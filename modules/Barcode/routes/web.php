<?php

declare(strict_types=1);

use Commerce\Barcode\Http\Controllers\Admin\BarcodeController;
use Commerce\Barcode\Http\Controllers\Admin\BarcodePrintController;
use Commerce\Barcode\Http\Controllers\Admin\HistoryController;
use Commerce\Barcode\Http\Controllers\Admin\TemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:barcode.print'])
        ->prefix('admin/barcode')
        ->name('admin.barcode.')
        ->group(function (): void {
            Route::get('/', [BarcodeController::class, 'index'])->name('index');
            Route::get('/search', [BarcodeController::class, 'search'])->name('search');
            Route::get('/generate', [BarcodeController::class, 'generate'])->name('generate');

            Route::post('/print', [BarcodePrintController::class, 'store'])->name('print.store');
            Route::get('/print/{job}', [BarcodePrintController::class, 'show'])->name('print.show');
            Route::get('/print/{job}/pdf', [BarcodePrintController::class, 'pdf'])->name('print.pdf');

            Route::middleware('permission:barcode.template.manage')->group(function (): void {
                Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
                Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
                Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
                Route::get('/templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
                Route::put('/templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
                Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
                Route::post('/templates/{template}/duplicate', [TemplateController::class, 'duplicate'])->name('templates.duplicate');
                Route::post('/templates/{template}/favorite', [TemplateController::class, 'favorite'])->name('templates.favorite');
            });

            Route::middleware('permission:barcode.history.view')->group(function (): void {
                Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
                Route::get('/history/{job}', [HistoryController::class, 'show'])->name('history.show');
            });

            Route::middleware('permission:barcode.history.reprint')->group(function (): void {
                Route::get('/history/{job}/reprint', [HistoryController::class, 'reprint'])->name('history.reprint');
            });
        });
});
