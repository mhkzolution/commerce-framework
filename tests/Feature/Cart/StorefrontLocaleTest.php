<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use Commerce\Currency\Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontLocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CurrencySeeder::class);
    }

    public function test_storefront_defaults_to_thai_locale(): void
    {
        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('ร้านค้า')
            ->assertSee('ตะกร้า');
    }

    public function test_storefront_can_switch_locale_to_english(): void
    {
        $this->post(route('storefront.locale'), ['locale' => 'en'])
            ->assertRedirect();

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('Shop')
            ->assertSee('Cart');
    }

    public function test_storefront_rejects_unsupported_locale(): void
    {
        $this->from(route('storefront.shop.index'))
            ->post(route('storefront.locale'), ['locale' => 'fr'])
            ->assertRedirect(route('storefront.shop.index'))
            ->assertSessionHasErrors('locale');
    }

    public function test_base_currency_defaults_to_thb_after_seed(): void
    {
        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('THB', false);
    }
}
