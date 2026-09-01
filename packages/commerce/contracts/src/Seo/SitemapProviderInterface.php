<?php

declare(strict_types=1);

namespace Commerce\Contracts\Seo;

interface SitemapProviderInterface
{
    /**
     * @return list<array{loc: string, lastmod?: ?string, changefreq?: string, priority?: string}>
     */
    public function urls(): array;
}
