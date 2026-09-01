<?php

declare(strict_types=1);

namespace Commerce\PluginManager\Console;

use Commerce\PluginManager\PluginLoader;
use Commerce\PluginManager\PluginStateStore;
use Illuminate\Console\Command;

final class PluginListCommand extends Command
{
    protected $signature = 'commerce:plugin:list';

    protected $description = 'List discovered plugins and their enabled state';

    public function handle(PluginLoader $loader, PluginStateStore $store): int
    {
        $state = array_merge(config('commerce.plugins', []), $store->all());
        $rows = [];

        foreach ($loader->discover() as $manifest) {
            $alias = (string) ($manifest['alias'] ?? '');
            $rows[] = [
                $alias,
                $manifest['name'] ?? $alias,
                ($state[$alias] ?? false) ? 'enabled' : 'disabled',
                $manifest['version'] ?? '—',
            ];
        }

        if ($rows === []) {
            $this->info('No plugins discovered in plugins/.');

            return self::SUCCESS;
        }

        $this->table(['Alias', 'Name', 'Status', 'Version'], $rows);

        return self::SUCCESS;
    }
}
