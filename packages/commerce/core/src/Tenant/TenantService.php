<?php

declare(strict_types=1);

namespace Commerce\Core\Tenant;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Core\Models\Tenant;
use Illuminate\Support\Str;

final class TenantService extends BaseService
{
    public function __construct(private readonly TenantContext $context) {}

    public function create(string $name, ?string $slug = null, ?string $domain = null): Tenant
    {
        $slug ??= Str::slug($name);

        return Tenant::query()->create([
            'name' => $name,
            'slug' => $slug,
            'domain' => $domain,
            'status' => 'active',
        ]);
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return Tenant::query()->where('slug', $slug)->first();
    }

    public function findByDomain(string $domain): ?Tenant
    {
        return Tenant::query()->where('domain', $domain)->first();
    }

    public function resolveFromRequest(?string $header = null, ?string $host = null): ?Tenant
    {
        if (! $this->context->isEnabled()) {
            return null;
        }

        if ($header !== null && $header !== '') {
            $tenant = $this->findBySlug($header) ?? Tenant::query()->where('uuid', $header)->first();

            if ($tenant !== null) {
                return $tenant->isActive() ? $tenant : null;
            }
        }

        if ($host !== null && $host !== '') {
            $tenant = $this->findByDomain($host);

            if ($tenant !== null) {
                return $tenant->isActive() ? $tenant : null;
            }
        }

        return null;
    }

    public function setCurrent(Tenant $tenant): void
    {
        if (! $tenant->isActive()) {
            throw new DomainException('Tenant is not active.');
        }

        $this->context->set($tenant);
    }

    public function findOrFail(string $uuid): Tenant
    {
        $tenant = Tenant::query()->where('uuid', $uuid)->first();

        if ($tenant === null) {
            throw new EntityNotFoundException("Tenant [{$uuid}] not found.");
        }

        return $tenant;
    }
}
