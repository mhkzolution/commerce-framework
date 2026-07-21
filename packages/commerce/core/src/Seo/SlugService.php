<?php

declare(strict_types=1);

namespace Commerce\Core\Seo;

use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Core\Models\SlugEntry;
use Illuminate\Support\Str;

final class SlugService implements SlugServiceInterface
{
    public function generate(string $source, string $entityType, ?string $tenantScope = null): string
    {
        $base = Str::slug($source);
        $slug = $base;
        $counter = 1;

        while (! $this->isAvailable($slug, $entityType, $tenantScope)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function isAvailable(string $slug, string $entityType, ?string $tenantScope = null): bool
    {
        return ! SlugEntry::query()
            ->where('entity_type', $entityType)
            ->where('slug', $slug)
            ->when($tenantScope !== null, static fn ($query) => $query->where('tenant_id', $tenantScope))
            ->when($tenantScope === null, static fn ($query) => $query->whereNull('tenant_id'))
            ->exists();
    }

    public function register(string $slug, string $entityType, string $entityUuid, ?int $tenantId = null): void
    {
        SlugEntry::query()->updateOrCreate(
            ['entity_type' => $entityType, 'entity_uuid' => $entityUuid],
            ['slug' => $slug, 'tenant_id' => $tenantId],
        );
    }

    public function unregister(string $entityType, string $entityUuid): void
    {
        SlugEntry::query()
            ->where('entity_type', $entityType)
            ->where('entity_uuid', $entityUuid)
            ->delete();
    }
}
