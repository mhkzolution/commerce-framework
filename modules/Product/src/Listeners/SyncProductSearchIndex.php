<?php

declare(strict_types=1);

namespace Commerce\Product\Listeners;

use Commerce\Contracts\Event\DomainEventInterface;
use Commerce\Product\Events\ProductCreated;
use Commerce\Product\Events\ProductPublished;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductSearchIndexer;

final class SyncProductSearchIndex
{
    public function __construct(
        private readonly ProductSearchIndexer $indexer,
    ) {}

    public function handle(DomainEventInterface $event): void
    {
        if (! $event instanceof ProductCreated && ! $event instanceof ProductPublished) {
            return;
        }

        $product = Product::query()->where('uuid', $event->productUuid)->first();

        if ($product === null) {
            return;
        }

        $this->indexer->index($product);
    }
}
