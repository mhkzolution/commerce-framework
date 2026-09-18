<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cart\Services\StorefrontBrandDirectory;
use Commerce\Cart\Services\StorefrontPrimaryNavigation;
use Commerce\Catalog\Models\Brand;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontBrandsTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_archive_is_a_searchable_az_directory_of_brand_cards(): void
    {
        $this->createBrand('Nestlé', 'nestle', withProduct: true);
        $this->createBrand('Adidas', 'adidas', withProduct: true);
        $this->createBrand('บ้านนา', 'banna', withProduct: true);
        $this->createBrand('Empty Co', 'empty-co');

        $html = $this->get(route('storefront.brands.index'))
            ->assertOk()
            ->assertSee(__('storefront::storefront.brands_archive_title'), false)
            ->assertSee(__('storefront::storefront.brands_archive_subtitle'), false)
            ->assertSee('storefront-brands-search__input', false)
            ->assertSee('storefront-shop-category-strip', false)
            ->assertSee('storefront-brands-alpha', false)
            ->assertSee('storefront-brand-card', false)
            ->assertSee('Nestlé')
            ->assertSee('Adidas')
            ->assertSee('บ้านนา')
            ->assertDontSee('Empty Co')
            ->assertSee(route('storefront.brands.show', 'nestle'), false)
            ->getContent();

        $this->assertStringContainsString('data-brands-search-input', $html);
        $this->assertStringContainsString('storefront-shop-toolbar__count', $html);
        $this->assertStringContainsString('id="brand-letter-a"', $html);
        $this->assertStringContainsString('id="brand-letter-n"', $html);
        $this->assertStringContainsString('id="brand-letter-other"', $html);
        $this->assertStringNotContainsString('data-brands-alpha-letter="B"', $html);
        $this->assertStringContainsString('A · 1', $html);
        $this->assertStringNotContainsString('About Brand', $html);
        $this->assertStringContainsString('"@type":"ItemList"', $html);
        $this->assertStringContainsString('"@type":"Brand"', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString(route('storefront.brands.index'), $html);
        $this->assertStringContainsString('storefront-brand-card__monogram', $html);
        $this->assertStringContainsString('storefront-brand-card__count', $html);
    }

    public function test_archive_hides_brands_without_storefront_products(): void
    {
        $this->createBrand('Craft Studio', 'craft-studio');
        $this->createBrand('North Peak', 'north-peak');

        $this->get(route('storefront.brands.index'))
            ->assertOk()
            ->assertSee(__('storefront::storefront.brands_empty'))
            ->assertDontSee('Craft Studio')
            ->assertDontSee('North Peak')
            ->assertDontSee('storefront-brand-card', false);
    }

    public function test_brand_landing_reuses_shop_listing_and_locks_the_brand(): void
    {
        $this->createBrand('Nestlé', 'nestle', withProduct: true, productName: 'Nestlé Cocoa');
        $this->createBrand('Adidas', 'adidas', withProduct: true, productName: 'Adidas Tee');

        $html = $this->get(route('storefront.brands.show', 'nestle'))
            ->assertOk()
            ->assertSee('Nestlé Cocoa')
            ->assertDontSee('Adidas Tee')
            ->assertSee(__('storefront::storefront.home'))
            ->assertSee(__('storefront::storefront.nav_brands'))
            ->assertSee('storefront-product-card', false)
            ->assertSee('storefront-shop-toolbar', false)
            ->assertDontSee('Best Selling')
            ->getContent();

        $this->assertStringContainsString('storefront-shop-filters-sidebar', $html);
        $this->assertStringNotContainsString('name="brand"', $html);
        $this->assertStringContainsString(route('storefront.brands.show', 'nestle'), $html);
        $this->assertStringContainsString('"@type":"CollectionPage"', $html);
        $this->assertStringContainsString('"@type":"Product"', $html);
        $this->assertStringNotContainsString('"@type":"Brand"', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
    }

    public function test_legacy_shop_brand_query_permanently_redirects_to_the_brand_landing(): void
    {
        $this->createBrand('Nestlé', 'nestle');

        $this->get('/shop?brand=nestle')
            ->assertRedirect('/brands/nestle')
            ->assertStatus(301);

        $this->get('/shop?brand=nestle&page=2')
            ->assertRedirect('/brands/nestle?page=2')
            ->assertStatus(301);

        $this->get('/shop?brand=nestle&sort=price_asc')
            ->assertRedirect('/brands/nestle?sort=price_asc')
            ->assertStatus(301);
    }

    public function test_unknown_and_inactive_brands_are_not_found(): void
    {
        $this->createBrand('Hidden', 'hidden', active: false);

        $this->get(route('storefront.brands.show', 'hidden'))->assertNotFound();
        $this->get(route('storefront.brands.show', 'missing'))->assertNotFound();
    }

    public function test_empty_brand_landing_points_shoppers_back_to_shop(): void
    {
        $this->createBrand('Empty Co', 'empty-co');

        $this->get(route('storefront.brands.show', 'empty-co'))
            ->assertOk()
            ->assertSee(__('storefront::storefront.brand_empty_title'))
            ->assertSee(__('storefront::storefront.brand_browse_all'))
            ->assertSee(route('storefront.shop.index'), false);
    }

    public function test_filtered_brand_landing_canonicalizes_and_is_not_indexed(): void
    {
        $this->createBrand('Nestlé', 'nestle', withProduct: true);

        $html = $this->get(route('storefront.brands.show', [
            'slug' => 'nestle',
            'sort' => 'price_asc',
        ]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('noindex,follow', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString(e(route('storefront.brands.show', 'nestle')), $html);
    }

    public function test_brands_mega_menu_shows_popular_az_and_explore(): void
    {
        $this->createBrand('Craft Studio', 'craft-studio');
        $this->createBrand('North Peak', 'north-peak');
        $this->createBrand('Harbor Goods', 'harbor-goods', withProduct: true);
        $this->createBrand('Peak Supply', 'peak-supply', withProduct: true);

        $nav = app(StorefrontPrimaryNavigation::class)->build();
        $brands = collect($nav['items'])->firstWhere('id', 'brands');
        $this->assertIsArray($brands);
        $this->assertCount(3, $brands['columns'] ?? []);

        $popular = $brands['columns'][0];
        $letters = $brands['columns'][1];
        $explore = $brands['columns'][2];

        $labels = array_column($popular['links'] ?? [], 'label');
        $this->assertSame(__('storefront::storefront.nav_popular_brands'), $popular['title'] ?? null);
        $this->assertSame(['Harbor Goods', 'Peak Supply'], $labels);
        $this->assertNotContains('Craft Studio', $labels);
        $this->assertNotContains('North Peak', $labels);
        $this->assertNull($popular['view_all'] ?? null);

        $this->assertSame(__('storefront::storefront.nav_browse_az'), $letters['title'] ?? null);
        $this->assertSame('pills', $letters['variant'] ?? null);
        $this->assertSame(['H', 'P'], array_column($letters['links'] ?? [], 'label'));
        $this->assertStringEndsWith('#brand-letter-h', $letters['links'][0]['url'] ?? '');
        $this->assertStringEndsWith('#brand-letter-p', $letters['links'][1]['url'] ?? '');

        $this->assertSame(__('storefront::storefront.nav_explore'), $explore['title'] ?? null);
        $this->assertSame('cta', $explore['variant'] ?? null);
        $this->assertSame(
            __('storefront::storefront.nav_view_all_brands'),
            $explore['view_all']['label'] ?? null,
        );
        $this->assertSame(route('storefront.brands.index'), $explore['view_all']['url'] ?? null);
    }

    public function test_brands_mega_menu_keeps_explore_when_every_brand_has_zero_products(): void
    {
        $this->createBrand('Craft Studio', 'craft-studio');
        $this->createBrand('North Peak', 'north-peak');

        $nav = app(StorefrontPrimaryNavigation::class)->build();
        $brands = collect($nav['items'])->firstWhere('id', 'brands');
        $this->assertIsArray($brands);
        $this->assertCount(1, $brands['columns'] ?? []);
        $this->assertSame('cta', $brands['columns'][0]['variant'] ?? null);
        $this->assertSame(__('storefront::storefront.nav_explore'), $brands['columns'][0]['title'] ?? null);
        $this->assertSame(route('storefront.brands.index'), $brands['columns'][0]['view_all']['url'] ?? null);
    }

    public function test_brand_directory_counts_are_cached_for_five_minutes(): void
    {
        $this->assertSame(300, StorefrontBrandDirectory::CACHE_TTL_SECONDS);
        $source = file_get_contents(dirname(__DIR__, 3).'/modules/Cart/src/Services/StorefrontBrandDirectory.php');
        $this->assertNotFalse($source);
        $this->assertStringContainsString('Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS', $source);
        $this->assertStringContainsString('groupBy(\'brand_uuid\')', $source);
    }

    public function test_directory_groups_latin_letters_and_other_glyphs(): void
    {
        $this->assertSame('N', StorefrontBrandDirectory::letterFor('Nestlé'));
        $this->assertSame('#', StorefrontBrandDirectory::letterFor('บ้านนา'));
        $this->assertSame('#', StorefrontBrandDirectory::letterFor('3M'));
        $this->assertSame('n', StorefrontBrandDirectory::letterAnchor('N'));
        $this->assertSame('other', StorefrontBrandDirectory::letterAnchor('#'));
        $this->assertSame('N', StorefrontBrandDirectory::monogramFor('Nestlé'));
        $this->assertSame('บ', StorefrontBrandDirectory::monogramFor('บ้านนา'));
    }

    private function createBrand(
        string $name,
        string $slug,
        bool $withProduct = false,
        string $productName = 'Brand Product',
        bool $active = true,
    ): Brand {
        $brand = Brand::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => $active,
        ]);

        if ($withProduct) {
            $product = app(ProductServiceInterface::class)->create(new CreateProductData(
                name: $productName,
                status: 'published',
                visibility: 'public',
                sku: 'BRAND-'.strtoupper($slug),
                price: 2500,
                brandUuid: $brand->uuid,
            ));
            $variant = $product->defaultVariant();
            $this->assertNotNull($variant);
            app(InventoryServiceInterface::class)->receive($variant->uuid, 4);
        }

        return $brand;
    }
}
