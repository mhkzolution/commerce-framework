<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\Models\Brand;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CatalogApiSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_brand_api_includes_seo_payload(): void
    {
        $brand = Brand::query()->create([
            'name' => 'API Brand',
            'slug' => 'api-brand',
            'is_active' => true,
        ]);

        app(SeoServiceInterface::class)->setForEntity(Brand::SEO_ENTITY_TYPE, $brand->uuid, [
            'meta_title' => 'API Brand SEO',
            'meta_description' => 'Brand description for API',
        ]);

        $this->actingAs(User::query()->first())
            ->getJson(route('api.v1.catalog.brands.show', $brand->uuid))
            ->assertOk()
            ->assertJsonPath('data.seo.title', 'API Brand SEO')
            ->assertJsonPath('data.seo.description', 'Brand description for API');
    }
}
