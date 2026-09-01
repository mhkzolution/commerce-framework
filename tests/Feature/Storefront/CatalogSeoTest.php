<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Catalog\Models\Collection;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CatalogSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_landing_page_renders_seo_meta_tags(): void
    {
        $collection = Collection::query()->create([
            'name' => 'Winter Collection',
            'slug' => 'winter-collection',
            'description' => 'Cozy styles for cold days',
        ]);

        app(SeoServiceInterface::class)->setForEntity(Collection::SEO_ENTITY_TYPE, $collection->uuid, [
            'meta_title' => 'Winter Collection SEO Title',
            'meta_description' => 'Shop our winter picks online',
            'meta_keywords' => 'winter, coats',
        ]);

        $this->get(route('storefront.catalog.collections.show', $collection->slug))
            ->assertOk()
            ->assertSee('Winter Collection SEO Title', false)
            ->assertSee('Shop our winter picks online', false)
            ->assertSee('winter, coats', false);
    }
}
