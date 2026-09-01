<?php

declare(strict_types=1);

namespace Commerce\Contracts\Wishlist;

interface WishlistServiceInterface
{
    /**
     * @param  list<array{product_id: string, variant_id?: string|null}>  $items
     */
    public function mergeForCustomer(string $customerUuid, array $items): int;
}
