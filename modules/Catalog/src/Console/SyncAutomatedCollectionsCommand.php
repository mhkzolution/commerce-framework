<?php

declare(strict_types=1);

namespace Commerce\Catalog\Console;

use Commerce\Catalog\Services\CollectionAutomatedSyncService;
use Illuminate\Console\Command;

final class SyncAutomatedCollectionsCommand extends Command
{
    protected $signature = 'catalog:sync-automated-collections';

    protected $description = 'Sync all automated collections with their matching products';

    public function handle(CollectionAutomatedSyncService $syncService): int
    {
        $syncService->syncAllAutomated();

        $this->info('Automated collections synced.');

        return self::SUCCESS;
    }
}
