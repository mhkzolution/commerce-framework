<?php

declare(strict_types=1);

namespace Commerce\PluginManager\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class PluginMakeCommand extends Command
{
    protected $signature = 'commerce:plugin:make {name : Plugin name in StudlyCase}';

    protected $description = 'Scaffold a new plugin in plugins/';

    public function handle(Filesystem $files): int
    {
        $name = (string) $this->argument('name');
        $studly = Str::studly($name);
        $alias = Str::kebab($studly);
        $namespace = 'Plugins\\'.$studly;
        $base = base_path("plugins/{$alias}");

        if ($files->isDirectory($base)) {
            $this->error("Plugin directory already exists: plugins/{$alias}");

            return self::FAILURE;
        }

        $files->makeDirectory("{$base}/src", 0755, true);

        $files->put("{$base}/plugin.json", json_encode([
            'name' => Str::headline($studly),
            'alias' => $alias,
            'version' => '1.0.0',
            'description' => "{$studly} plugin",
            'providers' => ["{$namespace}\\{$studly}ServiceProvider"],
            'bindings' => [],
            'hooks' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $files->put("{$base}/src/{$studly}ServiceProvider.php", <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\Support\ServiceProvider;

final class {$studly}ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }
}

PHP);

        $this->info("Plugin scaffolded at plugins/{$alias}");
        $this->line("Enable with: php artisan commerce:plugin:enable {$alias}");

        return self::SUCCESS;
    }
}
