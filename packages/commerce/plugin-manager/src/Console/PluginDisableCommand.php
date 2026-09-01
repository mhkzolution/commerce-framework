<?php

declare(strict_types=1);

namespace Commerce\PluginManager\Console;

use Commerce\PluginManager\PluginStateStore;
use Illuminate\Console\Command;

final class PluginDisableCommand extends Command
{
    protected $signature = 'commerce:plugin:disable {alias : Plugin alias from plugin.json}';

    protected $description = 'Disable a plugin';

    public function handle(PluginStateStore $store): int
    {
        $alias = (string) $this->argument('alias');
        $store->set($alias, false);
        $this->info("Plugin [{$alias}] disabled. Restart the application to apply.");

        return self::SUCCESS;
    }
}
