<?php

declare(strict_types=1);

namespace Commerce\Contracts\Plugin;

interface PluginInterface
{
    public function getName(): string;

    public function getAlias(): string;

    public function getVersion(): string;

    /**
     * @return list<string>
     */
    public function getRequiredModules(): array;

    /**
     * @return array<string, string>
     */
    public function getBindings(): array;

    /**
     * @return list<string>
     */
    public function getHooks(): array;
}
