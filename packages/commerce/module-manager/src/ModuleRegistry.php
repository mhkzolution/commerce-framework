<?php

declare(strict_types=1);

namespace Commerce\ModuleManager;

final class ModuleRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $modules = [];

    public function register(string $alias, array $manifest): void
    {
        $this->modules[$alias] = $manifest;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->modules;
    }

    public function get(string $alias): ?array
    {
        return $this->modules[$alias] ?? null;
    }

    public function isEnabled(string $alias): bool
    {
        return (bool) config("commerce.modules.{$alias}", false);
    }
}
