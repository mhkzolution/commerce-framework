<?php

declare(strict_types=1);

namespace Commerce\Contracts\Hook;

interface HookRegistryInterface
{
  public function register(string $hook, callable $callback, int $priority = 10): void;

    /**
     * @param  array<string, mixed>  $context
     */
    public function execute(string $hook, array $context = []): void;

    /**
     * @param  mixed  $value
     * @param  array<string, mixed>  $context
     * @return mixed
     */
    public function filter(string $hook, mixed $value, array $context = []): mixed;
}
