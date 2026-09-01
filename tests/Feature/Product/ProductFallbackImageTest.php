<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Media\Models\Media;
use Commerce\Product\Services\ProductImageResolver;
use Commerce\Product\Services\ProductSearchIndexer;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductFallbackImageTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_admin_can_view_fallback_image_settings_page(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.products.settings.show'))
            ->assertOk()
            ->assertSee('Fallback image');
    }

    public function test_admin_can_save_fallback_image_setting(): void
    {
        $media = $this->createMedia();

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.settings.update'), [
                'fallback_image_media_uuid' => $media->uuid,
            ])
            ->assertRedirect(route('admin.products.settings.show'));

        $this->assertNotNull(app(ProductImageResolver::class)->fallbackUrl());
        $this->assertStringContainsString($media->path, (string) app(ProductImageResolver::class)->fallbackUrl());
    }

    public function test_fallback_image_is_used_when_product_has_no_media(): void
    {
        $media = $this->createMedia();

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.settings.update'), [
                'fallback_image_media_uuid' => $media->uuid,
            ]);

        $variant = $this->createPurchasableProduct(price: 10, stock: 1, sku: 'NO-IMG-001');
        $product = $variant->product->fresh(['media']);

        $url = app(ProductImageResolver::class)->urlForProduct($product);

        $this->assertNotNull($url);
        $this->assertStringContainsString($media->path, $url);
    }

    public function test_storefront_shows_fallback_image_on_product_page(): void
    {
        $media = $this->createMedia();

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.settings.update'), [
                'fallback_image_media_uuid' => $media->uuid,
            ]);

        $variant = $this->createPurchasableProduct(price: 10, stock: 1, sku: 'STORE-IMG-001');
        $product = $variant->product;

        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->assertSee($media->path, false);
    }

    private function createMedia(): Media
    {
        $file = UploadedFile::fake()->image('fallback.jpg', 120, 120);
        $path = $file->store('media', 'public');

        return Media::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'path' => $path,
            'filename' => basename($path),
            'original_filename' => 'fallback.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $file->getSize(),
            'media_type' => 'image',
            'alt_text' => 'Fallback',
        ]);
    }
}
