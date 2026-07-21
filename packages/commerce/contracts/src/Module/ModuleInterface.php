<?php

declare(strict_types=1);

namespace Commerce\Contracts\Module;

interface ModuleInterface
{
    public function getName(): string;

    public function getAlias(): string;

    public function getVersion(): string;

    public function getPriority(): int;

    /**
     * @return list<string>
     */
    public function getDependencies(): array;

    /**
     * @return list<string>
     */
    public function getSoftDependencies(): array;
}
