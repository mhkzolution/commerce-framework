# Catalog V2 Suggest V1.1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Change storefront suggest from whole-string name prefix to whitespace word-prefix with AND and a per-token length gate, without changing ranking, merchandising, synonyms, rebuild, or the search engine.

**Architecture:** `ProductSuggestQuery` tokenizes with `SearchNormalizer::tokenize`. PHP `matchesName()` is the match source of truth. Product SQL only recalls possible titles. Brands and categories stay table / `shopFilterOptions()` then the same PHP matcher. Completions union those labels and dedupe by `textNormalize` across groups.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, existing `search_documents` + overlay `GET /shop/suggest`

**Spec:** `docs/superpowers/specs/2026-09-09-catalog-v2-suggest-v1.1-design.md` (Locked)

**Start gate:** Locked spec. Work on `feat/catalog-v2-suggest-v1.1`.

## Global Constraints

- Do not change Discovery ranking, merchandising, synonym expansion (`SearchSynonymExpander` / Octane), search index rebuild lifecycle, or search engine replacement — even slightly.
- Product rows: `search_documents.title` + `visibleOnStorefront()` only.
- PHP word-prefix is SoT. Product SQL is recall only.
- Categories: filter after `HomepageNavigationQuery::shopFilterOptions()`. No `search_documents` lookup for categories.
- Completions: union of product / brand / category labels; distinct by `textNormalize` across groups; URL `/shop?q={full label}`.
- Cap 5 per group. Sort shorter `mb_strlen(label)` then `strnatcasecmp`.
- Never call `ProductDiscoveryQuery` from suggest. Do not `expand()`.
- Human gates: Task 1 review → Task 2 review → Task 3 review → whole-branch review.

---

## File map

| Area | Files |
|---|---|
| Matcher | `modules/Product/src/Services/ProductSuggestQuery.php` |
| HTTP overlay | `modules/Cart/src/Http/Controllers/SuggestController.php` (regression only; no new UI) |
| Tests | `tests/Feature/Product/ProductSuggestQueryTest.php`, `tests/Feature/Storefront/StorefrontSuggestTest.php` |
| Suggest V1 | `docs/superpowers/specs/2026-09-08-catalog-v2-suggest-design.md` (Task 1) |

Do not edit `ProductDiscoveryQuery`, `ProductSearchIndexer::rebuild`, `SearchSynonymExpander`, or overlay JS.

---

### Task 1: Word-prefix matcher + token gate + Suggest V1 amendment

**Files:**
- Modify: `modules/Product/src/Services/ProductSuggestQuery.php`
- Modify: `tests/Feature/Product/ProductSuggestQueryTest.php`
- Modify: `docs/superpowers/specs/2026-09-08-catalog-v2-suggest-design.md`

**Interfaces:**
- Consumes: `ProductSuggestQuery::suggest(string $q, iterable $categories = []): SuggestResult`
- Produces: `tokenize` gate (any token `mb_strlen` < 2 → empty, no `search_documents`); PHP `matchesName($label, list<string> $queryTokens): bool` used on product titles after recall. Product SQL may stay V1 whole-title `LIKE` until Task 2.

Do not add `SearchSynonymExpander` to this class. Do not change overlay JS.

- [ ] **Step 1: Failing tests (gate + matcher + isolation)**

Add to `tests/Feature/Product/ProductSuggestQueryTest.php`:

```php
use Commerce\Product\Models\SearchSynonym;
use Commerce\Product\Services\SearchSynonymExpander;

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
```

Keep `test_short_or_empty_q_does_not_read_search_documents` green. Do **not** yet assert `Classic Tee` for `q=te` (that needs Task 2 recall).

- [ ] **Step 2: Run (RED)**

```bash
php artisan test --compact tests/Feature/Product/ProductSuggestQueryTest.php
```

Expected: `test_any_token_shorter_than_two_skips_search_documents` FAIL (`classic t` currently queries because whole-string length ≥ 2). AND / synonym tests may already pass on titles SQL can see.

- [ ] **Step 3: Implement matcher + gate**

In `ProductSuggestQuery::suggest`:

```php
public function suggest(string $q, iterable $categories = []): SuggestResult
{
    $tokens = SearchNormalizer::tokenize($q);

    if ($tokens === [] || $this->hasShortToken($tokens)) {
        return new SuggestResult;
    }

    $products = $this->productCandidates($tokens);
    $brands = $this->brandCandidates($tokens);
    $categoryCandidates = $this->categoryCandidates($tokens, $categories);
    $completionCandidates = array_merge($products, $brands, $categoryCandidates);

    return new SuggestResult(
        completions: $this->completionHits($completionCandidates),
        products: $this->hits($products),
        brands: $this->hits($brands),
        categories: $this->hits($categoryCandidates),
    );
}

/**
 * @param  list<string>  $tokens
 */
private function hasShortToken(array $tokens): bool
{
    foreach ($tokens as $token) {
        if (mb_strlen($token) < 2) {
            return true;
        }
    }

    return false;
}

/**
 * @param  list<string>  $queryTokens
 */
private function matchesName(string $label, array $queryTokens): bool
{
    $nameTokens = SearchNormalizer::tokenize($label);

    foreach ($queryTokens as $queryToken) {
        $hit = false;

        foreach ($nameTokens as $nameToken) {
            if (str_starts_with($nameToken, $queryToken)) {
                $hit = true;
                break;
            }
        }

        if (! $hit) {
            return false;
        }
    }

    return true;
}
```

Keep product SQL on V1 `title LIKE 'joined-or-first-token%'` for this task: build patterns from `implode(' ', $tokens)` **or** still use `textNormalize($q)` only inside `titlePrefilterPatterns` so existing `te%` test stays green. After SQL fetch, replace `hasPrefix($title, $prefix)` with `$this->matchesName($title, $tokens)`.

Leave `brandCandidates` / `categoryCandidates` calling `hasPrefix` **or** switch them to `matchesName` if that is a one-line swap — Task 2 still owns brand/category fixtures and overlay. Prefer switching them to `matchesName($name, $tokens)` now so they cannot drift; Task 2 adds the Peak Performance / Graphic Tees tests and SQL recall.

Delete unused whole-string `hasPrefix` if nothing calls it.

- [ ] **Step 4: Amend Suggest V1 in-place**

Edit `docs/superpowers/specs/2026-09-08-catalog-v2-suggest-design.md` at all targets in spec §5.1.

**§2 goal 2** — replace “Prefix match on **names**” so it means word prefix on name tokens, not substring inside a token, not listing tokens, not SKU rows.

**§3 Min length** — replace with: `SearchNormalizer::tokenize(q)`. If any token has length < 2 (or there are no tokens), empty groups and no `search_documents` read. Whitespace-only is empty.

**§3 Match** — replace `normalized LIKE 'prefix%'` as SoT with: case-insensitive word prefix; each query token prefixes some name token; product SQL `LIKE` is recall only.

**§3 Products / Brands / Categories / Completions** — word-prefix + AND; products title-only; categories from `shopFilterOptions()` then PHP; completions distinct by `textNormalize` across groups.

**§5 Query** — replace the whole-string flow. **Delete** the line `Do not tokenize.` Keep: do not expand synonyms; do not score with Phase 2 field ranks; do not scan payload.skus / description / attribute labels; do not call `ProductDiscoveryQuery`.

**§7** — keep Parka title-only, listing `cot`, throwing discovery fake. Add: token gate including `classic t`; word-prefix `Classic Tee` for `te` (document now; code in Task 2); AND; Streetwear SoT; cross-group completion dedupe.

Scan:

```bash
rg -n "Do not tokenize|LIKE 'prefix%'|Length \*\*< 2\*\*" \
  docs/superpowers/specs/2026-09-08-catalog-v2-suggest-design.md
```

Expected: no leftover whole-string-only match SoT. Synonym off stays.

- [ ] **Step 5: Re-run**

```bash
php artisan test --compact tests/Feature/Product/ProductSuggestQueryTest.php
```

Expected: PASS. Existing `te%` prefilter test still green.

- [ ] **Step 6: Commit**

```bash
git add \
  modules/Product/src/Services/ProductSuggestQuery.php \
  tests/Feature/Product/ProductSuggestQueryTest.php \
  docs/superpowers/specs/2026-09-08-catalog-v2-suggest-design.md
git commit -m "$(cat <<'EOF'
feat: word-prefix suggest matcher and token gate

EOF
)"
```

- [ ] **Step 7: Human gate — stop**

---

### Task 2: Product recall query + brand/category matcher + overlay regression

**Files:**
- Modify: `modules/Product/src/Services/ProductSuggestQuery.php`
- Modify: `tests/Feature/Product/ProductSuggestQueryTest.php`
- Modify: `tests/Feature/Storefront/StorefrontSuggestTest.php`

**Interfaces:**
- Consumes: `matchesName()` and token gate from Task 1.
- Produces: product SQL recall that can return `Classic Tee` for `te`; PHP still drops `Streetwear`; brand `Peak Performance` for `per`; category `Graphic Tees` for `te` from `shopFilterOptions()` only. Overlay HTTP still never resolves `ProductDiscoveryQuery`.

- [ ] **Step 1: Failing tests**

Add to `ProductSuggestQueryTest.php`:

```php
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
    $this->assertTrue(
        collect($bindings)->contains(static fn (mixed $binding): bool => is_string($binding) && str_contains($binding, 'te')),
    );
    $this->assertFalse(
        collect($bindings)->contains(static fn (mixed $binding): bool => $binding === 'te%' && count($bindings) === 1),
        'Recall must not be only whole-title te%.',
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

public function test_category_word_prefix_uses_shop_filter_options_only(): void
{
    Category::query()->create([
        'name' => 'Graphic Tees',
        'slug' => 'graphic-tees',
        'is_active' => true,
    ]);
    Category::query()->create([
        'name' => 'Hidden Tees',
        'slug' => 'hidden-tees',
        'is_active' => false,
    ]);

    $categories = app(HomepageNavigationQuery::class)->shopFilterOptions();
    $result = app(ProductSuggestQuery::class)->suggest('te', $categories);

    $this->assertSame(
        [['Graphic Tees', route('storefront.shop.index', ['category' => 'graphic-tees'])]],
        array_map(static fn (SuggestHit $hit): array => [$hit->label, $hit->url], $result->categories),
    );
}
```

Replace `test_product_query_prefilters_search_documents_by_title_prefix` so it no longer requires bindings `['te%']` as the only pattern. Keep `test_product_query_escapes_like_wildcards_in_title_prefix` (escape still required on recall patterns).

Add to `StorefrontSuggestTest.php`:

```php
public function test_suggest_http_returns_classic_tee_for_word_prefix(): void
{
    $product = $this->product('Classic Tee', 'HTTP-CLASSIC-TEE');

    $this->getJson(route('storefront.suggest', ['q' => 'te']))
        ->assertOk()
        ->assertJsonPath('products.0.label', 'Classic Tee')
        ->assertJsonPath('products.0.url', route('storefront.products.show', $product->slug))
        ->assertJsonPath('completions.0.url', route('storefront.shop.index', ['q' => 'Classic Tee']));
}
```

If another shorter product exists in that test DB, do not assume `products.0` is Classic Tee — assert `assertJsonFragment` on label/url instead.

Keep `test_suggest_http_never_calls_product_discovery_query` and `test_shop_listing_q_cot_still_uses_phase_2_exact_token`.

- [ ] **Step 2: Run (RED)**

```bash
php artisan test --compact \
  tests/Feature/Product/ProductSuggestQueryTest.php \
  tests/Feature/Storefront/StorefrontSuggestTest.php
```

Expected: Classic Tee / `te` FAIL until recall is not `te%` only.

- [ ] **Step 3: Product recall SQL**

Change `titlePrefilterPatterns` to accept `list<string> $tokens`. For each token, emit escaped NFC and NFD forms as both `{token}%` (start) and `%{token}%` (contains, so `Classic Tee` is recalled). PHP `matchesName` still drops `Streetwear` for `te`.

```php
private function titlePrefilterPatterns(array $tokens): array
{
    $patterns = [];

    foreach ($tokens as $token) {
        foreach ($this->unicodeForms($token) as $form) {
            $escaped = str_replace(
                ['!', '\\', '%', '_'],
                ['!!', '!\\', '!%', '!_'],
                $form,
            );
            $patterns[] = $escaped.'%';
            $patterns[] = '%'.$escaped.'%';
        }
    }

    return array_values(array_unique($patterns));
}

/**
 * @return list<string>
 */
private function unicodeForms(string $token): array
{
    $forms = [$token];
    $nfd = Normalizer::normalize($token, Normalizer::FORM_D);

    if (is_string($nfd)) {
        $forms[] = mb_strtolower($nfd);
    }

    return array_values(array_unique($forms));
}
```

`productCandidates`: `where` OR across those patterns; still `limit(PRODUCT_CANDIDATE_LIMIT)`; still `matchesName` after fetch.

`brandCandidates` / `categoryCandidates`: `matchesName($name, $tokens)` only. No `search_documents` in those methods.

- [ ] **Step 4: Re-run overlay regression**

```bash
php artisan test --compact \
  tests/Feature/Product/ProductSuggestQueryTest.php \
  tests/Feature/Storefront/StorefrontSuggestTest.php \
  tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php
```

Expected: PASS. Listing `cot` still misses Cotton. Suggest still does not resolve `ProductDiscoveryQuery`.

- [ ] **Step 5: Commit**

```bash
git add \
  modules/Product/src/Services/ProductSuggestQuery.php \
  tests/Feature/Product/ProductSuggestQueryTest.php \
  tests/Feature/Storefront/StorefrontSuggestTest.php
git commit -m "$(cat <<'EOF'
feat: recall word-prefix product titles for suggest

EOF
)"
```

- [ ] **Step 6: Human gate — stop**

---

### Task 3: Cross-group completion dedupe + sort/cap + acceptance sweep

**Files:**
- Modify: `modules/Product/src/Services/ProductSuggestQuery.php` (`completionHits` only if a test fails)
- Modify: `tests/Feature/Product/ProductSuggestQueryTest.php`

**Interfaces:**
- Consumes: matching product / brand / category candidate lists from Tasks 1–2.
- Produces: completions unique by `textNormalize` across groups; cap 5 after that sort; `q=te` completion order `Tee`, `Tee Shirt`, `Team Jersey`; `q=tee` product order `Tee`, `Tee Shirt`, `Classic Tee`.

V1 `completionHits` already dedupes by `textNormalize` on the merged array. Task 3 **locks** cross-group (product + brand same label) and sort/cap examples. If tests pass with no PHP change, that is allowed — do not add ranking.

- [ ] **Step 1: Failing (or locking) tests**

Add to `ProductSuggestQueryTest.php`:

```php
public function test_completions_dedupe_across_product_and_brand_labels(): void
{
    $this->product('Acme', 'SUGGEST-ACME-PRODUCT');
    app(BrandService::class)->create(new CreateBrandData(
        name: 'Acme',
        slug: 'acme',
        isActive: true,
    ));

    $labels = array_map(
        static fn (SuggestHit $hit): string => $hit->label,
        app(ProductSuggestQuery::class)->suggest('ac')->completions,
    );

    $this->assertSame(['Acme'], $labels);
    $this->assertSame(
        route('storefront.shop.index', ['q' => 'Acme']),
        app(ProductSuggestQuery::class)->suggest('ac')->completions[0]->url,
    );
}

public function test_tee_product_sort_and_team_jersey_is_not_a_tee_hit(): void
{
    $this->product('Team Jersey', 'SUGGEST-SORT-TEAM');
    $this->product('Tee Shirt', 'SUGGEST-SORT-SHIRT');
    $this->product('Classic Tee', 'SUGGEST-SORT-CLASSIC');
    $this->product('Tee', 'SUGGEST-SORT-TEE');

    $tee = array_map(
        static fn (SuggestHit $hit): string => $hit->label,
        app(ProductSuggestQuery::class)->suggest('tee')->products,
    );
    $this->assertSame(['Tee', 'Tee Shirt', 'Classic Tee'], $tee);

    $teCompletions = array_map(
        static fn (SuggestHit $hit): string => $hit->label,
        app(ProductSuggestQuery::class)->suggest('te')->completions,
    );
    $this->assertSame(['Tee', 'Tee Shirt', 'Team Jersey'], array_slice($teCompletions, 0, 3));
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
```

Keep `test_completions_dedupe_after_text_normalize_and_shorter_names_win` (`Tee` / `TEE` / `Tee Shirt` / `Team Jersey` for `te`). Keep `test_sixth_prefix_product_is_omitted` or retire it if it duplicates the Classic Tea cap.

- [ ] **Step 2: Run**

```bash
php artisan test --compact tests/Feature/Product/ProductSuggestQueryTest.php
```

Expected: FAIL only if completionHits is not already cross-group unique. Sort assertions FAIL if order is wrong.

- [ ] **Step 3: Implement only if red**

`completionHits` already: `sortCandidates` then unique `textNormalize`, cap 5, URL `q` = raw label. Do not add field rank. If product `Acme` and brand `Acme` produce two completions, unique by normalize before cap.

- [ ] **Step 4: Acceptance sweep**

```bash
php artisan test --compact \
  tests/Feature/Product/ProductSuggestQueryTest.php \
  tests/Feature/Storefront/StorefrontSuggestTest.php \
  tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php \
  tests/Feature/Storefront/ShopDiscoveryListingTest.php
```

Expected: PASS. Diff must not include ranking, expander, indexer rebuild, or search-engine files.

Scan leftover V1 wording again:

```bash
rg -n "Do not tokenize|whole-string|LIKE 'prefix%'" \
  docs/superpowers/specs/2026-09-08-catalog-v2-suggest-design.md
```

- [ ] **Step 5: Commit**

```bash
git add \
  modules/Product/src/Services/ProductSuggestQuery.php \
  tests/Feature/Product/ProductSuggestQueryTest.php
git commit -m "$(cat <<'EOF'
test: lock suggest completion dedupe sort and cap

EOF
)"
```

- [ ] **Step 6: Human gate — stop**

---

## Whole-branch review

```bash
php artisan test --compact \
  tests/Feature/Product/ProductSuggestQueryTest.php \
  tests/Feature/Storefront/StorefrontSuggestTest.php \
  tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php \
  tests/Feature/Storefront/ShopDiscoveryListingTest.php
```

```text
Task 1 → Task 2 → Task 3 → whole-branch review → merge
```

---

## Spec coverage

| Spec | Task |
|---|---|
| Token gate; no SQL on short token | Task 1 |
| PHP word-prefix + AND | Task 1 matcher; Task 2 product recall for `te` / `Classic Tee` |
| Suggest V1 in-place §2 / §3 / §5 / §7 | Task 1 |
| SQL recall; Streetwear SoT; brand/category; overlay HTTP | Task 2 |
| Cross-group completion; sort; cap 5; URL full label | Task 3 |
| No ranking / merchandising / expander / rebuild / engine | Global constraints |
