<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\Database\Seeders\CatalogMockupSeeder;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Support\CatalogMediaResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CatalogMockupSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_seeder_creates_mock_images_for_categories_and_brands(): void
    {
        $category = Category::query()->create([
            'uuid' => (string) str()->uuid(),
            'name' => 'Kids Wear',
            'slug' => 'kids-wear',
            'is_active' => true,
            'position' => 0,
        ]);

        $brand = Brand::query()->create([
            'uuid' => (string) str()->uuid(),
            'name' => 'Acme',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $this->seed(CatalogMockupSeeder::class);

        $category->refresh();
        $brand->refresh();

        $this->assertNotNull($category->image_media_uuid);
        $this->assertNotNull($brand->logo_media_uuid);

        $resolver = app(CatalogMediaResolver::class);
        $this->assertNotNull($resolver->url($category->image_media_uuid));
        $this->assertNotNull($resolver->url($brand->logo_media_uuid));
    }
}
