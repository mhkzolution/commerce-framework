<?php

declare(strict_types=1);

namespace Commerce\Core\Hooks;

use Commerce\Contracts\Hook\HookRegistryInterface;

final class HookRegistry implements HookRegistryInterface
{
    /** @var array<string, list<array{callable, int}>> */
    private array $actions = [];

    /** @var array<string, list<array{callable, int}>> */
    private array $filters = [];

    public function register(string $hook, callable $callback, int $priority = 10): void
    {
        $this->actions[$hook][] = [$callback, $priority];
        usort($this->actions[$hook], static fn (array $a, array $b): int => $a[1] <=> $b[1]);
    }

    public function execute(string $hook, array $context = []): void
    {
        foreach ($this->actions[$hook] ?? [] as [$callback]) {
            $callback($context);
        }
    }

    public function filter(string $hook, mixed $value, array $context = []): mixed
    {
        foreach ($this->filters[$hook] ?? [] as [$callback]) {
            $value = $callback($value, $context);
        }

        return $value;
    }

    public function registerFilter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->filters[$hook][] = [$callback, $priority];
        usort($this->filters[$hook], static fn (array $a, array $b): int => $a[1] <=> $b[1]);
    }
}
