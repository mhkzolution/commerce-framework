<?php

declare(strict_types=1);

use Commerce\Pos\Http\Controllers\Admin\RegisterController;
use Commerce\Pos\Http\Controllers\Admin\SessionController;
use Commerce\Pos\Http\Controllers\Admin\TerminalController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:pos.register.view'])
        ->prefix('admin/pos')
        ->name('admin.pos.')
        ->group(function (): void {
            Route::get('/registers', [RegisterController::class, 'index'])->name('registers.index');

            Route::middleware('permission:pos.register.manage')->group(function (): void {
                Route::get('/registers/create', [RegisterController::class, 'create'])->name('registers.create');
                Route::post('/registers', [RegisterController::class, 'store'])->name('registers.store');
                Route::get('/registers/{register}/edit', [RegisterController::class, 'edit'])->name('registers.edit');
                Route::put('/registers/{register}', [RegisterController::class, 'update'])->name('registers.update');
                Route::delete('/registers/{register}', [RegisterController::class, 'destroy'])->name('registers.destroy');
            });

            Route::middleware('permission:pos.terminal.use')->group(function (): void {
                Route::get('/terminal/{register}', [TerminalController::class, 'show'])->name('terminal.show');
                Route::get('/terminal/{register}/search', [TerminalController::class, 'search'])->name('terminal.search');
                Route::post('/terminal/{register}/items', [TerminalController::class, 'addItem'])->name('terminal.items.store');
                Route::patch('/terminal/{register}/items/{purchasable}', [TerminalController::class, 'updateItem'])->name('terminal.items.update');
                Route::delete('/terminal/{register}/items/{purchasable}', [TerminalController::class, 'removeItem'])->name('terminal.items.destroy');
                Route::post('/terminal/{register}/complete', [TerminalController::class, 'complete'])->name('terminal.complete');
                Route::post('/terminal/{register}/close', [TerminalController::class, 'closeSession'])->name('terminal.close');
            });

            Route::middleware('permission:pos.session.view')->group(function (): void {
                Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
                Route::get('/sessions/create', [SessionController::class, 'create'])->name('sessions.create');
                Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
                Route::get('/sessions/{session}/edit', [SessionController::class, 'edit'])->name('sessions.edit');
                Route::put('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
                Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');
            });
        });
});
