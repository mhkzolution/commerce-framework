<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cart\Services\ProductDetailBuilder;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Models\ProductMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class Ws002PdpContractTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_pdp_uses_page_container_shared_header_and_storefront_cart_form(): void
    {
        $variant = $this->createPurchasableProduct(price: 3200, stock: 3, sku: 'PDP-PAGE-1');
        $product = $variant->product;

        $html = $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-pdp-main', $html);
        $this->assertStringContainsString('storefront-page-container', $html);
        $this->assertStringContainsString('storefront-site-header', $html);
        $this->assertStringContainsString('storefront-pdp__add', $html);
        $this->assertStringContainsString('storefront-pdp--market', $html);
        $this->assertStringContainsString('storefront-buy-box', $html);
        $this->assertStringContainsString(__('storefront::storefront.buy_now'), $html);
        $this->assertStringContainsString('name="purchasable_uuid"', $html);
        $this->assertStringContainsString($variant->uuid, $html);
        $this->assertStringContainsString(__('storefront::storefront.add_to_cart'), $html);
        $this->assertStringNotContainsString('cf-btn', $html);
        $this->assertStringNotContainsString('cf-input', $html);
        $this->assertStringNotContainsString('Add to cart', $html);
    }

    public function test_pdp_renders_primary_image_when_media_url_exists(): void
    {
        $variant = $this->createPurchasableProduct(price: 2100, stock: 2, sku: 'PDP-IMG-2');
        $mediaUuid = 'media-pdp-contract';

        ProductMedia::query()->create([
            'product_id' => $variant->product->id,
            'media_uuid' => $mediaUuid,
            'position' => 0,
            'is_primary' => true,
        ]);

        $this->app->instance(MediaQueryServiceInterface::class, new class($mediaUuid) implements MediaQueryServiceInterface
        {
            public function __construct(private readonly string $uuid) {}

            public function findByUuid(string $uuid): ?object
            {
                return null;
            }

            public function getUrl(string $uuid, ?string $variant = null): ?string
            {
                return $uuid === $this->uuid ? 'https://cdn.example.test/pdp-contract.jpg' : null;
            }

            public function getSrcset(string $uuid): ?string
            {
                return $uuid === $this->uuid ? 'https://cdn.example.test/pdp-contract.jpg 800w' : null;
            }

            public function findByUuids(array $uuids): array
            {
                return [];
            }
        });
        $this->app->forgetInstance(ProductDetailBuilder::class);

        $html = $this->get(route('storefront.products.show', $variant->product->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('src="https://cdn.example.test/pdp-contract.jpg"', $html);
        $this->assertStringContainsString('alt="'.$variant->product->name.'"', $html);
    }

    public function test_out_of_stock_keeps_buy_form_hidden_for_variant_switching(): void
    {
        $variant = $this->createPurchasableProduct(price: 1800, stock: 1, sku: 'PDP-OOS-2');
        app(InventoryServiceInterface::class)->setOnHand($variant->uuid, 0);

        $html = $this->get(route('storefront.products.show', $variant->product->slug))
            ->assertOk()
            ->assertSee(__('storefront::storefront.out_of_stock'))
            ->getContent();

        $this->assertStringContainsString('data-buy-form', $html);
        $this->assertStringContainsString('data-buy-unavailable', $html);
        $this->assertStringContainsString('data-mobile-buy-bar', $html);
        $this->assertMatchesRegularExpression('/<form[^>]*data-buy-form[^>]*hidden/', $html);
    }

    public function test_pdp_renders_buy_form_when_sibling_variant_is_in_stock(): void
    {
        $default = $this->createPurchasableProduct(price: 1800, stock: 1, sku: 'PDP-MULTI-OOS-2');
        app(InventoryServiceInterface::class)->setOnHand($default->uuid, 0);
        $sibling = $default->product->variants()->create([
            'tenant_id' => $default->tenant_id,
            'sku' => 'PDP-MULTI-IN-2',
            'track_inventory' => true,
            'name' => 'In-stock sibling',
            'price' => 1800,
            'is_default' => false,
            'position' => 1,
            'meta' => ['options' => ['Size' => 'Large']],
        ]);
        app(InventoryServiceInterface::class)->receive($sibling->uuid, 3);

        $html = $this->get(route('storefront.products.show', $default->product->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-buy-form', $html);
        $this->assertStringContainsString('data-buy-unavailable', $html);
        $this->assertStringContainsString('data-mobile-buy-bar', $html);
        $this->assertStringContainsString('"in_stock":true', $html);
    }

    public function test_axis_value_stays_enabled_when_any_matching_variant_is_in_stock(): void
    {
        $default = $this->createPurchasableProduct(price: 1800, stock: 1, sku: 'PDP-RED-SMALL');
        app(InventoryServiceInterface::class)->setOnHand($default->uuid, 0);
        $default->update(['meta' => ['options' => ['Color' => 'Red', 'Size' => 'Small']]]);
        $sibling = $default->product->variants()->create([
            'tenant_id' => $default->tenant_id,
            'sku' => 'PDP-RED-LARGE',
            'track_inventory' => true,
            'name' => 'Red Large',
            'price' => 1800,
            'is_default' => false,
            'position' => 1,
            'meta' => ['options' => ['Color' => 'Red', 'Size' => 'Large']],
        ]);
        app(InventoryServiceInterface::class)->receive($sibling->uuid, 3);

        $html = $this->get(route('storefront.products.show', $default->product->slug))
            ->assertOk()
            ->getContent();

        $matched = preg_match('/<button[^>]*data-axis-value="Red"[^>]*>/', $html, $button);
        $this->assertSame(1, $matched);
        $this->assertStringNotContainsString('disabled', $button[0]);
    }

    public function test_buy_now_redirects_to_checkout(): void
    {
        $variant = $this->createPurchasableProduct(price: 2100, stock: 2, sku: 'PDP-BUY-1');

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
            'redirect_to' => 'checkout',
        ])->assertRedirect(route('storefront.checkout'));
    }
}
