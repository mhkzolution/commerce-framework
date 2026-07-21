<?php

declare(strict_types=1);

namespace Commerce\Contracts\Hook;

interface HookableInterface
{
    public function registerHooks(\Commerce\Contracts\Hook\HookRegistryInterface $hooks): void;
}
