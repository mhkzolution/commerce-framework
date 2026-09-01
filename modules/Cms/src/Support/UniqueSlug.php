<?php

declare(strict_types=1);

namespace Commerce\Cms\Support;

final class UniqueSlug
{
    /**
     * @param  callable(string): bool  $taken
     */
    public static function allocate(string $candidate, callable $taken): string
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            $candidate = 'item';
        }

        if (! $taken($candidate)) {
            return $candidate;
        }

        $n = 2;
        while ($taken($candidate.'-'.$n)) {
            $n++;
        }

        return $candidate.'-'.$n;
    }
}
