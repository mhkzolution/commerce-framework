<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Catalog\Http\Resources\AttributeResource;
use Commerce\Catalog\Http\Resources\BrandResource;
use Commerce\Catalog\Http\Resources\CategoryResource;
use Commerce\Catalog\Http\Resources\CollectionResource;
use Commerce\Catalog\Http\Resources\TagResource;
use Commerce\Catalog\Services\AttributeQueryService;
use Commerce\Catalog\Services\AttributeSetService;
use Commerce\Catalog\Services\BrandQueryService;
use Commerce\Catalog\Services\CategoryQueryService;
use Commerce\Catalog\Services\CollectionQueryService;
use Commerce\Catalog\Services\TagQueryService;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/catalog')->middleware(['api', 'auth'])->group(function (): void {
    Route::middleware('permission:catalog.category.view')->group(function (): void {
        Route::get('/categories', function (CategoryQueryService $categories) {
            $paginator = $categories->paginate();

            return ApiResponse::success(
                CategoryResource::collection($paginator->items()),
                meta: [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                ],
            );
        })->name('api.v1.catalog.categories.index');

        Route::get('/categories/{uuid}', function (CategoryQueryService $categories, string $uuid) {
            $category = $categories->findByUuid($uuid);

            if ($category === null) {
                return ApiResponse::error('catalog.category.not_found', 'Category not found.', status: 404);
            }

            return ApiResponse::success(new CategoryResource($category));
        })->name('api.v1.catalog.categories.show');
    });

    Route::middleware('permission:catalog.brand.view')->group(function (): void {
        Route::get('/brands', function (BrandQueryService $brands) {
            $paginator = $brands->paginate();

            return ApiResponse::success(
                BrandResource::collection($paginator->items()),
                meta: [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                ],
            );
        })->name('api.v1.catalog.brands.index');

        Route::get('/brands/{uuid}', function (BrandQueryService $brands, string $uuid) {
            $brand = $brands->findByUuid($uuid);

            if ($brand === null) {
                return ApiResponse::error('catalog.brand.not_found', 'Brand not found.', status: 404);
            }

            return ApiResponse::success(new BrandResource($brand));
        })->name('api.v1.catalog.brands.show');
    });

    Route::middleware('permission:catalog.collection.view')->group(function (): void {
        Route::get('/collections', function (CollectionQueryService $collections) {
            $paginator = $collections->paginate();

            return ApiResponse::success(
                CollectionResource::collection($paginator->items()),
                meta: [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                ],
            );
        })->name('api.v1.catalog.collections.index');

        Route::get('/collections/{uuid}', function (CollectionQueryService $collections, string $uuid) {
            $collection = $collections->findByUuid($uuid);

            if ($collection === null) {
                return ApiResponse::error('catalog.collection.not_found', 'Collection not found.', status: 404);
            }

            return ApiResponse::success(new CollectionResource($collection));
        })->name('api.v1.catalog.collections.show');
    });

    Route::middleware('permission:catalog.tag.view')->group(function (): void {
        Route::get('/tags', function (TagQueryService $tags) {
            $paginator = $tags->paginate();

            return ApiResponse::success(
                TagResource::collection($paginator->items()),
                meta: [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                ],
            );
        })->name('api.v1.catalog.tags.index');
    });

    Route::middleware('permission:catalog.attribute.view')->group(function (): void {
        Route::get('/attributes', function (AttributeQueryService $attributes) {
            $paginator = $attributes->paginate();

            return ApiResponse::success(
                AttributeResource::collection($paginator->items()),
                meta: [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                ],
            );
        })->name('api.v1.catalog.attributes.index');
    });

    Route::middleware('permission:catalog.attribute_set.view')->group(function (): void {
        Route::get('/attribute-sets', function (AttributeSetService $sets) {
            $paginator = $sets->paginate();

            return ApiResponse::success(
                $paginator->items(),
                meta: [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                ],
            );
        })->name('api.v1.catalog.attribute-sets.index');
    });
});
