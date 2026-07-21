<?php

declare(strict_types=1);

use Commerce\Cms\Http\Controllers\Admin\PageController;
use Commerce\Cms\Http\Controllers\Admin\PostController;
use Commerce\Cms\Http\Controllers\Storefront\PageController as StorefrontPageController;
use Commerce\Cms\Http\Controllers\Storefront\PostController as StorefrontPostController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/pages/{slug}', [StorefrontPageController::class, 'show'])->name('storefront.cms.pages.show');
    Route::get('/blog', [StorefrontPostController::class, 'index'])->name('storefront.cms.posts.index');
    Route::get('/blog/{slug}', [StorefrontPostController::class, 'show'])->name('storefront.cms.posts.show');

    Route::middleware(['auth', 'permission:cms.page.view'])
        ->prefix('admin/cms')
        ->name('admin.cms.')
        ->group(function (): void {
            Route::get('/pages', [PageController::class, 'index'])->name('pages.index');

            Route::middleware('permission:cms.page.manage')->group(function (): void {
                Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
                Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
                Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
                Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
                Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
            });

            Route::middleware('permission:cms.post.view')->group(function (): void {
                Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
            });

            Route::middleware('permission:cms.post.manage')->group(function (): void {
                Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
                Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
                Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
                Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
                Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
            });
        });
});
