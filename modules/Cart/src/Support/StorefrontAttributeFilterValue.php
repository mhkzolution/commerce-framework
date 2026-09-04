<?php

declare(strict_types=1);

namespace Commerce\Cart\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class StorefrontAttributeFilterValue
{
    /**
     * @return list<string>
     */
    public static function parts(string $stored): array
    {
        $stored = trim($stored);

        if ($stored === '') {
            return [];
        }

        if (str_starts_with($stored, '[')) {
            $decoded = json_decode($stored, true);

            if (is_array($decoded)) {
                return array_values(array_filter(array_map(
                    static fn (mixed $part): string => trim((string) $part),
                    $decoded,
                ), static fn (string $part): bool => $part !== ''));
            }
        }

        $parts = [];

        foreach (preg_split('/\s*,\s*/', $stored) ?: [] as $part) {
            $part = trim($part);

            if ($part !== '') {
                $parts[] = $part;
            }
        }

        return $parts;
    }

    /**
     * @param  Builder<Model>  $query
     */
    public static function applyStoredMatch(Builder $query, string $column, string $value): void
    {
        $value = trim($value);

        if ($value === '') {
            return;
        }

        $query->where(function (Builder $matchQuery) use ($column, $value): void {
            $matchQuery->where($column, $value);

            $encoded = json_encode([$value], JSON_UNESCAPED_UNICODE);

            if (is_string($encoded)) {
                $matchQuery->orWhere($column, $encoded);
            }

            $escaped = str_replace(['%', '_', '"', '\\'], ['\%', '\_', '\"', '\\\\'], $value);
            $matchQuery->orWhere($column, 'like', '%"'.$escaped.'"%');
        });
    }
}
