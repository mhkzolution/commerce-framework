<?php

declare(strict_types=1);

namespace Commerce\Catalog\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class SlugGenerator
{
    public static function unique(string $value, Builder $query, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $counter = 1;

        while (self::exists($query, $slug, $ignoreId)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private static function exists(Builder $query, string $slug, ?int $ignoreId): bool
    {
        return $query->clone()
            ->where('slug', $slug)
            ->when($ignoreId !== null, static fn (Builder $builder) => $builder->where('id', '!=', $ignoreId))
            ->exists();
    }
}
