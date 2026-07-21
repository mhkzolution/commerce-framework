<?php

declare(strict_types=1);

namespace Commerce\Contracts\Identifiable;

interface IdentifiableInterface
{
    public function getUuid(): string;

    public function getTenantId(): ?int;
}
