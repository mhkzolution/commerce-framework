<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\DTO\CreateCategoryData;
use Commerce\Catalog\Services\CategoryService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CatalogStorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_storefront_category_page_is_accessible(): void
    {
        $category = app(CategoryService::class)->create(new CreateCategoryData(
            name: 'Outdoor Gear',
            slug: 'outdoor-gear',
            isActive: true,
        ));

        $category->update(['is_active' => true]);

        $this->get(route('storefront.catalog.categories.show', $category->slug))
            ->assertOk()
            ->assertSee('Outdoor Gear');
    }
}
