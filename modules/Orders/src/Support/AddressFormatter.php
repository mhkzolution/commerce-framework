<?php

declare(strict_types=1);

namespace Commerce\Orders\Support;

final class AddressFormatter
{
    /**
     * @param  array<string, mixed>|null  $address
     * @return list<string>
     */
    public static function lines(?array $address): array
    {
        if ($address === null || $address === []) {
            return [];
        }

        $rows = [
            $address['recipient_name'] ?? null,
            $address['line1'] ?? null,
            $address['line2'] ?? null,
            implode(' ', array_filter([
                $address['subdistrict'] ?? null,
                $address['district'] ?? $address['city'] ?? null,
            ], static fn (mixed $value): bool => is_string($value) && $value !== '')),
            implode(' ', array_filter([
                $address['province'] ?? $address['state'] ?? null,
                $address['postal_code'] ?? null,
            ], static fn (mixed $value): bool => is_string($value) && $value !== '')),
            $address['phone'] ?? null,
        ];

        $lines = [];
        foreach ($rows as $row) {
            if (is_string($row) && trim($row) !== '') {
                $lines[] = trim($row);
            }
        }

        return $lines;
    }
}
