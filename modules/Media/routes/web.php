<?php

declare(strict_types=1);

use Commerce\Media\Http\Controllers\Admin\MediaController;
use Commerce\Media\Http\Controllers\Admin\MediaFolderController;
use Commerce\Media\Http\Controllers\Admin\MediaPickerController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:media.media.view'])
        ->prefix('admin/media')
        ->name('admin.media.')
        ->group(function (): void {
            Route::get('/', [MediaController::class, 'index'])->name('index');
            Route::get('/picker', MediaPickerController::class)->name('picker');
            Route::get('/{media}/download', [MediaController::class, 'download'])->name('download');
            Route::get('/{media}', [MediaController::class, 'show'])->name('show');

            Route::middleware('permission:media.media.upload')
                ->post('/', [MediaController::class, 'store'])
                ->name('store');

            Route::middleware('permission:media.media.upload')
                ->post('/import', [MediaController::class, 'import'])
                ->name('import');

            Route::middleware('permission:media.media.update')
                ->put('/{media}', [MediaController::class, 'update'])
                ->name('update');

            Route::middleware('permission:media.media.update')
                ->post('/bulk-move', [MediaController::class, 'bulkMove'])
                ->name('bulk-move');

            Route::middleware('permission:media.media.update')
                ->post('/bulk-tag', [MediaController::class, 'bulkTag'])
                ->name('bulk-tag');

            Route::middleware('permission:media.media.update')
                ->post('/bulk-regenerate', [MediaController::class, 'bulkRegenerate'])
                ->name('bulk-regenerate');

            Route::middleware('permission:media.media.upload')
                ->post('/{media}/replace', [MediaController::class, 'replace'])
                ->name('replace');

            Route::middleware('permission:media.media.delete')
                ->delete('/{media}', [MediaController::class, 'destroy'])
                ->name('destroy');

            Route::middleware('permission:media.media.delete')
                ->post('/bulk-delete', [MediaController::class, 'bulkDelete'])
                ->name('bulk-delete');

            Route::middleware('permission:media.folder.create')
                ->post('/folders', [MediaFolderController::class, 'store'])
                ->name('folders.store');

            Route::middleware('permission:media.folder.update')
                ->put('/folders/{folder}', [MediaFolderController::class, 'update'])
                ->name('folders.update');

            Route::middleware('permission:media.folder.delete')
                ->delete('/folders/{folder}', [MediaFolderController::class, 'destroy'])
                ->name('folders.destroy');
        });
});
