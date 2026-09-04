<?php

declare(strict_types=1);

namespace Commerce\Product\Import;

use RuntimeException;

final class WooCommerceCsvReader
{
    /**
     * @return \Generator<int, array<string, string>>
     */
    public function read(string $path): \Generator
    {
        if (! is_readable($path)) {
            throw new RuntimeException("CSV file is not readable: {$path}");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Could not open CSV file: {$path}");
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false || $headers === [null]) {
                return;
            }

            $headers = array_map(static fn (?string $header): string => trim((string) $header), $headers);

            if (isset($headers[0])) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) ?? $headers[0];
            }

            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === false) {
                    continue;
                }

                if ($this->isBlankRow($row)) {
                    continue;
                }

                $assoc = [];

                foreach ($headers as $index => $header) {
                    if ($header === '') {
                        continue;
                    }

                    $assoc[$header] = trim((string) ($row[$index] ?? ''));
                }

                if (! $this->isProductRow($assoc)) {
                    continue;
                }

                yield $assoc;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function isProductRow(array $row): bool
    {
        return ($row['ID'] ?? '') !== '' || ($row['SKU'] ?? '') !== '' || ($row['Name'] ?? '') !== '';
    }
}
