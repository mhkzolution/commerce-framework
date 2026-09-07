<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

final class VariantIdentity
{
    /**
     * @param  list<int|string>  $valueIds
     */
    public static function key(array $valueIds): string
    {
        $ids = array_map('intval', $valueIds);
        sort($ids);

        return implode('-', $ids);
    }
}
