<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Contracts\Settings\SiteIdentityServiceInterface;
use Commerce\Marketplace\Models\Seller;
use Commerce\Product\Models\Product;
use Illuminate\Support\Facades\Schema;

final class BarcodeOwnerResolver
{
    public function __construct(
        private readonly SiteIdentityServiceInterface $siteIdentity,
    ) {}

    public function resolve(?Product $product): string
    {
        if ($product?->seller_uuid) {
            return $this->resolveForSeller((string) $product->seller_uuid);
        }

        return $this->siteIdentity->name();
    }

    public function resolveForSeller(?string $sellerUuid): string
    {
        if ($sellerUuid && Schema::hasTable('marketplace_sellers')) {
            $sellerName = Seller::query()
                ->where('uuid', $sellerUuid)
                ->value('name');

            if (is_string($sellerName) && $sellerName !== '') {
                return $sellerName;
            }
        }

        return $this->siteIdentity->name();
    }
}
