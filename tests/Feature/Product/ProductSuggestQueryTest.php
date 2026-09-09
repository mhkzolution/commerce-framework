<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Cart\Services\HomepageNavigationQuery;
use Commerce\Catalog\DTO\CreateBrandData;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Services\BrandService;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\DTO\SuggestHit;
use Commerce\Product\Models\Product;
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

    public function test_product_query_prefilters_search_documents_by_title_prefix(): void
    {
        $this->product('Tee', 'SUGGEST-TEE-PREFILTER');
        DB::flushQueryLog();
        DB::enableQueryLog();

        app(ProductSuggestQuery::class)->suggest('te');

        $productQuery = collect(DB::getQueryLog())->first(
            static fn (array $entry): bool => str_contains($entry['query'], 'search_documents'),
        );

        $this->assertNotNull($productQuery);
        $this->assertStringContainsString('suggest_documents.title like ? escape \'!\'', $productQuery['query']);
        $this->assertContains('te%', $productQuery['bindings']);
    }

    public function test_product_query_escapes_like_wildcards_in_title_prefix(): void
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
        $this->assertContains('!\\!%!_%', $productQuery['bindings']);
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

    public function test_sixth_prefix_product_is_omitted(): void
    {
        foreach (range(1, 6) as $i) {
            $this->product('Tea '.$i, 'SUGGEST-TEA-'.$i);
        }
        $this->assertCount(5, app(ProductSuggestQuery::class)->suggest('te')->products);
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

    public function test_category_hits_come_from_shop_filter_options_with_shop_urls(): void
    {
        Category::query()->create([
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

        $categories = app(HomepageNavigationQuery::class)->shopFilterOptions();
        $result = app(ProductSuggestQuery::class)->suggest('ac', $categories);

        $this->assertSame(
            [['Accessories', route('storefront.shop.index', ['category' => 'accessories'])]],
            array_map(static fn (SuggestHit $hit): array => [$hit->label, $hit->url], $result->categories),
        );
    }

    private function product(string $name, string $sku): Product
    {
        $product = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: $name,
            status: 'published',
            visibility: 'public',
            sku: $sku,
            price: 2500,
        ));

        $variant = $product->defaultVariant();
        $this->assertNotNull($variant);
        app(InventoryServiceInterface::class)->receive($variant->uuid, 5);
        app(ProductSearchIndexer::class)->index($product->fresh());

        return $product->fresh();
    }
}
