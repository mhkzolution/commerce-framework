<?php

declare(strict_types=1);

namespace Commerce\Product\Console;

use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Console\Command;

final class ReindexProductsCommand extends Command
{
    protected $signature = 'product:reindex';

    protected $description = 'Rebuild the product search index';

    public function handle(ProductSearchIndexer $indexer): int
    {
        $count = $indexer->reindexAll();

        $this->info("Indexed {$count} products.");

        return self::SUCCESS;
    }
}
