<?php

declare(strict_types=1);

use Commerce\Cms\Http\Controllers\Admin\CategoryController;
use Commerce\Cms\Http\Controllers\Admin\PageController;
use Commerce\Cms\Http\Controllers\Admin\PostController;
use Commerce\Cms\Http\Controllers\Admin\TagController;
use Commerce\Cms\Http\Controllers\Storefront\AuthorController;
use Commerce\Cms\Http\Controllers\Storefront\CategoryController as StorefrontCategoryController;
use Commerce\Cms\Http\Controllers\Storefront\PageController as StorefrontPageController;
use Commerce\Cms\Http\Controllers\Storefront\PostController as StorefrontPostController;
use Commerce\Cms\Http\Controllers\Storefront\TagController as StorefrontTagController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware('module:cms')->group(function (): void {
        Route::get('/pages/{slug}', [StorefrontPageController::class, 'show'])->name('storefront.cms.pages.show');
    });

    Route::middleware('module:blog')->group(function (): void {
        Route::get('/blog', [StorefrontPostController::class, 'index'])->name('storefront.cms.posts.index');
        Route::get('/blog/category/{slug}', [StorefrontCategoryController::class, 'show'])->name('storefront.cms.categories.show');
        Route::get('/blog/tag/{slug}', [StorefrontTagController::class, 'show'])->name('storefront.cms.tags.show');
        Route::get('/blog/author/{author}', [AuthorController::class, 'show'])->name('storefront.cms.authors.show');
        Route::get('/blog/preview/{post}', [StorefrontPostController::class, 'preview'])
            ->middleware('signed')
            ->name('storefront.cms.posts.preview');
        Route::get('/blog/{slug}', [StorefrontPostController::class, 'show'])->name('storefront.cms.posts.show');
    });

    Route::middleware(['auth', 'permission:cms.page.view'])
        ->prefix('admin/cms')
        ->name('admin.cms.')
        ->group(function (): void {
            Route::middleware('module:cms')->group(function (): void {
                Route::get('/pages', [PageController::class, 'index'])->name('pages.index');

                Route::middleware('permission:cms.page.manage')->group(function (): void {
                    Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
                    Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
                    Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
                    Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
                    Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
                });
            });

            Route::middleware(['module:blog', 'permission:cms.post.view'])->group(function (): void {
                Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
            });

            Route::middleware(['module:blog', 'permission:cms.post.manage'])->group(function (): void {
                Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
                Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
                Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
                Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
                Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
            });

            Route::middleware(['module:blog', 'permission:cms.category.view'])->group(function (): void {
                Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
            });

            Route::middleware(['module:blog', 'permission:cms.category.manage'])->group(function (): void {
                Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
                Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
                Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
                Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
                Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
            });

            Route::middleware(['module:blog', 'permission:cms.tag.view'])->group(function (): void {
                Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
            });

            Route::middleware(['module:blog', 'permission:cms.tag.manage'])->group(function (): void {
                Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
                Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
            });
        });
});
