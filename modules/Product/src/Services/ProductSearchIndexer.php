<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Contracts\Search\SearchIndexInterface;
use Commerce\Product\Models\Product;

final class ProductSearchIndexer
{
    public const INDEX = 'products';

    public function __construct(
        private readonly SearchIndexInterface $searchIndex,
    ) {}

    public function index(Product $product): void
    {
        $product->loadMissing(['variants', 'categories', 'brand', 'attributeValues.attributeValue']);

        $description = strip_tags((string) $product->description);
        $skus = $product->variants
            ->pluck('sku')
            ->filter(static fn ($sku): bool => is_string($sku) && trim($sku) !== '')
            ->values()
            ->all();
        $attributes = [];

        foreach ($product->attributeValues as $productAttributeValue) {
            $attributeValue = $productAttributeValue->attributeValue;

            if ($attributeValue === null) {
                continue;
            }

            $attributes[$attributeValue->code] = [
                'code' => $attributeValue->code,
                'label' => $attributeValue->label,
            ];
        }

        $this->searchIndex->index(self::INDEX, $product->uuid, [
            'uuid' => $product->uuid,
            'title' => $product->name,
            'body' => trim($description.' '.implode(' ', $skus)),
            'slug' => $product->slug,
            'status' => $product->status,
            'sku' => $product->defaultVariant()?->sku,
            'skus' => $skus,
            'brand_name' => $product->brand?->name,
            'brand_slug' => $product->brand?->slug,
            'category_names' => $product->categories->pluck('name')->values()->all(),
            'attributes' => array_values($attributes),
        ]);
    }

    public function delete(string $productUuid): void
    {
        $this->searchIndex->delete(self::INDEX, $productUuid);
    }

    public function reindexAll(): int
    {
        $count = 0;

        Product::query()
            ->with(['variants', 'categories', 'brand', 'attributeValues.attributeValue'])
            ->chunkById(100, function ($products) use (&$count): void {
                foreach ($products as $product) {
                    $this->index($product);
                    $count++;
                }
            });

        return $count;
    }
}
