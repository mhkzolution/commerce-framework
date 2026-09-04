<?php

declare(strict_types=1);

namespace Tests\Unit\Cart;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\DTO\CartData;
use Commerce\Cart\Services\ProductDetailBuilder;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Models\ProductMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductDetailBuilderTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_unknown_slug_returns_null(): void
    {
        $this->assertNull(app(ProductDetailBuilder::class)->fromSlug('does-not-exist'));
    }

    public function test_maps_default_variant_price_sku_and_stock(): void
    {
        $variant = $this->createPurchasableProduct(price: 3200, stock: 3, sku: 'PDP-BUILD-1');

        $data = app(ProductDetailBuilder::class)->fromSlug($variant->product->slug);

        $this->assertNotNull($data);
        $this->assertSame($variant->product->name, $data->name);
        $this->assertSame(3200, $data->price);
        $this->assertSame('PDP-BUILD-1', $data->sku);
        $this->assertSame(3, $data->available);
        $this->assertTrue($data->inStock);
        $this->assertSame($variant->uuid, $data->variantUuid);
        $this->assertNull($data->imageUrl);
        $this->assertSame(route('storefront.shop.index'), $data->shopUrl);
    }

    public function test_converts_price_in_the_builder_not_the_view(): void
    {
        $variant = $this->createPurchasableProduct(price: 2000, stock: 2, sku: 'PDP-FX-1');

        $cart = $this->createMock(CartServiceInterface::class);
        $cart->method('get')->willReturn(new CartData(
            currency: 'EUR',
            lines: [],
            subtotal: 0,
            itemCount: 0,
        ));

        $converter = $this->createMock(CurrencyConverterInterface::class);
        $converter->method('baseCurrency')->willReturn('USD');
        $converter->method('convert')->with(2000, 'USD', 'EUR')->willReturn(1840);

        $this->app->instance(CartServiceInterface::class, $cart);
        $this->app->instance(CurrencyConverterInterface::class, $converter);
        $this->app->forgetInstance(ProductDetailBuilder::class);

        $data = app(ProductDetailBuilder::class)->fromSlug($variant->product->slug);

        $this->assertNotNull($data);
        $this->assertSame(1840, $data->price);
        $this->assertSame('EUR', $data->displayCurrency);
    }

    public function test_primary_image_url_comes_from_media_query(): void
    {
        $variant = $this->createPurchasableProduct(price: 1500, stock: 1, sku: 'PDP-IMG-1');
        $mediaUuid = 'media-pdp-primary';

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
                return $uuid === $this->uuid ? 'https://cdn.example.test/pdp.jpg' : null;
            }

            public function findByUuids(array $uuids): array
            {
                return [];
            }
        });
        $this->app->forgetInstance(ProductDetailBuilder::class);

        $data = app(ProductDetailBuilder::class)->fromSlug($variant->product->slug);

        $this->assertNotNull($data);
        $this->assertSame('https://cdn.example.test/pdp.jpg', $data->imageUrl);
        $this->assertNotEmpty($data->gallery);
        $this->assertSame('https://cdn.example.test/pdp.jpg', $data->gallery[0]['url']);
    }

    public function test_inventory_failure_fail_softs_to_in_stock(): void
    {
        $variant = $this->createPurchasableProduct(price: 1100, stock: 4, sku: 'PDP-INV-1');

        $inventory = $this->createMock(InventoryQueryServiceInterface::class);
        $inventory->method('getAvailable')->willThrowException(new RuntimeException('inventory down'));

        $this->app->instance(InventoryQueryServiceInterface::class, $inventory);
        $this->app->forgetInstance(ProductDetailBuilder::class);

        $data = app(ProductDetailBuilder::class)->fromSlug($variant->product->slug);

        $this->assertNotNull($data);
        $this->assertNull($data->available);
        $this->assertTrue($data->inStock);
    }

    public function test_zero_stock_is_out_of_stock(): void
    {
        $variant = $this->createPurchasableProduct(price: 1100, stock: 1, sku: 'PDP-OOS-1');
        app(InventoryServiceInterface::class)->setOnHand($variant->uuid, 0);

        $data = app(ProductDetailBuilder::class)->fromSlug($variant->product->slug);

        $this->assertNotNull($data);
        $this->assertSame(0, $data->available);
        $this->assertFalse($data->inStock);
    }
}
