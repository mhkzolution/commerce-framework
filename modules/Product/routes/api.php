<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Product\Http\Resources\ProductResource;
use Commerce\Product\Services\ProductQueryService;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::get('/products', function (ProductQueryService $products) {
        $paginator = $products->paginateStorefront();

        return ApiResponse::success(
            ProductResource::collection($paginator->items()),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        );
    })->name('api.v1.products.index');

    Route::get('/products/{uuid}', function (ProductQueryService $products, string $uuid) {
        $product = $products->findBySlug($uuid) ?? $products->findByUuid($uuid);

        if ($product === null || ! $product->isVisibleOnStorefront()) {
            return ApiResponse::error('product.not_found', 'Product not found.', status: 404);
        }

        return ApiResponse::success(new ProductResource($product));
    })->name('api.v1.products.show');
});
