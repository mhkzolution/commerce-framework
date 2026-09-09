<?php

declare(strict_types=1);

namespace Commerce\Product\Import;

use RuntimeException;

final class ProductImageMappingReader
{
    /**
     * @return array<string, list<string>>
     */
    public function read(string $path): array
    {
        if (! is_readable($path)) {
            throw new RuntimeException('Mapping file is not readable: '.$path);
        }

        $contents = (string) file_get_contents($path);
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'json') {
            return $this->fromJson($contents);
        }

        return $this->fromMarkdown($contents);
    }

    /**
     * @return array<string, list<string>>
     */
    private function fromJson(string $contents): array
    {
        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Mapping JSON is invalid.');
        }

        $mapping = [];

        foreach ($decoded as $sku => $paths) {
            $sku = is_int($sku) || is_string($sku) ? (string) $sku : '';

            if ($sku === '' || ! is_array($paths)) {
                continue;
            }

            $mapping[$sku] = $this->uniquePaths(array_values(array_filter(
                $paths,
                static fn (mixed $path): bool => is_string($path) && trim($path) !== '',
            )));
        }

        return $mapping;
    }

    /**
     * @return array<string, list<string>>
     */
    private function fromMarkdown(string $contents): array
    {
        $mapping = [];

        foreach (preg_split("/\r\n|\r|\n/", $contents) ?: [] as $line) {
            if (! str_contains($line, '|')) {
                continue;
            }

            $cells = array_map(trim(...), explode('|', $line));
            $cells = array_values(array_filter($cells, static fn (string $cell): bool => $cell !== ''));

            if (count($cells) < 3) {
                continue;
            }

            if (! preg_match('/^`([^`]+)`$/', $cells[0], $skuMatch)) {
                continue;
            }

            $sku = $skuMatch[1];

            if ($sku === 'SKU') {
                continue;
            }

            $filesCell = $cells[count($cells) - 1];
            $paths = [];

            foreach (preg_split('/<br\s*\/?>/i', $filesCell) ?: [] as $chunk) {
                if (preg_match_all('/`([^`]+)`/', $chunk, $matches) > 0) {
                    foreach ($matches[1] as $path) {
                        $paths[] = $path;
                    }

                    continue;
                }

                $plain = trim(html_entity_decode($chunk, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($plain !== '' && $plain !== '---' && $plain !== ':---') {
                    $paths[] = $plain;
                }
            }

            $mapping[(string) $sku] = $this->uniquePaths($paths);
        }

        return $mapping;
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function uniquePaths(array $paths): array
    {
        $unique = [];

        foreach ($paths as $path) {
            $normalized = $this->normalizePath((string) $path);

            if ($normalized === '' || in_array($normalized, $unique, true)) {
                continue;
            }

            $unique[] = $normalized;
        }

        return $unique;
    }

    public function normalizePath(string $path): string
    {
        $relative = str_replace('\\', '/', trim($path));
        $relative = ltrim($relative, '/');

        if (str_starts_with($relative, 'uploads/')) {
            return substr($relative, strlen('uploads/'));
        }

        return $relative;
    }
}
