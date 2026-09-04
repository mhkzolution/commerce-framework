<?php

declare(strict_types=1);

namespace Commerce\Orders\Services;

use Commerce\Contracts\Customer\CustomerQueryServiceInterface;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Customers\Models\Customer;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Support\Str;

final class AdminOrderLookupService
{
    public function __construct(
        private readonly InventoryQueryServiceInterface $inventory,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function customers(string $query, int $limit = 20): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $found = collect();

        if (app()->bound(CustomerQueryServiceInterface::class)) {
            $customers = app(CustomerQueryServiceInterface::class);

            if (Str::isUuid($query)) {
                $byId = $customers->findByUuid($query);
                if ($byId instanceof Customer) {
                    $found->push($byId);
                }
            }

            $found = $found->concat($customers->paginate(search: $query, status: 'active', perPage: $limit)->items());
        }

        return $found
            ->unique(static fn (Customer $customer): string => $customer->uuid)
            ->take($limit)
            ->map(fn (Customer $customer): array => $this->mapCustomer($customer))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function products(string $query, int $limit = 20): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $like = '%'.$query.'%';

        $variants = ProductVariant::query()
            ->with(['product.media'])
            ->where(function ($inner) use ($query, $like): void {
                $inner->where('sku', 'like', $like)
                    ->orWhere('sku', $query)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('meta->barcode', 'like', $like)
                    ->orWhere('meta->barcode', $query)
                    ->orWhereHas('product', static function ($product) use ($like): void {
                        $product->where('name', 'like', $like)
                            ->orWhere('slug', 'like', $like);
                    });
            })
            ->orderBy('sku')
            ->limit($limit)
            ->get();

        return $variants->map(fn (ProductVariant $variant): array => $this->mapVariant($variant))->all();
    }

    /**
     * @param  list<string>  $uuids
     * @return list<array<string, mixed>>
     */
    public function productsByUuids(array $uuids): array
    {
        $uuids = array_values(array_unique(array_filter(
            $uuids,
            static fn (mixed $uuid): bool => is_string($uuid) && $uuid !== '',
        )));

        if ($uuids === []) {
            return [];
        }

        return ProductVariant::query()
            ->with(['product.media'])
            ->whereIn('uuid', $uuids)
            ->get()
            ->map(fn (ProductVariant $variant): array => $this->mapVariant($variant))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCustomer(Customer $customer): array
    {
        $address = $customer->defaultShippingAddress();

        return [
            'uuid' => $customer->uuid,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'avatar_url' => $this->customerAvatarUrl($customer),
            'address' => $address === null ? null : [
                'recipient_name' => $customer->name,
                'phone' => $customer->phone,
                'line1' => $address->line1,
                'line2' => $address->line2,
                'district' => $address->city,
                'subdistrict' => is_array($address->meta) ? ($address->meta['subdistrict'] ?? null) : null,
                'province' => $address->state,
                'postal_code' => $address->postal_code,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapVariant(ProductVariant $variant): array
    {
        $available = 0;

        try {
            $available = $this->inventory->getAvailable($variant->uuid);
        } catch (\Throwable) {
            $available = 0;
        }

        $status = 'out_of_stock';
        if ($available > 5) {
            $status = 'in_stock';
        } elseif ($available > 0) {
            $status = 'low_stock';
        }

        return [
            'purchasable_uuid' => $variant->uuid,
            'product_name' => $variant->product?->name ?? $variant->name ?? 'Product',
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'price' => (int) $variant->price,
            'available' => $available,
            'stock_status' => $status,
            'image_url' => $this->imageUrl($variant),
        ];
    }

    private function customerAvatarUrl(Customer $customer): ?string
    {
        $meta = is_array($customer->meta) ? $customer->meta : [];
        $direct = $meta['avatar_url'] ?? null;

        if (is_string($direct) && $direct !== '') {
            return $direct;
        }

        $mediaUuid = $meta['avatar_media_uuid'] ?? $meta['image_media_uuid'] ?? null;

        if (! is_string($mediaUuid) || $mediaUuid === '' || ! app()->bound(MediaQueryServiceInterface::class)) {
            return null;
        }

        try {
            return app(MediaQueryServiceInterface::class)->getUrl($mediaUuid, 'thumbnail')
                ?? app(MediaQueryServiceInterface::class)->getUrl($mediaUuid);
        } catch (\Throwable) {
            return null;
        }
    }

    private function imageUrl(ProductVariant $variant): ?string
    {
        $mediaUuid = $variant->product?->media?->first()?->media_uuid;

        if (! is_string($mediaUuid) || $mediaUuid === '' || ! app()->bound(MediaQueryServiceInterface::class)) {
            return null;
        }

        try {
            return app(MediaQueryServiceInterface::class)->getUrl($mediaUuid, 'thumbnail')
                ?? app(MediaQueryServiceInterface::class)->getUrl($mediaUuid);
        } catch (\Throwable) {
            return null;
        }
    }
}
