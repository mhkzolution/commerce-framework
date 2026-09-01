<?php

declare(strict_types=1);

namespace Commerce\Wishlist\Services;

use Commerce\Contracts\Wishlist\WishlistServiceInterface;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Customers\Models\Customer;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;
use Commerce\Wishlist\DTO\WishlistItemReferenceData;
use Commerce\Wishlist\Models\Wishlist;
use Commerce\Wishlist\Models\WishlistItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class WishlistService implements WishlistServiceInterface
{
    public function forCustomer(Customer $customer): Wishlist
    {
        return Wishlist::query()->firstOrCreate(
            ['customer_id' => $customer->id],
            ['tenant_id' => $customer->tenant_id],
        );
    }

    /**
     * @return Collection<int, WishlistItem>
     */
    public function itemsForCustomer(Customer $customer): Collection
    {
        $wishlist = $this->forCustomer($customer);

        return $wishlist->items()
            ->with(['product.variants', 'product.media', 'variant'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function countForCustomer(Customer $customer): int
    {
        $wishlist = Wishlist::query()->where('customer_id', $customer->id)->first();

        if ($wishlist === null) {
            return 0;
        }

        return (int) $wishlist->items()->count();
    }

    public function addItem(Customer $customer, WishlistItemReferenceData $reference): WishlistItem
    {
        $product = $this->resolveProduct($reference->productUuid);
        $variant = $this->resolveVariant($reference->variantUuid, $product);

        $wishlist = $this->forCustomer($customer);

        $existing = $wishlist->items()
            ->where('product_id', $product->id)
            ->first();

        if ($existing instanceof WishlistItem) {
            if ($variant !== null && $existing->product_variant_id !== $variant->id) {
                $existing->update(['product_variant_id' => $variant->id]);
            }

            return $existing->fresh(['product.variants', 'product.media', 'variant']);
        }

        return $wishlist->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ])->load(['product.variants', 'product.media', 'variant']);
    }

    public function removeItem(Customer $customer, WishlistItemReferenceData $reference): void
    {
        $product = Product::query()->where('uuid', $reference->productUuid)->first();

        if ($product === null) {
            return;
        }

        $wishlist = Wishlist::query()->where('customer_id', $customer->id)->first();

        if ($wishlist === null) {
            return;
        }

        $wishlist->items()->where('product_id', $product->id)->delete();
    }

    /**
     * @param  list<array{product_id: string, variant_id?: string|null}>  $items
     */
    public function mergeForCustomer(string $customerUuid, array $items): int
    {
        $customer = Customer::query()->where('uuid', $customerUuid)->first();

        if ($customer === null) {
            throw new EntityNotFoundException('Customer not found.');
        }

        return $this->mergeItems($customer, $items);
    }

    /**
     * @param  list<array{product_id: string, variant_id?: string|null}>  $items
     */
    public function mergeItems(Customer $customer, array $items): int
    {
        $added = 0;

        DB::transaction(function () use ($customer, $items, &$added): void {
            $wishlist = $this->forCustomer($customer);

            foreach ($items as $payload) {
                $reference = WishlistItemReferenceData::fromArray($payload);

                if ($reference === null) {
                    continue;
                }

                try {
                    $product = $this->resolveProduct($reference->productUuid);

                    if ($wishlist->items()->where('product_id', $product->id)->exists()) {
                        continue;
                    }

                    $this->addItem($customer, $reference);
                    $added++;
                } catch (EntityNotFoundException) {
                    continue;
                }
            }
        });

        return $added;
    }

    private function resolveProduct(string $productUuid): Product
    {
        $product = Product::query()
            ->where('uuid', $productUuid)
            ->visibleOnStorefront()
            ->first();

        if ($product === null) {
            throw new EntityNotFoundException('Product not found.');
        }

        return $product;
    }

    private function resolveVariant(?string $variantUuid, Product $product): ?ProductVariant
    {
        if ($variantUuid === null) {
            return $product->defaultVariant();
        }

        $variant = ProductVariant::query()
            ->where('uuid', $variantUuid)
            ->where('product_id', $product->id)
            ->first();

        if ($variant === null) {
            throw new EntityNotFoundException('Product variant not found.');
        }

        return $variant;
    }
}
