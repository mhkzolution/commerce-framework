<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Barcode\DTO\BarcodeSearchResult;
use Commerce\Barcode\Support\BarcodeSkuNormalizer;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;

final class BarcodeProductSearchService
{
    public function __construct(
        private readonly BarcodeOwnerResolver $ownerResolver,
        private readonly BarcodeImageResolver $imageResolver,
    ) {}

    /**
     * @return list<BarcodeSearchResult>
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

        return $variants
            ->map(fn (ProductVariant $variant) => $this->toResult($variant))
            ->all();
    }

    public function findBySku(string $sku): ?BarcodeSearchResult
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

    private function toResult(ProductVariant $variant): BarcodeSearchResult
    {
        $product = $variant->product;
        $sku = (string) ($variant->sku ?? '');

        return new BarcodeSearchResult(
            productUuid: $product?->uuid,
            variantUuid: $variant->uuid,
            sku: $sku,
            productName: (string) ($product?->name ?? ''),
            variantName: (string) ($variant->name ?? ''),
            ownerName: $this->ownerResolver->resolve($product),
            thumbnailUrl: $this->imageResolver->resolve($this->primaryMediaUuid($product)),
        );
    }

    private function primaryMediaUuid(?Product $product): ?string
    {
        if ($product === null) {
            return null;
        }

        $media = $product->media;
        $primary = $media->firstWhere('is_primary', true) ?? $media->first();

        return $primary instanceof ProductMedia ? $primary->media_uuid : null;
    }
}
