<?php

declare(strict_types=1);

namespace Commerce\Product\Support;

use Commerce\Marketplace\Models\Seller;
use Commerce\Product\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class ProductCsvSellerResolver
{
    /** @var array<string, string|null> */
    private array $uuidCache = [];

    public function isAvailable(): bool
    {
        return class_exists(Seller::class)
            && Schema::hasTable('marketplace_sellers');
    }

    public function resolveUuid(?string $raw): ?string
    {
        $raw = trim((string) $raw);

        if ($raw === '' || ! $this->isAvailable()) {
            return null;
        }

        $raw = trim(explode(',', $raw)[0] ?? '');

        if ($raw === '') {
            return null;
        }

        $cacheKey = Str::lower($raw);

        if (array_key_exists($cacheKey, $this->uuidCache)) {
            return $this->uuidCache[$cacheKey];
        }

        $sellerClass = Seller::class;
        $seller = null;

        if (Str::isUuid($raw)) {
            $seller = $sellerClass::query()->where('uuid', $raw)->first();
        }

        if ($seller === null) {
            $seller = $sellerClass::query()->where('name', $raw)->first();
        }

        if ($seller === null) {
            $slug = Str::slug($raw);

            if ($slug !== '') {
                $seller = $sellerClass::query()->where('slug', $slug)->first();
            }
        }

        if ($seller === null) {
            $seller = $this->createSeller($raw);
        }

        $this->uuidCache[$cacheKey] = $seller?->uuid;

        return $seller?->uuid;
    }

    /**
     * @param  iterable<int, Product>  $products
     * @return array<string, string>
     */
    public function namesForProducts(iterable $products): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $uuids = [];

        foreach ($products as $product) {
            if ($product->seller_uuid) {
                $uuids[] = $product->seller_uuid;
            }
        }

        if ($uuids === []) {
            return [];
        }

        return Seller::query()
            ->whereIn('uuid', array_values(array_unique($uuids)))
            ->pluck('name', 'uuid')
            ->all();
    }

    private function createSeller(string $name): ?object
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        $sellerClass = Seller::class;

        return $sellerClass::query()->create([
            'name' => $name,
            'slug' => Str::slug($name),
            'commission_rate' => 0,
            'status' => 'active',
        ]);
    }
}
