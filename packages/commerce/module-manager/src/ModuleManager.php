<?php

declare(strict_types=1);

namespace Commerce\ModuleManager;

final class ModuleManager
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleDependencyResolver $resolver,
        private readonly ModuleActivator $activator,
    ) {}

    public function boot(): void
    {
        $modules = $this->resolver->resolve($this->registry->all());
        $this->activator->boot($modules);
    }

    public function registry(): ModuleRegistry
    {
        return $this->registry;
    }
}
