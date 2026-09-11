<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Media\Models\Media;
use Commerce\Product\Services\ProductSearchIndexer;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductSettingsTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_and_save_product_settings(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.products.settings.show'))
            ->assertOk()
            ->assertSee('data-file-attach', false)
            ->assertSee('Fallback image', false)
            ->assertSee('Upload', false)
            ->assertSee('From URL', false)
            ->assertSee('Library', false)
            ->assertSee('Default SKU pattern', false)
            ->assertDontSee('Fallback image media UUID', false);

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.settings.update'), [
                'sku_pattern' => '{PRODUCT}-{SIZE}',
                'fallback_image_media_uuid' => null,
            ])
            ->assertRedirect(route('admin.products.settings.show'));

        $this->assertSame('{PRODUCT}-{SIZE}', app(SettingQueryServiceInterface::class)->get('product.sku_pattern'));
    }

    public function test_saved_fallback_image_is_shown_when_a_product_has_no_media(): void
    {
        $this->withoutVite();

        $fallback = Media::query()->create([
            'filename' => 'product-fallback.jpg',
            'original_filename' => 'product-fallback.jpg',
            'mime_type' => 'image/jpeg',
            'media_type' => 'image',
            'size' => 1024,
            'disk' => 'public',
            'path' => 'media/product-fallback.jpg',
        ]);

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.settings.update'), [
                'sku_pattern' => '{PRODUCT}-{COLOR}-{SIZE}',
                'fallback_image_media_uuid' => $fallback->uuid,
            ])
            ->assertRedirect(route('admin.products.settings.show'));

        $this->assertSame($fallback->uuid, app(SettingQueryServiceInterface::class)->get('product.fallback_image_media_uuid'));

        $variant = $this->createPurchasableProduct(price: 1500, stock: 4, sku: 'FALLBACK-PDP-1');
        $product = $variant->product;
        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee($product->name, false)
            ->assertSee('/storage/media/product-fallback.jpg', false)
            ->assertSee('storefront-product-card__image', false)
            ->assertDontSee('storefront-product-card__placeholder', false);

        $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->assertSee('/storage/media/product-fallback.jpg', false)
            ->assertDontSee('storefront-gallery__placeholder', false);
    }
}
