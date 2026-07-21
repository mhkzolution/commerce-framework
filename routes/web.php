<?php

use App\Http\Controllers\Admin\DesignSystemController;
use App\Http\Controllers\Admin\GlobalSearchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/shop');

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/design-system', DesignSystemController::class)->name('design-system');
    Route::get('/search', GlobalSearchController::class)->name('search');
});
