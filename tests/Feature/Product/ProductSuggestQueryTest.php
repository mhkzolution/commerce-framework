<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Cart\Services\HomepageNavigationQuery;
use Commerce\Catalog\DTO\CreateBrandData;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Services\BrandService;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\DTO\SuggestHit;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\SearchSynonym;
use Commerce\Product\Services\ProductSearchIndexer;
use Commerce\Product\Services\ProductSuggestQuery;
use Commerce\Product\Services\SearchSynonymExpander;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Normalizer;
use Tests\TestCase;

final class ProductSuggestQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_or_empty_q_does_not_read_search_documents(): void
    {
        $this->product('Tee', 'SUGGEST-TEE-1');
        DB::flushQueryLog();
        DB::enableQueryLog();

        foreach (['', '  ', 't'] as $query) {
            app(ProductSuggestQuery::class)->suggest($query);
        }

        $this->assertFalse(collect(DB::getQueryLog())->contains(
            static fn (array $entry): bool => str_contains($entry['query'], 'search_documents'),
        ));
    }

    public function test_any_token_shorter_than_two_skips_search_documents(): void
    {
        $this->product('Classic Tee', 'SUGGEST-GATE-CLASSIC');
        DB::flushQueryLog();
        DB::enableQueryLog();

        foreach (['t e', 'classic t'] as $query) {
            $result = app(ProductSuggestQuery::class)->suggest($query);
            $this->assertSame([], $result->products);
            $this->assertSame([], $result->completions);
        }

        $this->assertFalse(collect(DB::getQueryLog())->contains(
            static fn (array $entry): bool => str_contains($entry['query'], 'search_documents'),
        ));
    }

    public function test_trimmed_two_letter_token_still_suggests(): void
    {
        $this->product('Tee', 'SUGGEST-TRIM-TE');
        $labels = array_map(
            static fn (SuggestHit $hit): string => $hit->label,
            app(ProductSuggestQuery::class)->suggest('  te  ')->products,
        );
        $this->assertContains('Tee', $labels);
    }

    public function test_and_tokens_match_classic_tee_via_whole_title_recall(): void
    {
        $this->product('Classic Tee', 'SUGGEST-AND-CLASSIC');

        $labels = array_map(
            static fn (SuggestHit $hit): string => $hit->label,
            app(ProductSuggestQuery::class)->suggest('classic te')->products,
        );
        $this->assertContains('Classic Tee', $labels);

        $miss = array_map(
            static fn (SuggestHit $hit): string => $hit->label,
            app(ProductSuggestQuery::class)->suggest('tee park')->products,
        );
        $this->assertNotContains('Classic Tee', $miss);
    }

    public function test_suggest_does_not_resolve_synonym_expander(): void
    {
        $this->product('Cotton Shirt', 'SUGGEST-COTTON-SHIRT');
        SearchSynonym::query()->create([
            'from_term' => 'tee',
            'to_term' => 'cotton',
        ]);
        $this->app->forgetInstance(SearchSynonymExpander::class);

        $labels = array_map(
            static fn (SuggestHit $hit): string => $hit->label,
            app(ProductSuggestQuery::class)->suggest('tee')->products,
        );
        $this->assertNotContains('Cotton Shirt', $labels);

        $constructor = (new \ReflectionClass(ProductSuggestQuery::class))->getConstructor();
        $this->assertTrue($constructor === null || $constructor->getNumberOfRequiredParameters() === 0);
    }

    public function test_product_suggest_query_has_no_cart_service_dependency(): void
    {
        $constructor = (new \ReflectionClass(ProductSuggestQuery::class))->getConstructor();

        $this->assertTrue($constructor === null || $constructor->getNumberOfRequiredParameters() === 0);
    }

    public function test_word_prefix_finds_classic_tee_and_rejects_substring_and_streetwear(): void
    {
        $this->product('Tee', 'SUGGEST-WP-TEE');
        $this->product('Classic Tee', 'SUGGEST-WP-CLASSIC');
        $this->product('Team Jersey', 'SUGGEST-WP-TEAM');
        $this->product('Streetwear', 'SUGGEST-WP-STREET');

        $tee = array_map(
            static fn (SuggestHit $hit): string => $hit->label,
            app(ProductSuggestQuery::class)->suggest('tee')->products,
        );
        $this->assertContains('Tee', $tee);
        $this->assertContains('Classic Tee', $tee);
        $this->assertNotContains('Team Jersey', $tee);

        $te = array_map(
            static fn (SuggestHit $hit): string => $hit->label,
            app(ProductSuggestQuery::class)->suggest('te')->products,
        );
        $this->assertContains('Classic Tee', $te);
        $this->assertNotContains('Streetwear', $te);

        $this->assertNotContains(
            'Team Jersey',
            array_map(
                static fn (SuggestHit $hit): string => $hit->label,
                app(ProductSuggestQuery::class)->suggest('eam')->products,
            ),
        );
        $this->assertNotContains(
            'Classic Tee',
            array_map(
                static fn (SuggestHit $hit): string => $hit->label,
                app(ProductSuggestQuery::class)->suggest('las')->products,
            ),
        );
    }

    public function test_product_recall_sql_is_not_whole_title_prefix_only(): void
    {
        $this->product('Classic Tee', 'SUGGEST-RECALL-CLASSIC');
        DB::flushQueryLog();
        DB::enableQueryLog();

        app(ProductSuggestQuery::class)->suggest('te');

        $productQuery = collect(DB::getQueryLog())->first(
            static fn (array $entry): bool => str_contains($entry['query'], 'search_documents'),
        );

        $this->assertNotNull($productQuery);
        $this->assertStringContainsString('suggest_documents.title like ? escape \'!\'', $productQuery['query']);
        $bindings = $productQuery['bindings'];
        $this->assertContains('%te%', $bindings);
    }

    public function test_product_query_escapes_like_wildcards_in_title_contains_pattern(): void
    {
        $this->product('\%_ Tee', 'SUGGEST-LIKE-WILDCARDS');
        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = app(ProductSuggestQuery::class)->suggest('\%_');

        $productQuery = collect(DB::getQueryLog())->first(
            static fn (array $entry): bool => str_contains($entry['query'], 'search_documents'),
        );

        $this->assertNotNull($productQuery);
        $this->assertStringContainsString('suggest_documents.title like ? escape \'!\'', $productQuery['query']);
        $this->assertContains('%!\\!%!_%', $productQuery['bindings']);
        $this->assertSame(['\%_ Tee'], array_map(
            static fn (SuggestHit $hit): string => $hit->label,
            $result->products,
        ));
    }

    public function test_product_query_accepts_nfd_indexed_title_for_nfc_query(): void
    {
        $product = $this->product('Été', 'SUGGEST-ETE-NFD');
        $nfdTitle = Normalizer::normalize('Été', Normalizer::FORM_D);
        $this->assertIsString($nfdTitle);
        DB::table('search_documents')
            ->where('document_id', $product->uuid)
            ->where('index_name', ProductSearchIndexer::INDEX)
            ->update(['title' => $nfdTitle]);

        $labels = array_map(
            static fn (SuggestHit $hit): string => $hit->label,
            app(ProductSuggestQuery::class)->suggest('ét')->products,
        );

        $this->assertContains($nfdTitle, $labels);
    }

    public function test_products_are_title_prefix_only_and_ignore_description(): void
    {
        $tee = $this->product('Tee', 'SUGGEST-TEE-2');
        $parka = $this->product('Parka', 'SUGGEST-PARKA-1');
        $parka->update(['description' => 'tee lining']);
        app(ProductSearchIndexer::class)->index($parka->fresh());

        $labels = array_map(fn (SuggestHit $hit) => $hit->label, app(ProductSuggestQuery::class)->suggest('te')->products);
        $this->assertContains('Tee', $labels);
        $this->assertNotContains('Parka', $labels);
    }

    public function test_completions_dedupe_after_text_normalize_and_shorter_names_win(): void
    {
        $this->product('Team Jersey', 'SUGGEST-TEAM');
        $this->product('Tee Shirt', 'SUGGEST-TEESHIRT');
        $this->product('Tee', 'SUGGEST-TEE-3');
        $this->product('TEE', 'SUGGEST-TEE-UPPER');

        $labels = array_map(fn (SuggestHit $hit) => $hit->label, app(ProductSuggestQuery::class)->suggest('te')->completions);
        $this->assertSame(['Tee', 'Tee Shirt', 'Team Jersey'], $labels);
    }

    public function test_completions_dedupe_across_product_and_brand_labels(): void
    {
        $this->product('Acme', 'SUGGEST-ACME-PRODUCT');
        app(BrandService::class)->create(new CreateBrandData(
            name: 'Acme',
            slug: 'acme',
            isActive: true,
        ));

        $result = app(ProductSuggestQuery::class)->suggest('ac');
        $labels = array_map(
            static fn (SuggestHit $hit): string => $hit->label,
            $result->completions,
        );

        $this->assertSame(['Acme'], $labels);
        $this->assertSame(
            route('storefront.shop.index', ['q' => 'Acme']),
            $result->completions[0]->url,
        );
    }

    public function test_tee_products_sort_and_team_jersey_is_not_a_tee_hit(): void
    {
        $this->product('Team Jersey', 'SUGGEST-SORT-TEAM');
        $this->product('Tee Shirt', 'SUGGEST-SORT-SHIRT');
        $this->product('Classic Tee', 'SUGGEST-SORT-CLASSIC');
        $this->product('Tee', 'SUGGEST-SORT-TEE');

        $labels = array_map(
            static fn (SuggestHit $hit): string => $hit->label,
            app(ProductSuggestQuery::class)->suggest('tee')->products,
        );

        $this->assertSame(['Tee', 'Tee Shirt', 'Classic Tee'], $labels);
    }

    public function test_sixth_prefix_product_is_omitted(): void
    {
        foreach (range(1, 6) as $i) {
            $this->product('Tea '.$i, 'SUGGEST-TEA-'.$i);
        }
        $this->assertCount(5, app(ProductSuggestQuery::class)->suggest('te')->products);
    }

    public function test_classic_tea_sixth_product_is_omitted(): void
    {
        foreach (range(1, 6) as $i) {
            $this->product('Classic Tea '.$i, 'SUGGEST-TEA-CAP-'.$i);
        }

        $this->assertCount(5, app(ProductSuggestQuery::class)->suggest('te')->products);
    }

    public function test_completion_url_uses_full_classic_tee_label(): void
    {
        $this->product('Classic Tee', 'SUGGEST-COMPLETION-URL');

        $hits = app(ProductSuggestQuery::class)->suggest('te')->completions;
        $classic = array_values(array_filter(
            $hits,
            static fn (SuggestHit $hit): bool => $hit->label === 'Classic Tee',
        ));

        $this->assertNotSame([], $classic);
        $this->assertSame(route('storefront.shop.index', ['q' => 'Classic Tee']), $classic[0]->url);
    }

    public function test_product_candidate_window_keeps_shorter_higher_id_title(): void
    {
        foreach (range(1, 100) as $i) {
            $this->product('Team Jersey '.$i, 'SUGGEST-WINDOW-'.$i);
        }
        $this->product('Tee', 'SUGGEST-WINDOW-TEE');

        $labels = array_map(
            static fn (SuggestHit $hit): string => $hit->label,
            app(ProductSuggestQuery::class)->suggest('te')->products,
        );

        $this->assertContains('Tee', $labels);
    }

    public function test_product_candidate_window_requires_every_query_token(): void
    {
        foreach (range(1, 100) as $i) {
            $this->product('Tea '.$i, 'SUGGEST-AND-WINDOW-'.$i);
        }
        $this->product('Classic Tee', 'SUGGEST-AND-WINDOW-CLASSIC');

        $labels = array_map(
            static fn (SuggestHit $hit): string => $hit->label,
            app(ProductSuggestQuery::class)->suggest('classic te')->products,
        );

        $this->assertContains('Classic Tee', $labels);
    }

    public function test_product_hit_uses_pdp_url_and_excludes_non_storefront_product(): void
    {
        $visible = $this->product('Tent', 'SUGGEST-TENT');
        $hidden = $this->product('Tempo', 'SUGGEST-TEMPO');
        $hidden->update(['visibility' => 'hidden']);
        app(ProductSearchIndexer::class)->index($hidden->fresh());

        $hits = app(ProductSuggestQuery::class)->suggest('te')->products;

        $this->assertSame(
            [['Tent', route('storefront.products.show', $visible->slug)]],
            array_map(static fn (SuggestHit $hit): array => [$hit->label, $hit->url], $hits),
        );
    }

    public function test_product_hit_includes_primary_image_url_when_media_exists(): void
    {
        $product = $this->product('Tee Shirt', 'SUGGEST-IMG-TEE');
        $mediaUuid = 'media-suggest-tee';
        ProductMedia::query()->create([
            'product_id' => $product->id,
            'media_uuid' => $mediaUuid,
            'position' => 0,
            'is_primary' => true,
        ]);

        $this->app->instance(MediaQueryServiceInterface::class, new class($mediaUuid) implements MediaQueryServiceInterface
        {
            public function __construct(private readonly string $uuid) {}

            public function findByUuid(string $uuid): ?object
            {
                return null;
            }

            public function getUrl(string $uuid, ?string $variant = null): ?string
            {
                return $uuid === $this->uuid ? 'https://cdn.example.test/tee.jpg' : null;
            }

            public function getSrcset(string $uuid): ?string
            {
                return null;
            }

            public function findByUuids(array $uuids): array
            {
                return [];
            }
        });
        $this->app->forgetInstance(ProductSuggestQuery::class);

        $hit = app(ProductSuggestQuery::class)->suggest('te')->products[0] ?? null;

        $this->assertNotNull($hit);
        $this->assertSame('Tee Shirt', $hit->label);
        $this->assertSame('https://cdn.example.test/tee.jpg', $hit->imageUrl);
    }

    public function test_product_hit_image_url_is_null_without_media(): void
    {
        $this->product('Tee No Image', 'SUGGEST-NO-IMG');

        $hit = app(ProductSuggestQuery::class)->suggest('te')->products[0] ?? null;

        $this->assertNotNull($hit);
        $this->assertNull($hit->imageUrl);
    }

    public function test_brand_hits_are_active_prefix_matches_with_shop_urls(): void
    {
        app(BrandService::class)->create(new CreateBrandData(
            name: 'Acme',
            slug: 'acme',
            isActive: true,
        ));
        app(BrandService::class)->create(new CreateBrandData(
            name: 'Acorn',
            slug: 'acorn',
            isActive: false,
        ));

        $result = app(ProductSuggestQuery::class)->suggest('ac');

        $this->assertSame(
            [['Acme', route('storefront.shop.index', ['brand' => 'acme'])]],
            array_map(static fn (SuggestHit $hit): array => [$hit->label, $hit->url], $result->brands),
        );
    }

    public function test_brand_word_prefix_matches_later_word_and_skips_inactive(): void
    {
        app(BrandService::class)->create(new CreateBrandData(
            name: 'Peak Performance',
            slug: 'peak-performance',
            isActive: true,
        ));
        app(BrandService::class)->create(new CreateBrandData(
            name: 'Peak Performance Labs',
            slug: 'peak-inactive',
            isActive: false,
        ));

        $result = app(ProductSuggestQuery::class)->suggest('per');

        $this->assertSame(
            [['Peak Performance', route('storefront.shop.index', ['brand' => 'peak-performance'])]],
            array_map(static fn (SuggestHit $hit): array => [$hit->label, $hit->url], $result->brands),
        );
    }

    public function test_category_hits_come_from_shop_filter_options_with_shop_urls(): void
    {
        $accessories = Category::query()->create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);
        Category::query()->create([
            'name' => 'Accents',
            'slug' => 'accents',
            'is_active' => false,
        ]);
        Category::query()->create([
            'name' => 'Accordion',
            'slug' => '',
            'is_active' => true,
        ]);
        $this->product('Accessory Hook', 'SUGGEST-ACC-1', [$accessories->id]);

        $categories = app(HomepageNavigationQuery::class)->shopFilterOptions();
        $result = app(ProductSuggestQuery::class)->suggest('ac', $categories);

        $this->assertSame(
            [['Accessories', route('storefront.shop.index', ['category' => 'accessories'])]],
            array_map(static fn (SuggestHit $hit): array => [$hit->label, $hit->url], $result->categories),
        );
    }

    public function test_category_word_prefix_uses_shop_filter_options_only(): void
    {
        $graphicTees = Category::query()->create([
            'name' => 'Graphic Tees',
            'slug' => 'graphic-tees',
            'is_active' => true,
        ]);
        Category::query()->create([
            'name' => 'Hidden Tees',
            'slug' => 'hidden-tees',
            'is_active' => false,
        ]);
        $this->product('Graphic Print', 'SUGGEST-TEE-CAT', [$graphicTees->id]);

        $categories = app(HomepageNavigationQuery::class)->shopFilterOptions();
        $result = app(ProductSuggestQuery::class)->suggest('te', $categories);

        $this->assertSame(
            [['Graphic Tees', route('storefront.shop.index', ['category' => 'graphic-tees'])]],
            array_map(static fn (SuggestHit $hit): array => [$hit->label, $hit->url], $result->categories),
        );
    }

    public function test_english_alias_swim_hits_visible_swim_leaves_only(): void
    {
        $this->visibleLeaf('ชุดว่ายน้ำเด็ก', 'kids-swimwear', 'SUGGEST-SWIM-1');
        $this->visibleLeaf('เสื้อว่ายน้ำเด็ก', 'kids-swim-shirts', 'SUGGEST-SWIM-2');

        $hits = $this->categoryHits('swim');

        $this->assertContains(
            ['ชุดว่ายน้ำเด็ก', route('storefront.shop.index', ['category' => 'kids-swimwear'])],
            $hits,
        );
        $this->assertContains(
            ['เสื้อว่ายน้ำเด็ก', route('storefront.shop.index', ['category' => 'kids-swim-shirts'])],
            $hits,
        );
    }

    public function test_english_alias_dress_hits_dresses_leaf(): void
    {
        $this->visibleLeaf('ชุดเดรส', 'kids-dresses', 'SUGGEST-DRESS-1');

        $this->assertSame(
            [['ชุดเดรส', route('storefront.shop.index', ['category' => 'kids-dresses'])]],
            $this->categoryHits('dress'),
        );
    }

    public function test_english_alias_toy_hits_toys_leaf_not_parent(): void
    {
        $parent = Category::query()->create([
            'name' => 'ของเล่นและเครื่องนอนการตกแต่ง',
            'slug' => 'item-4',
            'is_active' => true,
        ]);
        $toys = Category::query()->create([
            'name' => 'ของเล่น',
            'slug' => 'toys',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);
        $this->product('Skill toys', 'SUGGEST-TOY-1', [$toys->id]);

        $hits = $this->categoryHits('toy');

        $this->assertSame(
            [['ของเล่น', route('storefront.shop.index', ['category' => 'toys'])]],
            $hits,
        );
        $this->assertNotContains(
            'ของเล่นและเครื่องนอนการตกแต่ง',
            array_column($hits, 0),
        );
    }

    public function test_english_alias_book_and_shoes_hit_leaves(): void
    {
        $this->visibleLeaf('หนังสือเด็ก', 'kids-books', 'SUGGEST-BOOK-1');
        $this->visibleLeaf('รองเท้า', 'kids-shoes', 'SUGGEST-SHOE-1');

        $this->assertSame(
            [['หนังสือเด็ก', route('storefront.shop.index', ['category' => 'kids-books'])]],
            $this->categoryHits('book'),
        );
        $this->assertSame(
            [['รองเท้า', route('storefront.shop.index', ['category' => 'kids-shoes'])]],
            $this->categoryHits('shoes'),
        );
    }

    public function test_thai_infix_matches_swimwear_and_dress_names(): void
    {
        $this->visibleLeaf('ชุดว่ายน้ำเด็ก', 'kids-swimwear', 'SUGGEST-INFIX-SWIM');
        $this->visibleLeaf('ชุดเดรส', 'kids-dresses', 'SUGGEST-INFIX-DRESS');

        $this->assertSame(
            [['ชุดว่ายน้ำเด็ก', route('storefront.shop.index', ['category' => 'kids-swimwear'])]],
            $this->categoryHits('ว่ายน้ำ'),
        );
        $this->assertSame(
            [['ชุดเดรส', route('storefront.shop.index', ['category' => 'kids-dresses'])]],
            $this->categoryHits('เดรส'),
        );
    }

    public function test_dress_alias_skips_empty_or_inactive_dresses_leaf(): void
    {
        Category::query()->create([
            'name' => 'ชุดเดรส',
            'slug' => 'kids-dresses',
            'is_active' => true,
        ]);
        Category::query()->create([
            'name' => 'ชุดเดรสซ่อน',
            'slug' => 'kids-dresses-hidden',
            'is_active' => false,
        ]);

        $this->assertSame([], $this->categoryHits('dress'));
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function categoryHits(string $q): array
    {
        $categories = app(HomepageNavigationQuery::class)->shopFilterOptions();
        $result = app(ProductSuggestQuery::class)->suggest($q, $categories);

        return array_map(
            static fn (SuggestHit $hit): array => [$hit->label, $hit->url],
            $result->categories,
        );
    }

    private function visibleLeaf(string $name, string $slug, string $sku): Category
    {
        $category = Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);
        $this->product($name.' product', $sku, [$category->id]);

        return $category;
    }

    /**
     * @param  list<int>  $categoryIds
     */
    private function product(string $name, string $sku, array $categoryIds = []): Product
    {
        $product = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: $name,
            status: 'published',
            visibility: 'public',
            sku: $sku,
            price: 2500,
            categoryIds: $categoryIds,
        ));

        $variant = $product->defaultVariant();
        $this->assertNotNull($variant);
        app(InventoryServiceInterface::class)->receive($variant->uuid, 5);
        app(ProductSearchIndexer::class)->index($product->fresh());

        return $product->fresh();
    }
}
