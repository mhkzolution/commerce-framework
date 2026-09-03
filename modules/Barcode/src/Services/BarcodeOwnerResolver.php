<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Marketplace\Models\Seller;
use Commerce\Product\Models\Product;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class BarcodeOwnerResolver
{
    public function __construct(
        private readonly SettingQueryServiceInterface $settings,
    ) {}

    public function resolve(?Product $product): string
    {
        return $this->resolveForSeller($product?->seller_uuid);
    }

    public function resolveForSeller(?string $sellerUuid): string
    {
        try {
            if ($sellerUuid && class_exists(Seller::class) && Schema::hasTable('marketplace_sellers')) {
                $sellerName = Seller::query()
                    ->where('uuid', $sellerUuid)
                    ->value('name');

                if (is_string($sellerName) && $sellerName !== '') {
                    return $sellerName;
                }
            }
        } catch (Throwable) {
        }

        try {
            $storeName = $this->settings->get('store.name');
            if (is_string($storeName) && trim($storeName) !== '') {
                return trim($storeName);
            }
        } catch (Throwable) {
        }

        $appName = config('app.name');
        if (is_string($appName) && trim($appName) !== '') {
            return trim($appName);
        }

        return 'Store';
    }
}
