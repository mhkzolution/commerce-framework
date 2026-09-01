<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Resources\Concerns;

use Commerce\Catalog\Support\CatalogSeoSync;

trait WithCatalogSeo
{
    /**
     * @return array<string, mixed>
     */
    protected function catalogSeoMeta(string $entityType, string $fallbackTitle, ?string $fallbackDescription): array
    {
        return app(CatalogSeoSync::class)->pageMeta(
            $entityType,
            (string) $this->uuid,
            $fallbackTitle,
            $fallbackDescription,
        );
    }
}
