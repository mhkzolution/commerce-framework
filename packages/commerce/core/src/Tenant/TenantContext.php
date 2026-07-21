<?php

declare(strict_types=1);

namespace Commerce\Core\Tenant;

use Commerce\Core\Models\Tenant;

final class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function isEnabled(): bool
    {
        return (bool) config('commerce.tenant.enabled', false);
    }

    public function clear(): void
    {
        $this->tenant = null;
    }
}
