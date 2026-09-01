<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Api\Support\ApiInclude;
use Commerce\Product\Http\Controllers\Api\V1\ProductWorkspaceApiController;
use Commerce\Product\Http\Resources\ProductResource;
use Commerce\Product\Services\ProductQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::get('/products', function (ProductQueryService $products, Request $request) {
        $relations = ApiInclude::relations($request, ProductResource::INCLUDE_MAP);
        $search = $request->string('search')->toString();
        $paginator = $search !== ''
            ? $products->paginateStorefrontSearch($search, relations: $relations)
            : $products->paginateStorefront(relations: $relations);

        return ApiResponse::success(
            ProductResource::collection($paginator->items()),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        );
    })->name('api.v1.products.index');

    Route::get('/products/{uuid}', function (ProductQueryService $products, Request $request, string $uuid) {
        $relations = ApiInclude::relations($request, ProductResource::INCLUDE_MAP);
        $product = $products->findBySlug($uuid, $relations) ?? $products->findByUuid($uuid, $relations);

        if ($product === null || ! $product->isVisibleOnStorefront()) {
            return ApiResponse::error('product.not_found', 'Product not found.', status: 404);
        }

        return ApiResponse::success(new ProductResource($product));
    })->name('api.v1.products.show');

    Route::prefix('admin/products')->middleware(['auth'])->group(function (): void {
        Route::post('/workspace', [ProductWorkspaceApiController::class, 'store'])
            ->middleware('permission:product.product.create')
            ->name('api.v1.admin.products.workspace.store');

        Route::get('/{uuid}/workspace', [ProductWorkspaceApiController::class, 'show'])
            ->middleware('permission:product.product.view')
            ->name('api.v1.admin.products.workspace.show');

        Route::put('/{uuid}/workspace', [ProductWorkspaceApiController::class, 'update'])
            ->middleware('permission:product.product.update')
            ->name('api.v1.admin.products.workspace.update');
    });
});
