<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\Models\ProductVariant;

trait CreatesPurchasableProduct
{
    protected function createPurchasableProduct(int $price = 2500, int $stock = 100, ?string $sku = null): ProductVariant
    {
        $product = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: 'Test Product ' . uniqid(),
            status: 'published',
            visibility: 'public',
            sku: $sku ?? 'TEST-' . strtoupper(substr(uniqid(), -6)),
            price: $price,
        ));

        $variant = $product->defaultVariant();
        app(InventoryServiceInterface::class)->receive($variant->uuid, $stock);

        return $variant;
    }

    protected function seedCheckoutDependencies(): void
    {
        $this->seed([
            \Commerce\Shipping\Database\Seeders\ShippingMethodSeeder::class,
            \Commerce\Tax\Database\Seeders\TaxRateSeeder::class,
            \Commerce\Currency\Database\Seeders\CurrencySeeder::class,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function checkoutPayload(?string $shippingMethodUuid = null): array
    {
        $shippingMethodUuid ??= \Commerce\Shipping\Models\ShippingMethod::query()
            ->where('code', 'standard')
            ->value('uuid');

        return [
            'customer_email' => 'buyer@example.com',
            'customer_name' => 'Test Buyer',
            'shipping_address' => [
                'line1' => '123 Main St',
                'city' => 'New York',
                'postal_code' => '10001',
                'country_code' => 'US',
            ],
            'billing_same_as_shipping' => true,
            'shipping_method_uuid' => $shippingMethodUuid,
        ];
    }
}
