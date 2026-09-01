<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CollectionAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_create_collection(): void
    {
        $this->actingAs(User::query()->first())
            ->post(route('admin.catalog.collections.store'), [
                'name' => 'New Arrivals',
                'description' => 'Latest products',
            ])
            ->assertRedirect(route('admin.catalog.collections.index'));

        $this->assertDatabaseHas('collections', [
            'name' => 'New Arrivals',
            'slug' => 'new-arrivals',
        ]);
    }

    public function test_admin_can_update_collection_with_cover_image(): void
    {
        $collection = Collection::query()->create([
            'name' => 'Featured',
            'slug' => 'featured',
        ]);

        $this->actingAs(User::query()->first())
            ->put(route('admin.catalog.collections.update', $collection), [
                'name' => 'Featured Picks',
                'slug' => 'featured-picks',
                'description' => 'Hand-picked favorites',
            ])
            ->assertRedirect(route('admin.catalog.collections.index'));

        $this->assertDatabaseHas('collections', [
            'uuid' => $collection->uuid,
            'name' => 'Featured Picks',
            'slug' => 'featured-picks',
            'description' => 'Hand-picked favorites',
        ]);
    }

    public function test_admin_can_create_automated_collection_with_rule_builder_fields(): void
    {
        $category = Category::query()->create([
            'name' => 'Summer',
            'slug' => 'summer',
            'is_active' => true,
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.catalog.collections.store'), [
                'name' => 'Summer Sale',
                'type' => 'automated',
                'rules' => [
                    'match' => 'all',
                    'on_sale' => '1',
                    'category_ids' => [$category->id],
                    'category_match' => 'any',
                    'price_min' => '10',
                    'price_max' => '100',
                ],
            ])
            ->assertRedirect(route('admin.catalog.collections.index'));

        $collection = Collection::query()->where('slug', 'summer-sale')->first();

        $this->assertNotNull($collection);
        $this->assertSame(Collection::TYPE_AUTOMATED, $collection->type);
        $this->assertSame('all', $collection->rules['match']);
        $this->assertTrue($collection->rules['on_sale']);
        $this->assertSame('any', $collection->rules['category_match']);
        $this->assertSame([$category->id], $collection->rules['category_ids']);
        $this->assertSame(10.0, (float) $collection->rules['price_min']);
        $this->assertSame(100.0, (float) $collection->rules['price_max']);
    }

    public function test_admin_collection_form_renders_rule_builder(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.catalog.collections.index'))
            ->assertOk()
            ->assertSee('data-rule-builder', false)
            ->assertSee('Add condition');
    }

    public function test_admin_can_view_collections_index(): void
    {
        Collection::query()->create([
            'name' => 'Featured',
            'slug' => 'featured',
        ]);

        $this->actingAs(User::query()->first())
            ->get(route('admin.catalog.collections.index'))
            ->assertOk()
            ->assertSee('Featured');
    }
}
