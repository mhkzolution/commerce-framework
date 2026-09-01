<?php

declare(strict_types=1);

namespace Commerce\Api\Support;

use Illuminate\Http\Request;

final class ApiInclude
{
    /**
     * @param  list<string>  $allowed
     * @return list<string>
     */
    public static function parse(Request $request, array $allowed): array
    {
        $value = trim($request->string('include')->toString());

        if ($value === '') {
            return [];
        }

        $requested = array_values(array_filter(array_map('trim', explode(',', $value))));

        return array_values(array_intersect($requested, $allowed));
    }

    /**
     * @param  array<string, string|list<string>>  $map
     * @return list<string>
     */
    public static function relations(Request $request, array $map): array
    {
        $includes = self::parse($request, array_keys($map));
        $relations = [];

        foreach ($includes as $include) {
            $mapped = $map[$include] ?? null;

            if ($mapped === null) {
                continue;
            }

            foreach ((array) $mapped as $relation) {
                $relations[] = $relation;
            }
        }

        return array_values(array_unique($relations));
    }
}
