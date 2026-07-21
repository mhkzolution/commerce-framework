<?php

declare(strict_types=1);

namespace Commerce\Core\Base;

use Illuminate\Support\ServiceProvider;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    abstract public function getModuleAlias(): string;

    protected function modulePath(string $path = ''): string
    {
        $base = $this->getModuleRoot();

        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . $path;
    }

    protected function getModuleRoot(): string
    {
        return dirname((new \ReflectionClass(static::class))->getFileName(), 2);
    }
}
