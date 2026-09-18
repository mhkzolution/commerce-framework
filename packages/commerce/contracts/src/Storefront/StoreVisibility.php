<?php

declare(strict_types=1);

namespace Commerce\Contracts\Storefront;

enum StoreVisibility: string
{
    case Public = 'public';
    case Catalog = 'catalog';
    case Members = 'members';
    case Private = 'private';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
