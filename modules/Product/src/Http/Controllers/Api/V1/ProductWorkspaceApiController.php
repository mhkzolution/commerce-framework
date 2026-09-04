<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Controllers\Api\V1;

use Commerce\Api\Responses\ApiResponse;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Product\Http\Requests\SaveProductWorkspaceApiRequest;
use Commerce\Product\Http\Resources\ProductWorkspaceResource;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductWorkspaceSaveService;
use Commerce\Product\Services\ProductWorkspaceStateBuilder;
use Commerce\Product\Support\WorkspacePayload;
use Illuminate\Routing\Controller;

final class ProductWorkspaceApiController extends Controller
{
    public function __construct(
        private readonly ProductWorkspaceSaveService $workspaceSaveService,
        private readonly ProductWorkspaceStateBuilder $workspaceStateBuilder,
    ) {}

    public function show(string $uuid)
    {
        $product = Product::query()
            ->with(['variants', 'media', 'categories', 'tags', 'attributeValues', 'attributeSet.attributes'])
            ->where('uuid', $uuid)
            ->first();

        if ($product === null) {
            return ApiResponse::error('product.not_found', 'Product not found.', status: 404);
        }

        $stockLevels = $this->workspaceStateBuilder->stockLevelsFor($product);

        return ApiResponse::success(new ProductWorkspaceResource(
            $product,
            $this->workspaceStateBuilder->build($product, $stockLevels),
        ));
    }

    public function store(SaveProductWorkspaceApiRequest $request)
    {
        try {
            $product = $this->workspaceSaveService->create(
                WorkspacePayload::fromArray($request->validated()),
            );

            $stockLevels = $this->workspaceStateBuilder->stockLevelsFor($product);

            return ApiResponse::success(new ProductWorkspaceResource(
                $product,
                $this->workspaceStateBuilder->build($product, $stockLevels),
            ), status: 201);
        } catch (DomainException $exception) {
            return ApiResponse::error('product.invalid', $exception->getMessage(), status: 422);
        }
    }

    public function update(SaveProductWorkspaceApiRequest $request, string $uuid)
    {
        try {
            $product = $this->workspaceSaveService->update(
                $uuid,
                WorkspacePayload::fromArray($request->validated()),
            );

            $stockLevels = $this->workspaceStateBuilder->stockLevelsFor($product);

            return ApiResponse::success(new ProductWorkspaceResource(
                $product,
                $this->workspaceStateBuilder->build($product, $stockLevels),
            ));
        } catch (EntityNotFoundException) {
            return ApiResponse::error('product.not_found', 'Product not found.', status: 404);
        } catch (DomainException $exception) {
            return ApiResponse::error('product.invalid', $exception->getMessage(), status: 422);
        }
    }
}
