<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Contracts\Storefront\StoreVisibility;
use Commerce\Customers\Models\Customer;
use Commerce\Product\Services\ProductSearchIndexer;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\Concerns\SetsStoreVisibility;
use Tests\TestCase;

final class StoreVisibilityCatalogTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;
    use SetsStoreVisibility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->withoutVite();
    }

    public function test_catalog_guest_sees_product_but_not_price_or_purchase_controls(): void
    {
        $this->setStoreVisibility(StoreVisibility::Catalog);
        $variant = $this->createPurchasableProduct(price: 987654, sku: 'CAT-HIDE');
        $product = $variant->product;
        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $html = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('storefront-price-login--card', false)
            ->assertDontSee('storefront-product-card__quick-add', false)
            ->getContent();

        $this->assertStringNotContainsString('9876.54', $html);
        $this->assertStringNotContainsString((string) $variant->price, $html);

        $pdp = $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('storefront-price-login', false)
            ->assertDontSee('storefront-pdp__add', false)
            ->assertDontSee('storefront-buy-box__cta--buy', false)
            ->assertDontSee('storefront-mobile-buy-bar', false)
            ->getContent();

        $this->assertStringNotContainsString('data-product-price="987654"', $pdp);
        $this->assertStringNotContainsString('9876.54', $pdp);
        $this->assertStringContainsString(route('storefront.account.login'), $pdp);
    }

    public function test_catalog_quick_view_hides_price_fields(): void
    {
        $this->setStoreVisibility(StoreVisibility::Catalog);
        $variant = $this->createPurchasableProduct(price: 987654, sku: 'CAT-QV');

        $this->getJson(route('api.v1.storefront.products.quick-view', $variant->product->uuid))
            ->assertOk()
            ->assertJsonPath('data.uuid', $variant->product->uuid)
            ->assertJsonPath('data.price', null)
            ->assertJsonPath('data.sale_price', null)
            ->assertJsonPath('data.formatted_price', null)
            ->assertJsonPath('data.prices_hidden', true);
    }

    public function test_catalog_guest_cannot_add_to_cart(): void
    {
        $this->setStoreVisibility(StoreVisibility::Catalog);
        $variant = $this->createPurchasableProduct(price: 2500, sku: 'CAT-CART');

        $this->from(route('storefront.products.show', $variant->product->slug))
            ->post(route('storefront.cart.items.store'), [
                'purchasable_uuid' => $variant->uuid,
                'quantity' => 1,
            ])
            ->assertRedirect(route('storefront.account.login', [
                'redirect' => route('storefront.products.show', $variant->product->slug),
            ]));
    }

    public function test_catalog_logged_in_customer_sees_prices_and_can_add_to_cart(): void
    {
        $this->setStoreVisibility(StoreVisibility::Catalog);
        $variant = $this->createPurchasableProduct(price: 2500, sku: 'CAT-OK');
        $product = $variant->product;
        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $customer = Customer::query()->create([
            'email' => 'catalog.member@example.com',
            'name' => 'Catalog Member',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->assertSee('storefront-pdp__add', false)
            ->assertDontSee('storefront-price-login', false);

        $this->actingAs($customer, 'customer')
            ->post(route('storefront.cart.items.store'), [
                'purchasable_uuid' => $variant->uuid,
                'quantity' => 1,
            ])
            ->assertRedirect(route('storefront.cart.index'));
    }

    public function test_public_mode_is_unchanged(): void
    {
        $this->setStoreVisibility(StoreVisibility::Public);
        $variant = $this->createPurchasableProduct(price: 2500, sku: 'PUB-OK');
        app(ProductSearchIndexer::class)->index($variant->product->fresh(['variants', 'categories']));

        $this->get(route('storefront.products.show', $variant->product->slug))
            ->assertOk()
            ->assertSee('storefront-pdp__add', false)
            ->assertDontSee('storefront-price-login', false);
    }
}
