<?php

declare(strict_types=1);

namespace Commerce\Product\Console;

use Commerce\Product\Contracts\ProductServiceInterface;
use Illuminate\Console\Command;

final class PublishScheduledProductsCommand extends Command
{
    protected $signature = 'product:publish-scheduled';

    protected $description = 'Publish products that have reached their scheduled publish time';

    public function handle(ProductServiceInterface $productService): int
    {
        $count = $productService->publishScheduled();

        $this->info("Published {$count} scheduled product(s).");

        return self::SUCCESS;
    }
}
