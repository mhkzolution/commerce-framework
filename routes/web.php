<?php

use App\Http\Controllers\Admin\DesignSystemController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/shop');

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/design-system', DesignSystemController::class)->name('design-system');
});
