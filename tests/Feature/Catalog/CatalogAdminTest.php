<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\Models\Category;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Media\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        app()->setLocale('en');
    }

    public function test_catalog_nav_renders_every_old_tab(): void
    {
        $tabs = [
            __('catalog::admin.overview'),
            __('catalog::admin.categories'),
            __('catalog::admin.brands'),
            __('catalog::admin.collections'),
            __('catalog::admin.tags'),
            __('product::workspace.variant_options_nav'),
            __('catalog::admin.attributes'),
            __('catalog::admin.attribute_sets'),
        ];

        $pages = [
            route('admin.catalog.index'),
            route('admin.catalog.categories.index'),
            route('admin.catalog.brands.index'),
            route('admin.catalog.collections.index'),
            route('admin.catalog.tags.index'),
            route('admin.catalog.variant-options.index'),
            route('admin.catalog.attributes.index'),
            route('admin.catalog.attribute-sets.index'),
        ];

        foreach ($pages as $url) {
            $response = $this->actingAs(User::query()->first())->get($url)->assertOk();

            foreach ($tabs as $tab) {
                $response->assertSee($tab, false);
            }
        }
    }

    public function test_category_form_has_image_picker_and_seo_and_saves_them(): void
    {
        $media = Media::query()->create([
            'filename' => 'kids.jpg',
            'original_filename' => 'kids.jpg',
            'mime_type' => 'image/jpeg',
            'media_type' => 'image',
            'size' => 1024,
            'disk' => 'public',
            'path' => 'media/kids.jpg',
        ]);

        $this->actingAs(User::query()->first())
            ->get(route('admin.catalog.categories.create'))
            ->assertOk()
            ->assertSee('Category image', false)
            ->assertSee('name="image_media_uuid"', false)
            ->assertSee('Meta title', false);

        $this->actingAs(User::query()->first())
            ->post(route('admin.catalog.categories.store'), [
                'name' => 'Kids',
                'image_media_uuid' => $media->uuid,
                'is_active' => 1,
                'seo' => [
                    'meta_title' => 'Kids SEO',
                    'meta_description' => 'Kids clothes',
                ],
            ])
            ->assertRedirect(route('admin.catalog.categories.index'));

        $category = Category::query()->where('slug', 'kids')->first();

        $this->assertNotNull($category);
        $this->assertSame($media->uuid, $category->image_media_uuid);

        $this->actingAs(User::query()->first())
            ->get(route('admin.catalog.categories.edit', $category))
            ->assertOk()
            ->assertSee('Kids SEO', false);
    }

    public function test_brand_form_has_logo_picker_and_seo(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.catalog.brands.create'))
            ->assertOk()
            ->assertSee('Brand logo', false)
            ->assertSee('Meta title', false);

        $this->actingAs(User::query()->first())
            ->post(route('admin.catalog.brands.store'), [
                'name' => 'Nike',
                'is_active' => 1,
                'seo' => [
                    'meta_title' => 'Nike SEO',
                ],
            ])
            ->assertRedirect(route('admin.catalog.brands.index'));

        $this->assertDatabaseHas('brands', [
            'name' => 'Nike',
            'slug' => 'nike',
        ]);
    }

    public function test_variant_options_can_be_created_from_catalog_tab(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.catalog.variant-options.create'))
            ->assertOk()
            ->assertSee(__('product::workspace.variant_options_create'));

        $this->actingAs(User::query()->first())
            ->post(route('admin.catalog.variant-options.store'), [
                'name' => 'Color',
                'code' => 'color',
                'options' => ['Red', 'Blue'],
                'position' => 0,
            ])
            ->assertRedirect(route('admin.catalog.variant-options.index'));

        $this->actingAs(User::query()->first())
            ->get(route('admin.catalog.variant-options.index'))
            ->assertOk()
            ->assertSee('Color', false)
            ->assertSee('Red', false);
    }
}
