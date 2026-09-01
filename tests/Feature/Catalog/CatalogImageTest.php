<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\DTO\CreateBrandData;
use Commerce\Catalog\DTO\CreateCategoryData;
use Commerce\Catalog\Services\BrandService;
use Commerce\Catalog\Services\CategoryService;
use Commerce\Catalog\Support\CatalogMediaResolver;
use Commerce\Media\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class CatalogImageTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_category_can_store_image_media_uuid(): void
    {
        $media = $this->createMedia('category.jpg');

        $category = app(CategoryService::class)->create(new CreateCategoryData(
            name: 'Kids',
            slug: 'kids',
            imageMediaUuid: $media->uuid,
            isActive: true,
        ));

        $this->assertSame($media->uuid, $category->fresh()->image_media_uuid);
    }

    public function test_brand_can_store_logo_media_uuid(): void
    {
        $media = $this->createMedia('brand-logo.png');

        $brand = app(BrandService::class)->create(new CreateBrandData(
            name: 'Nike',
            slug: 'nike',
            logoMediaUuid: $media->uuid,
            isActive: true,
        ));

        $this->assertSame($media->uuid, $brand->fresh()->logo_media_uuid);
    }

    public function test_shop_shows_category_and_brand_images_in_filters(): void
    {
        $categoryMedia = $this->createMedia('shop-category.jpg');
        $brandMedia = $this->createMedia('shop-brand.png');

        $category = app(CategoryService::class)->create(new CreateCategoryData(
            name: 'Shoes',
            slug: 'shoes',
            imageMediaUuid: $categoryMedia->uuid,
            isActive: true,
        ));

        $brand = app(BrandService::class)->create(new CreateBrandData(
            name: 'Puma',
            slug: 'puma',
            logoMediaUuid: $brandMedia->uuid,
            isActive: true,
        ));

        $product = $this->createPurchasableProduct(price: 100, stock: 5, sku: 'IMG-FILTER-001');
        $product->product->update([
            'brand_uuid' => $brand->uuid,
        ]);
        $product->product->categories()->attach($category->id);

        $categoryUrl = app(CatalogMediaResolver::class)->url($category->image_media_uuid);
        $brandUrl = app(CatalogMediaResolver::class)->url($brand->logo_media_uuid);

        $this->assertNotNull($categoryUrl);
        $this->assertNotNull($brandUrl);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee($categoryUrl, false)
            ->assertSee($brandUrl, false);
    }

    private function createMedia(string $filename): Media
    {
        $file = UploadedFile::fake()->image($filename, 120, 120);
        $path = $file->store('media', 'public');

        return Media::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'path' => $path,
            'filename' => basename($path),
            'original_filename' => $filename,
            'mime_type' => str_ends_with($filename, '.png') ? 'image/png' : 'image/jpeg',
            'size' => $file->getSize(),
            'media_type' => 'image',
        ]);
    }
}
