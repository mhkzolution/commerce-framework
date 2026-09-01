<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Contracts\Pricing\PriceResolverInterface;
use Commerce\Core\Pricing\PricingContext;
use Commerce\Product\Models\ProductVariantPriceTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class TierPricingTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_quantity_tier_reduces_unit_price(): void
    {
        $variant = $this->createPurchasableProduct(price: 10, stock: 50);

        ProductVariantPriceTier::query()->create([
            'variant_uuid' => $variant->uuid,
            'min_quantity' => 5,
            'price' => 8,
        ]);

        $resolver = app(PriceResolverInterface::class);

        $single = $resolver->resolve($variant, new PricingContext(quantity: 1));
        $bulk = $resolver->resolve($variant, new PricingContext(quantity: 5));

        $this->assertSame(1000, $single->getAmount());
        $this->assertSame(800, $bulk->getAmount());
        $this->assertTrue($bulk->getBreakdown()['tier_applied']);
    }
}
