<?php

declare(strict_types=1);

namespace Commerce\ModuleManager\Commands;

use Commerce\ModuleManager\ModuleRegistry;
use Illuminate\Console\Command;

final class ModuleListCommand extends Command
{
    protected $signature = 'commerce:modules';

    protected $description = 'List registered Commerce Framework modules';

    public function handle(ModuleRegistry $registry): int
    {
        $rows = [];

        foreach ($registry->all() as $alias => $manifest) {
            $rows[] = [
                $alias,
                $manifest['name'] ?? $alias,
                $manifest['version'] ?? 'n/a',
                $registry->isEnabled($alias) ? 'enabled' : 'disabled',
                (string) ($manifest['priority'] ?? 100),
            ];
        }

        $this->table(['Alias', 'Name', 'Version', 'Status', 'Priority'], $rows);

        return self::SUCCESS;
    }
}
