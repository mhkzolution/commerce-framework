<?php

declare(strict_types=1);

namespace Tests\Feature\Currency;

use Commerce\Currency\Database\Seeders\CurrencySeeder;
use Commerce\Currency\Models\Currency;
use Commerce\Currency\Services\CurrencyQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontDecimalPlacesTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CurrencySeeder::class);
        Currency::query()->where('code', 'THB')->update(['decimal_places' => 0]);
        app(CurrencyQueryService::class)->clearCache();
    }

    public function test_shop_and_pdp_omit_cents_when_currency_has_zero_decimals(): void
    {
        $variant = $this->createPurchasableProduct(price: 8000, sku: 'THB-ZERO-DEC');
        $product = $variant->product;

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee($product->name, false)
            ->assertSee('฿80', false)
            ->assertDontSee('80.00')
            ->assertSee('window.__storefrontMoney', false)
            ->assertSee('"decimals":0', false);

        $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->assertSee($product->name, false)
            ->assertSee('฿80', false)
            ->assertDontSee('80.00');
    }
}
