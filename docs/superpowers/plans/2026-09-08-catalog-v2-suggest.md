# Catalog V2 Phase 3 Suggest Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prefix typeahead on the existing search overlay (completions, products, brands, categories) without changing Phase 2 listing.

**Architecture:** `ProductSuggestQuery` matches `SearchNormalizer::textNormalize($q)` as a prefix on names only. Products read `search_documents.title` + `visibleOnStorefront()`. Brands from `brands`. Categories from `HomepageNavigationQuery::shopFilterOptions()`. A public JSON route feeds overlay JS. Never call `ProductDiscoveryQuery`.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, existing `search_documents`, storefront overlay JS

**Spec:** `docs/superpowers/specs/2026-09-08-catalog-v2-suggest-design.md` (Locked)

**Start gate:** Catalog V2 Phase 2 Complete on `main` (`92a152f`). Work on `feat/catalog-v2-suggest-v1`.

## Global Constraints

- Do not change Phase 2 listing, ranking, facets, synonyms, or empty-`q` browse.
- Do not call `ProductDiscoveryQuery` from suggest.
- No Meilisearch, Typesense, `suggest_index` table, query log, SKU completion rows, or merchandising.
- Synonyms stay listing-only.
- `constrainInStock()`, PDP combination rules, and reserved URL params are unchanged.
- Human gate after the wave. Do not start follow-up tickets in this plan.

---

## File map

| Area | Files |
|---|---|
| Result DTO | Create `modules/Product/src/DTO/SuggestHit.php`, `SuggestResult.php` |
| Query | Create `modules/Product/src/Services/ProductSuggestQuery.php` |
| HTTP | Create `modules/Cart/src/Http/Controllers/SuggestController.php`; modify `modules/Cart/routes/web.php` |
| Overlay | Modify `resources/views/components/storefront/navigation/search-overlay.blade.php`, `resources/js/storefront/header.js` |
| Tests | `tests/Feature/Product/ProductSuggestQueryTest.php`, `tests/Feature/Storefront/StorefrontSuggestTest.php` |

---

## Shared helpers (use in tests)

Sort every group: shorter `mb_strlen($name)` first, then `strnatcasecmp` on name. Completions unique by `SearchNormalizer::textNormalize($label)` keeping the first after that sort.

Prefix match: `str_starts_with(SearchNormalizer::textNormalize($name), $prefix)` where `$prefix = SearchNormalizer::textNormalize($q)` and `mb_strlen($prefix) >= 2`.

Cap 5 after sort/dedupe.

---

### Task 1: ProductSuggestQuery (title-only prefix)

**Files:**
- Create: `modules/Product/src/DTO/SuggestHit.php`
- Create: `modules/Product/src/DTO/SuggestResult.php`
- Create: `modules/Product/src/Services/ProductSuggestQuery.php`
- Test: `tests/Feature/Product/ProductSuggestQueryTest.php`

**Interfaces:**
- Produces: `ProductSuggestQuery::suggest(string $q): SuggestResult`
- `SuggestHit(string $label, string $url)`
- `SuggestResult` with `array $completions`, `$products`, `$brands`, `$categories` (each `list<SuggestHit>`)
- Inject `HomepageNavigationQuery` for categories. Do not inject `ProductDiscoveryQuery`.

- [ ] **Step 1: Failing tests**

```php
public function test_short_or_empty_q_does_not_read_search_documents(): void
{
    $this->product('Tee', 'SUGGEST-TEE-1');
    DB::flushQueryLog();
    DB::enableQueryLog();
    app(ProductSuggestQuery::class)->suggest('t');
    $this->assertFalse(collect(DB::getQueryLog())->contains(
        static fn (array $entry): bool => str_contains($entry['query'], 'search_documents'),
    ));
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
```

Product helper: published + in-stock + `ProductSearchIndexer::index`. Brands/categories: create active brand/category, assert URL `/shop?brand={slug}` and `/shop?category={slug}`. Categories must come from `shopFilterOptions()` (active + slug), not an unpublished/hidden category.

- [ ] **Step 2: Run, fail** — `php vendor/bin/phpunit tests/Feature/Product/ProductSuggestQueryTest.php`

- [ ] **Step 3: Implement.** Match in PHP with `textNormalize` + `str_starts_with` (NFC-safe). SQL `LIKE` may prefilter. Product URL = `route('storefront.products.show', $slug)`. Completion URL = `route('storefront.shop.index', ['q' => $label])`. Sort then unique completions then cap.

- [ ] **Step 4: Pass tests.**

- [ ] **Step 5: Commit** `feat: add prefix product suggest query`

---

### Task 2: JSON endpoint (no discovery)

**Files:**
- Create: `modules/Cart/src/Http/Controllers/SuggestController.php`
- Modify: `modules/Cart/routes/web.php` — `GET /shop/suggest` named `storefront.suggest` (sibling of `/shop`, not a `{slug}`)
- Test: `tests/Feature/Storefront/StorefrontSuggestTest.php`

**Interfaces:**
- Consumes: `ProductSuggestQuery::suggest`
- `GET /shop/suggest?q=` returns `200` JSON (not wrapped in `data`):

```json
{
  "completions": [{"label": "Tee", "url": "..."}],
  "products": [{"label": "Tee", "url": "..."}],
  "brands": [{"label": "Acme", "url": "..."}],
  "categories": [{"label": "T-shirts", "url": "..."}]
}
```

- [ ] **Step 1: Failing tests**

```php
public function test_suggest_http_never_calls_product_discovery_query(): void
{
    $this->mock(ProductDiscoveryQuery::class, function ($mock): void {
        $mock->shouldNotReceive('candidateUuids');
    });
    $this->product('Tee', 'HTTP-TEE-1');
    $this->getJson(route('storefront.suggest', ['q' => 'te']))
        ->assertOk()
        ->assertJsonPath('products.0.label', 'Tee');
}

public function test_short_q_is_empty_json_and_skips_search_documents(): void
{
    $this->product('Tee', 'HTTP-TEE-2');
    DB::enableQueryLog();
    $this->getJson(route('storefront.suggest', ['q' => 't']))
        ->assertOk()
        ->assertExactJson([
            'completions' => [],
            'products' => [],
            'brands' => [],
            'categories' => [],
        ]);
    $this->assertFalse(collect(DB::getQueryLog())->contains(
        static fn (array $entry): bool => str_contains($entry['query'], 'search_documents'),
    ));
}

public function test_shop_listing_q_te_still_uses_phase_2_exact_token(): void
{
    $cotton = $this->product('Cotton Parka', 'HTTP-COTTON-1');
    $this->get(route('storefront.shop.index', ['q' => 'cot']))
        ->assertOk()
        ->assertDontSee($cotton->name);
}
```

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Implement controller** — missing `q` treated as `''`. Always 200. Do not type-hint `ProductDiscoveryQuery` on the controller.

- [ ] **Step 4: Pass** plus `php vendor/bin/phpunit tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php`

- [ ] **Step 5: Commit** `feat: expose storefront suggest json endpoint`

---

### Task 3: Overlay wiring

**Files:**
- Modify: `resources/views/components/storefront/navigation/search-overlay.blade.php` — `data-suggest-url="{{ route('storefront.suggest') }}"`, empty `<div data-search-results hidden></div>` beside `[data-search-hints]`
- Modify: `resources/js/storefront/header.js` — `bindSearchAutocomplete` reads `dataset.suggestUrl` (not shop `searchUrl`). Render **completions** first, then products, brands, categories. Item shape `{label, url}` (escape `label`). Cap/limit query param is not required (server caps at 5). Drop `collections` and `price_label` / required thumbnails. Keep debounce; existing 300ms is fine (spec ≈200ms). On fetch failure, `resetResults()` without closing overlay.
- Test: extend `tests/Feature/Storefront/StorefrontShopFilterChromeTest.php` or `StorefrontSuggestTest` — shop HTML contains `data-suggest-url` and `data-search-results`. Isolation test that currently lists header fields stays green.

**Interfaces:**
- Consumes: Task 2 JSON. Enter / form submit still `GET /shop?q=`.

- [ ] **Step 1: Failing assertion** that overlay markup includes `data-suggest-url` pointing at `storefront.suggest`.

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Wire Blade + JS.** Hide `[data-search-hints]` when any suggest group is shown. `< 2` chars shows hints again. Product links are PDP URLs from JSON (plain `<a>` with `label` is enough; placeholder thumb optional).

- [ ] **Step 4: Pass** `php vendor/bin/phpunit tests/Feature/Storefront/StorefrontSuggestTest.php tests/Feature/Storefront/StorefrontShopFilterChromeTest.php tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php tests/Unit/Storefront/Ws002HeaderIsolationTest.php tests/Feature/Storefront/Ws002HeaderContractTest.php`

- [ ] **Step 5: Commit** `feat: wire search overlay to prefix suggest`

**Stop for human Wave / Phase 3 review.**

---

## Spec coverage (plan self-review)

| Spec | Task |
|---|---|
| Min length / no `search_documents` | 1, 2 |
| Title-only products | 1 |
| Completions distinct after `textNormalize` | 1 |
| Shorter-then-alpha order | 1 |
| Categories = `shopFilterOptions()` | 1 |
| Cap 5 | 1 |
| Never `ProductDiscoveryQuery` | 2 |
| JSON + overlay click URLs | 2, 3 |
| Enter = Phase 2 listing | 2, 3 |
| Overlay empty = popular + recent | 3 |

## Out of this plan

Synonym-aware suggest, SKU rows, query log, Meilisearch, collections group, Phase 2 follow-up tickets.
