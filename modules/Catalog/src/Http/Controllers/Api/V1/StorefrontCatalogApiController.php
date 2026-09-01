<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Controllers\Api\V1;

use Commerce\Api\Responses\ApiResponse;
use Commerce\Catalog\Http\Resources\BrandResource;
use Commerce\Catalog\Http\Resources\CategoryResource;
use Commerce\Catalog\Http\Resources\CollectionResource;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;
use Commerce\Catalog\Services\BrandService;
use Commerce\Catalog\Services\CategoryService;
use Commerce\Catalog\Services\CollectionQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class StorefrontCatalogApiController extends Controller
{
    public function brands(): JsonResponse
    {
        $brands = Brand::query()->forStorefrontNavigation()->get();

        return ApiResponse::success(BrandResource::collection($brands));
    }

    public function showBrand(string $slug, BrandService $brands): JsonResponse
    {
        $brand = $brands->findActiveBySlug($slug);

        if ($brand === null) {
            return ApiResponse::error('catalog.brand.not_found', 'Brand not found.', status: 404);
        }

        return ApiResponse::success(new BrandResource($brand));
    }

    public function categories(): JsonResponse
    {
        $categories = Category::query()->forStorefrontNavigation()->get();

        return ApiResponse::success(CategoryResource::collection($categories));
    }

    public function showCategory(string $slug, CategoryService $categories): JsonResponse
    {
        $category = $categories->findActiveBySlug($slug);

        if ($category === null) {
            return ApiResponse::error('catalog.category.not_found', 'Category not found.', status: 404);
        }

        return ApiResponse::success(new CategoryResource($category));
    }

    public function collections(): JsonResponse
    {
        $items = Collection::query()->forStorefrontNavigation()->get();

        return ApiResponse::success(CollectionResource::collection($items));
    }

    public function showCollection(string $slug, CollectionQueryService $queryService): JsonResponse
    {
        $collection = $queryService->findBySlug($slug);

        if ($collection === null) {
            return ApiResponse::error('catalog.collection.not_found', 'Collection not found.', status: 404);
        }

        return ApiResponse::success(new CollectionResource($collection));
    }
}
