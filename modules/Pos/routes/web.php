<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Commerce\Pos\Http\Controllers\Admin\RegisterController;
use Commerce\Pos\Http\Controllers\Admin\SessionController;

Route::middleware(['web', 'auth'])->prefix('admin/pos')->name('admin.pos.')->group(function (): void {
    Route::get('/registers', [RegisterController::class, 'index'])->name('registers.index');
    Route::get('/registers/create', [RegisterController::class, 'create'])->name('registers.create');
    Route::post('/registers', [RegisterController::class, 'store'])->name('registers.store');
    Route::get('/registers/{register}/edit', [RegisterController::class, 'edit'])->name('registers.edit');
    Route::put('/registers/{register}', [RegisterController::class, 'update'])->name('registers.update');
    Route::delete('/registers/{register}', [RegisterController::class, 'destroy'])->name('registers.destroy');
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/create', [SessionController::class, 'create'])->name('sessions.create');
    Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
    Route::get('/sessions/{session}/edit', [SessionController::class, 'edit'])->name('sessions.edit');
    Route::put('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
    Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');
});
