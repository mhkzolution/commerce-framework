<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Barcode\Support\BarcodeSkuNormalizer;
use Commerce\Product\Models\ProductVariant;
use Commerce\Product\Services\ProductImageResolver;

final class BarcodeProductSearchService
{
    public function __construct(
        private readonly BarcodeOwnerResolver $ownerResolver,
        private readonly ProductImageResolver $imageResolver,
    ) {}

    /**
     * @return list<array{
     *     variant_uuid: string,
     *     thumbnail_url: string|null,
     *     owner_name: string,
     *     product_name: string,
     *     variant_name: string,
     *     sku: string
     * }>
     */
    public function search(string $query, int $limit = 20): array
    {
        $query = BarcodeSkuNormalizer::normalize($query);

        if ($query === '') {
            return [];
        }

        $variants = ProductVariant::query()
            ->with(['product.media'])
            ->where(function ($inner) use ($query): void {
                $inner->where('sku', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%")
                    ->orWhereHas('product', static function ($product) use ($query): void {
                        $product->where('name', 'like', "%{$query}%")
                            ->orWhere('slug', 'like', "%{$query}%");
                    });
            })
            ->orderBy('sku')
            ->limit($limit)
            ->get();

        $this->imageResolver->preloadForProducts($variants->pluck('product')->filter());

        return $variants
            ->map(fn (ProductVariant $variant) => $this->toResult($variant))
            ->all();
    }

    /**
     * @return array{
     *     variant_uuid: string,
     *     thumbnail_url: string|null,
     *     owner_name: string,
     *     product_name: string,
     *     variant_name: string,
     *     sku: string
     * }|null
     */
    public function findBySku(string $sku): ?array
    {
        $sku = BarcodeSkuNormalizer::normalize($sku);

        if ($sku === '') {
            return null;
        }

        $variant = ProductVariant::query()
            ->with(['product.media'])
            ->where('sku', $sku)
            ->first();

        if (! $variant) {
            $variant = ProductVariant::query()
                ->with(['product.media'])
                ->whereRaw('LOWER(sku) = ?', [strtolower($sku)])
                ->first();
        }

        if (! $variant) {
            return null;
        }

        return $this->toResult($variant);
    }

    /**
     * @return array{
     *     variant_uuid: string,
     *     thumbnail_url: string|null,
     *     owner_name: string,
     *     product_name: string,
     *     variant_name: string,
     *     sku: string
     * }
     */
    private function toResult(ProductVariant $variant): array
    {
        $product = $variant->product;
        $sku = (string) ($variant->sku ?? '');

        return [
            'variant_uuid' => $variant->uuid,
            'thumbnail_url' => $product ? $this->imageResolver->urlForProduct($product) : null,
            'owner_name' => $this->ownerResolver->resolve($product),
            'product_name' => (string) ($product?->name ?? ''),
            'variant_name' => (string) ($variant->name ?? ''),
            'sku' => $sku,
        ];
    }
}
