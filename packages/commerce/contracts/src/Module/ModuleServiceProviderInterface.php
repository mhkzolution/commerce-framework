<?php

declare(strict_types=1);

namespace Commerce\Contracts\Module;

interface ModuleServiceProviderInterface
{
    public function getModule(): ModuleInterface;
}
