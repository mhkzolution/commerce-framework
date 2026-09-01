<?php

declare(strict_types=1);

namespace Commerce\Wishlist\Http\Controllers\Api\V1;

use Commerce\Api\Responses\ApiResponse;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Customers\Models\Customer;
use Commerce\Wishlist\DTO\WishlistItemReferenceData;
use Commerce\Wishlist\Http\Requests\DestroyWishlistItemRequest;
use Commerce\Wishlist\Http\Requests\MergeWishlistRequest;
use Commerce\Wishlist\Http\Requests\PreviewWishlistRequest;
use Commerce\Wishlist\Http\Requests\StoreWishlistItemRequest;
use Commerce\Wishlist\Services\StorefrontWishlistPresenter;
use Commerce\Wishlist\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

final class StorefrontWishlistApiController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlistService,
        private readonly StorefrontWishlistPresenter $presenter,
    ) {}

    public function index(): JsonResponse
    {
        $customer = $this->customer();

        $items = $this->wishlistService->itemsForCustomer($customer);

        return ApiResponse::success([
            'count' => $items->count(),
            'items' => $this->presenter->presentItems($items),
        ]);
    }

    public function store(StoreWishlistItemRequest $request): JsonResponse
    {
        $customer = $this->customer();
        $reference = WishlistItemReferenceData::fromArray($request->validated());

        if ($reference === null) {
            return ApiResponse::error('wishlist.invalid_item', 'Invalid wishlist item.', status: 422);
        }

        try {
            $this->wishlistService->addItem($customer, $reference);
        } catch (EntityNotFoundException $exception) {
            return ApiResponse::error('wishlist.not_found', $exception->getMessage(), status: 404);
        }

        $items = $this->wishlistService->itemsForCustomer($customer);

        return ApiResponse::success([
            'count' => $items->count(),
            'items' => $this->presenter->presentItems($items),
        ], status: 201);
    }

    public function destroy(DestroyWishlistItemRequest $request): JsonResponse
    {
        $customer = $this->customer();
        $reference = WishlistItemReferenceData::fromArray($request->validated());

        if ($reference === null) {
            return ApiResponse::error('wishlist.invalid_item', 'Invalid wishlist item.', status: 422);
        }

        $this->wishlistService->removeItem($customer, $reference);

        $items = $this->wishlistService->itemsForCustomer($customer);

        return ApiResponse::success([
            'count' => $items->count(),
            'items' => $this->presenter->presentItems($items),
        ]);
    }

    public function merge(MergeWishlistRequest $request): JsonResponse
    {
        $customer = $this->customer();
        $merged = $this->wishlistService->mergeItems($customer, $request->validated('items'));

        $items = $this->wishlistService->itemsForCustomer($customer);

        return ApiResponse::success([
            'merged' => $merged,
            'count' => $items->count(),
            'items' => $this->presenter->presentItems($items),
        ]);
    }

    public function preview(PreviewWishlistRequest $request): JsonResponse
    {
        $items = $this->presenter->presentReferences($request->validated('items'));

        return ApiResponse::success([
            'count' => count($items),
            'items' => $items,
        ]);
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($customer instanceof Customer, 401);

        return $customer;
    }
}
