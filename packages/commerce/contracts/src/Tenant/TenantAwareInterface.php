<?php

declare(strict_types=1);

namespace Commerce\Contracts\Tenant;

interface TenantAwareInterface
{
    public function getTenantId(): ?int;

    public function setTenantId(?int $tenantId): void;
}
