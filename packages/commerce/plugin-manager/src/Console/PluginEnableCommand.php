<?php

declare(strict_types=1);

namespace Commerce\PluginManager\Console;

use Commerce\PluginManager\PluginLoader;
use Commerce\PluginManager\PluginStateStore;
use Illuminate\Console\Command;

final class PluginEnableCommand extends Command
{
    protected $signature = 'commerce:plugin:enable {alias : Plugin alias from plugin.json}';

    protected $description = 'Enable a plugin';

    public function handle(PluginLoader $loader, PluginStateStore $store): int
    {
        $alias = (string) $this->argument('alias');

        if (! $this->pluginExists($loader, $alias)) {
            $this->error("Plugin [{$alias}] was not found.");

            return self::FAILURE;
        }

        $store->set($alias, true);
        $this->info("Plugin [{$alias}] enabled. Restart the application to apply.");

        return self::SUCCESS;
    }

    private function pluginExists(PluginLoader $loader, string $alias): bool
    {
        foreach ($loader->discover() as $manifest) {
            if (($manifest['alias'] ?? null) === $alias) {
                return true;
            }
        }

        return false;
    }
}
