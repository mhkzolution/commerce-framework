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
        $product->loadMissing(['variants', 'categories']);

        $sku = $product->defaultVariant()?->sku;
        $description = strip_tags((string) $product->description);

        $this->searchIndex->index(self::INDEX, $product->uuid, [
            'uuid' => $product->uuid,
            'title' => $product->name,
            'body' => trim($description . ' ' . ($sku ?? '')),
            'slug' => $product->slug,
            'status' => $product->status,
            'sku' => $product->defaultVariant()?->sku,
            'category' => $product->categories->first()?->name,
        ]);
    }

    public function delete(string $productUuid): void
    {
        $this->searchIndex->delete(self::INDEX, $productUuid);
    }

    public function reindexAll(): int
    {
        $count = 0;

        Product::query()->with(['variants', 'categories'])->chunkById(100, function ($products) use (&$count): void {
            foreach ($products as $product) {
                $this->index($product);
                $count++;
            }
        });

        return $count;
    }
}
