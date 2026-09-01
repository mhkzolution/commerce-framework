<?php

declare(strict_types=1);

namespace Commerce\Core\Seo;

use Commerce\Contracts\Seo\SitemapProviderInterface;

final class SitemapGenerator
{
    /**
     * @param  iterable<object>  $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {}

    public function toXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($this->providers as $provider) {
            if (! $provider instanceof SitemapProviderInterface) {
                continue;
            }

            foreach ($provider->urls() as $entry) {
                $loc = trim((string) ($entry['loc'] ?? ''));
                if ($loc === '') {
                    continue;
                }

                $xml .= '<url>';
                $xml .= '<loc>'.$this->escape($loc).'</loc>';

                if (! empty($entry['lastmod'])) {
                    $xml .= '<lastmod>'.$this->escape((string) $entry['lastmod']).'</lastmod>';
                }

                if (! empty($entry['changefreq'])) {
                    $xml .= '<changefreq>'.$this->escape((string) $entry['changefreq']).'</changefreq>';
                }

                if (! empty($entry['priority'])) {
                    $xml .= '<priority>'.$this->escape((string) $entry['priority']).'</priority>';
                }

                $xml .= '</url>';
            }
        }

        $xml .= '</urlset>';

        return $xml;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
