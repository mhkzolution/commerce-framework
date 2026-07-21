<?php

declare(strict_types=1);

use Commerce\Crm\Http\Controllers\Admin\DealController;
use Commerce\Crm\Http\Controllers\Admin\LeadController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:crm.lead.view'])
        ->prefix('admin/crm')
        ->name('admin.crm.')
        ->group(function (): void {
            Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');

            Route::middleware('permission:crm.lead.manage')->group(function (): void {
                Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
                Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
                Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
                Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
                Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
            });

            Route::middleware('permission:crm.deal.view')->group(function (): void {
                Route::get('/deals', [DealController::class, 'index'])->name('deals.index');
            });

            Route::middleware('permission:crm.deal.manage')->group(function (): void {
                Route::get('/deals/create', [DealController::class, 'create'])->name('deals.create');
                Route::post('/deals', [DealController::class, 'store'])->name('deals.store');
                Route::get('/deals/{deal}/edit', [DealController::class, 'edit'])->name('deals.edit');
                Route::put('/deals/{deal}', [DealController::class, 'update'])->name('deals.update');
                Route::delete('/deals/{deal}', [DealController::class, 'destroy'])->name('deals.destroy');
            });
        });
});
