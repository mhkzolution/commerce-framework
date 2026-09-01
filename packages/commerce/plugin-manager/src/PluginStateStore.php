<?php

declare(strict_types=1);

namespace Commerce\PluginManager;

use Illuminate\Filesystem\Filesystem;

final class PluginStateStore
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * @return array<string, bool>
     */
    public function all(): array
    {
        $path = $this->path();

        if (! $this->files->exists($path)) {
            return [];
        }

        /** @var array<string, bool> $state */
        $state = require $path;

        return $state;
    }

    public function set(string $alias, bool $enabled): void
    {
        $state = array_merge(config('commerce.plugins', []), $this->all());
        $state[$alias] = $enabled;

        $this->files->ensureDirectoryExists(dirname($this->path()));
        $export = var_export($state, true);
        $this->files->put($this->path(), "<?php\n\ndeclare(strict_types=1);\n\nreturn {$export};\n");
    }

    public function path(): string
    {
        return storage_path('framework/commerce-plugins.php');
    }
}
