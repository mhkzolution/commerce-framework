<?php

declare(strict_types=1);

namespace Commerce\PluginManager;

use Illuminate\Filesystem\Filesystem;

final class PluginLoader
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function discover(): array
    {
        $path = base_path('plugins');
        $manifests = [];

        if (! $this->files->isDirectory($path)) {
            return [];
        }

        foreach ($this->files->directories($path) as $directory) {
            $manifestFile = $directory . '/plugin.json';

            if ($this->files->exists($manifestFile)) {
                $manifests[] = json_decode($this->files->get($manifestFile), true, 512, JSON_THROW_ON_ERROR);
            }
        }

        return $manifests;
    }
}
