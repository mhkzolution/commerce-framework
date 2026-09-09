# Catalog V2 Ranking V1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Order Discovery candidates by highest matched field, then distinct expanded-token coverage in that field, then title — exact SKU still first and never compared on coverage — then slice 500.

**Architecture:** Keep matching and the AND set in `ProductDiscoveryQuery::candidateUuids`. Extend the `usort` comparator only. Coverage is `|distinct(expand(tokenize(q))) ∩ winning-field tokens|` for `fieldRank >= 2`. Rank-1 pairs compare `title` only. Cap stays `array_slice` after that sort. Listing default sort still uses `candidateUuids` CASE order; `price_asc` / `price_desc` still ignore that order.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, existing `search_documents` + `SearchSynonymExpander`

**Spec:** `docs/superpowers/specs/2026-09-09-catalog-v2-ranking-v1-design.md` (Locked)

**Start gate:** Locked spec. Work on `feat/catalog-v2-ranking-v1`.

## Global Constraints

- Ranking only. Do not add merchandising, pinning, keyword rules, redirects, or popularity.
- Do not change AND matching, exact-SKU equality (`skuNormalize` whole query), synonym CUD / Octane freeze, rebuild, Suggest, or the search engine.
- No new Ranker class. No SQL `ORDER BY` for relevance.
- Coverage uses directed **replace** expand (same list as matching). `tee → cotton` is `[cotton]`, not `[tee, cotton]`.
- Exact-SKU comparator **bypasses coverage entirely**: do not compute it for rank-1 rows; two rank-1 hits sort by `title` only.
- Cap: `usort` then `array_slice(..., 0, CANDIDATE_CAP)`. Never slice then sort.
- Human gates: Task 1 review → Task 2 review → whole-branch review.

---

## File map

| Area | Files |
|---|---|
| Comparator | `modules/Product/src/Services/ProductDiscoveryQuery.php` |
| Query tests | `tests/Feature/Product/ProductDiscoveryQueryTest.php` |
| Shop / facets / price | `tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php`, `tests/Feature/Product/SearchIndexOperationsIsolationTest.php` (read-only unless isolation breaks) |
| Discovery | `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md` (Task 1) |
| Index ops flow | `docs/superpowers/specs/2026-09-08-catalog-v2-search-index-operations-design.md` (Task 1, `usort` line only) |

Do not edit `ProductSuggestQuery`, `SearchSynonymExpander`, `ProductSearchIndexer::rebuild`, overlay JS, or merchandising files (none exist — do not create them).

---

### Task 1: Coverage comparator + Discovery spec amendment

**Files:**
- Modify: `modules/Product/src/Services/ProductDiscoveryQuery.php`
- Modify: `tests/Feature/Product/ProductDiscoveryQueryTest.php`
- Modify: `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`
- Modify: `docs/superpowers/specs/2026-09-08-catalog-v2-search-index-operations-design.md`

**Interfaces:**
- Consumes: `candidateUuids(string $q): list<string>`; `$tokens = $this->expander->expand(SearchNormalizer::tokenize($q))`; `highestMatchedFieldRank`; `fieldTokens` keyed `2..6`.
- Produces: each match row `{ uuid, rank, coverage, title }` where `coverage` is `0` and unused when `rank === 1`; `usort` as spec §4; still `array_slice` after sort.

Keep `CANDIDATE_CAP = 500` and existing equal-coverage cap tests green. Do not add the 501-doc high-coverage-late-title fixture here (Task 2).

- [ ] **Step 1: Failing tests (coverage + SKU bypass + expand replace)**

Add to `tests/Feature/Product/ProductDiscoveryQueryTest.php`:

```php
public function test_coverage_uses_winning_field_only(): void
{
    $bothInName = $this->product('Red Cotton Shirt', 'SKU-COV-BOTH');
    $cottonInName = $this->product('Cotton Shirt', 'SKU-COV-NAME', 'Bright red finish');
    $this->index($bothInName, $cottonInName);

    $this->assertSame(
        [$bothInName->uuid, $cottonInName->uuid],
        app(ProductDiscoveryQuery::class)->candidateUuids('red cotton'),
    );
}

public function test_same_field_higher_coverage_beats_title_order(): void
{
    $parka = $this->product('Cotton Parka', 'SKU-COV-PARKA', 'Bright red finish');
    $tee = $this->product('Red Cotton Tee', 'SKU-COV-TEE');
    $this->index($parka, $tee);

    $this->assertSame(
        [$tee->uuid, $parka->uuid],
        app(ProductDiscoveryQuery::class)->candidateUuids('red cotton'),
    );
}

public function test_coverage_counts_distinct_query_tokens(): void
{
    $two = $this->product('Red Cotton Tee', 'SKU-DIST-TWO');
    $one = $this->product('Cotton Shirt', 'SKU-DIST-ONE', 'Bright red finish');
    $this->index($two, $one);

    $this->assertSame(
        [$two->uuid, $one->uuid],
        app(ProductDiscoveryQuery::class)->candidateUuids('red red cotton'),
    );
}

public function test_equal_coverage_falls_back_to_title(): void
{
    $zebra = $this->product('Zebra Cotton', 'SKU-EQ-ZEBRA');
    $alpha = $this->product('Alpha Cotton', 'SKU-EQ-ALPHA');
    $this->index($zebra, $alpha);

    $this->assertSame(
        [$alpha->uuid, $zebra->uuid],
        app(ProductDiscoveryQuery::class)->candidateUuids('cotton'),
    );
}

public function test_exact_sku_hits_sort_by_title_and_ignore_name_coverage(): void
{
    SearchDocument::query()->create([
        'index_name' => ProductSearchIndexer::INDEX,
        'document_id' => 'sku-alpha',
        'title' => 'Alpha Widget',
        'body' => '',
        'payload' => ['skus' => ['RED COTTON'], 'attributes' => []],
    ]);
    SearchDocument::query()->create([
        'index_name' => ProductSearchIndexer::INDEX,
        'document_id' => 'sku-zed',
        'title' => 'Zed Red Cotton',
        'body' => '',
        'payload' => ['skus' => ['RED COTTON'], 'attributes' => []],
    ]);

    $this->assertSame(
        ['sku-alpha', 'sku-zed'],
        app(ProductDiscoveryQuery::class)->candidateUuids('RED COTTON'),
    );
}

public function test_coverage_uses_replaced_expanded_tokens(): void
{
    $cottonTee = $this->product('Cotton Tee', 'SKU-EXP-COTTON');
    $teeShirt = $this->product('Tee Shirt', 'SKU-EXP-TEE', 'Premium cotton lining');
    $this->index($cottonTee, $teeShirt);
    SearchSynonym::query()->create([
        'from_term' => 'ผ้าฝ้าย',
        'to_term' => 'cotton',
    ]);
    app()->forgetInstance(SearchSynonymExpander::class);

    $this->assertSame(
        [$cottonTee->uuid, $teeShirt->uuid],
        app(ProductDiscoveryQuery::class)->candidateUuids('ผ้าฝ้าย tee'),
    );
}
```

Keep `test_highest_field_rank_is_not_cumulative` and `test_non_default_sku_exact_match_ranks_first` unchanged.

- [ ] **Step 2: Run (RED)**

```bash
php artisan test --compact tests/Feature/Product/ProductDiscoveryQueryTest.php
```

Expected: `test_same_field_higher_coverage_beats_title_order` FAIL (`Cotton Parka` currently before `Red Cotton Tee` by title). `test_exact_sku_hits_sort_by_title_and_ignore_name_coverage` FAIL if current `[rank, title]` already puts Alpha first — then this test may PASS immediately; still add it. If it PASSes, that only locks title order; Step 3 must still **not** attach coverage to rank-1 rows (a later `[rank, -coverage, title]` without the rank-1 branch would fail this test). `test_equal_coverage_falls_back_to_title` may already PASS.

- [ ] **Step 3: Comparator**

In `ProductDiscoveryQuery::candidateUuids`, after matching, build rank + coverage, then sort:

```php
$rank = $isExactSku ? 1 : $this->highestMatchedFieldRank($tokens, $fields);

$matches[] = [
    'uuid' => (string) $document->document_id,
    'rank' => $rank,
    'coverage' => $isExactSku ? 0 : $this->tokenCoverage($tokens, $fields[$rank] ?? []),
    'title' => (string) $document->title,
];

usort($matches, static function (array $left, array $right): int {
    if ($left['rank'] === 1 && $right['rank'] === 1) {
        return $left['title'] <=> $right['title'];
    }

    return [$left['rank'], -$left['coverage'], $left['title']]
        <=> [$right['rank'], -$right['coverage'], $right['title']];
});

$matches = array_slice($matches, 0, self::CANDIDATE_CAP);
```

Add:

```php
/**
 * @param  list<string>  $tokens
 * @param  list<string>  $fieldTokens
 */
private function tokenCoverage(array $tokens, array $fieldTokens): int
{
    $count = 0;

    foreach (array_unique($tokens) as $token) {
        if (in_array($token, $fieldTokens, true)) {
            $count++;
        }
    }

    return $count;
}
```

Do not call `tokenCoverage` when `$isExactSku` is true. Do not count tokens from worse fields. Do not OR-append synonym sources.

Do not reverse `usort` / `array_slice` order.

- [ ] **Step 4: Amend Discovery + Index Ops in-place**

Edit `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`.

**§3 Ranking** — replace the tie-break sentence with: Highest matched field, not cumulative. Same-field: distinct expanded query tokens that are exact tokens in the **winning** field (DESC), then `title` ASC. Exact SKU (rank 1) bypasses coverage entirely (two SKU hits: title only). No merchandising.

**§3 Candidate cap** — After that full order (not “highest field then title only”), return at most 500 uuids. Facets for non-empty `q` use that same list. Not SQL LIMIT.

**§6.3** — Keep the field list and the red-cotton name-vs-description example. Replace “Tie-break among the same highest field: `title` ascending.” with coverage in the winning field then title. State: two exact-SKU hits skip coverage. Cap runs after this sort.

**§12** — Keep items 1–16. Replace item 17 so rank order is fieldRank, then coverage DESC (text ranks), then title, then cap 500. Add:

```text
18. Query `red cotton`: name contains both tokens ranks above name-only `cotton` + `red` in description (coverage uses winning field only).
19. Query `red red cotton`: name `Red Cotton Tee` is coverage 2, not 3, and ranks above a name-only cotton hit that matches `red` only in a worse field.
20. Two search_documents with the same exact SKU: title ASC even when the later title contains more query tokens. Coverage is not computed for rank 1.
21. `GET /shop?q=…&sort=price_asc` orders by price inside the capped set; default sort follows `candidateUuids`.
22. Query `ผ้าฝ้าย tee` with replace `ผ้าฝ้าย → cotton`: `Cotton Tee` (name coverage 2) before `Tee Shirt` with cotton only in description. Not CUD / Octane.
23. More than 500 same-fieldRank hits: a late title with higher coverage is inside the 500; an early title with lower coverage can be the omitted 501st (Task 2 test).
```

Scan:

```bash
rg -n "ties resolved by title only|highest field, then title|Tie-break among the same highest field" \
  docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md
```

Expected: no leftover same-field-title-only ranking. Synonym **replace** stays. Non-cumulative field rank stays.

Edit `docs/superpowers/specs/2026-09-08-catalog-v2-search-index-operations-design.md` §5 `candidateUuids` flow only:

```text
candidateUuids(q)
  → load documents, match tokens (unchanged)
  → usort fieldRank, then coverage DESC for text ranks
    (exact-SKU pair: title only; coverage not computed)
  → slice first 500 of that ordered list
  → return uuids
  → ShopController: that same array is listing searchUuids and facet searchUuids
```

Keep “Slice **after** sort”. Do not reopen rebuild, Suggest, or merchandising in that spec. Index Ops intro may still say ranking formula lives on Discovery — after this edit that formula includes coverage.

**Index Ops §6 item 6** — replace “same sort: exact SKU, then highest field, then title” with: same sort as Ranking V1 (`fieldRank`, coverage DESC on text ranks, title; exact-SKU pairs title-only). Cap still after that sort.

- [ ] **Step 5: Re-run**

```bash
php artisan test --compact tests/Feature/Product/ProductDiscoveryQueryTest.php
```

Expected: PASS, including `test_highest_field_rank_is_not_cumulative`, `test_candidate_cap_keeps_rank_prefix_and_drops_the_tail`, `test_synonyms_are_applied_in_the_configured_direction_only`.

- [ ] **Step 6: Commit**

```bash
git add \
  modules/Product/src/Services/ProductDiscoveryQuery.php \
  tests/Feature/Product/ProductDiscoveryQueryTest.php \
  docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md \
  docs/superpowers/specs/2026-09-08-catalog-v2-search-index-operations-design.md
git commit -m "$(cat <<'EOF'
feat: rank discovery candidates by winning-field coverage

EOF
)"
```

- [ ] **Step 7: Human gate — stop**

---

### Task 2: Cap-after-coverage + listing / facet / price regression

**Files:**
- Modify: `tests/Feature/Product/ProductDiscoveryQueryTest.php`
- Modify: `tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php`
- Modify: `modules/Product/src/Services/ProductDiscoveryQuery.php` only if a test proves slice/sort order is wrong (do not add a Ranker)

**Interfaces:**
- Consumes: Task 1 comparator; `ShopProductQuery::paginate(..., searchUuids: $searchUuids)`; `ShopController` one `candidateUuids` call.
- Produces: cap of 500 applied to the coverage-aware list; listing default order matches that list; `price_asc` / `price_desc` do not use relevance; facets still receive the same array.

Do not change Suggest. Do not pin products.

- [ ] **Step 1: Failing (or locking) tests**

Add to `ProductDiscoveryQueryTest.php`:

```php
public function test_candidate_cap_runs_after_coverage(): void
{
    SearchDocument::query()->create([
        'index_name' => ProductSearchIndexer::INDEX,
        'document_id' => 'cov-zzz',
        'title' => 'ZZZ Cotton Red',
        'body' => '',
        'payload' => ['skus' => [], 'attributes' => []],
    ]);

    $lowIds = [];
    for ($i = 0; $i < ProductDiscoveryQuery::CANDIDATE_CAP; $i++) {
        $uuid = sprintf('cov-aaa-%03d', $i);
        $lowIds[] = $uuid;
        SearchDocument::query()->create([
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => $uuid,
            'title' => sprintf('Aaa Cotton %03d', $i),
            'body' => 'red',
            'payload' => ['skus' => [], 'attributes' => []],
        ]);
    }

    $uuids = app(ProductDiscoveryQuery::class)->candidateUuids('red cotton');

    $this->assertCount(ProductDiscoveryQuery::CANDIDATE_CAP, $uuids);
    $this->assertContains('cov-zzz', $uuids);
    $this->assertNotContains($lowIds[ProductDiscoveryQuery::CANDIDATE_CAP - 1], $uuids);
}
```

This is 501 matches, all `fieldRank = 2`. `ZZZ Cotton Red` has coverage 2; each `Aaa Cotton NNN` has coverage 1 (`cotton` in name, `red` in body). Title-only cap would drop `cov-zzz`. After coverage, `cov-zzz` is kept and the last `Aaa` is dropped.

Keep `test_candidate_cap_keeps_rank_prefix_and_drops_the_tail` (equal coverage, title prefix).

Add to `CatalogV2SearchDiscoveryRegressionTest.php`. Extend `product()` so description can be set, or `update` then `index`:

```php
public function test_shop_listing_follows_coverage_order(): void
{
    $parka = $this->product('Cotton Parka', 'SHOP-COV-PARKA');
    $parka->update(['description' => 'Bright red finish']);
    $tee = $this->product('Red Cotton Tee', 'SHOP-COV-TEE');
    $this->index($parka->fresh(), $tee);

    $this->get(route('storefront.shop.index', ['q' => 'red cotton']))
        ->assertOk()
        ->assertSeeInOrder([$tee->name, $parka->name]);
}

public function test_price_asc_does_not_use_coverage_order(): void
{
    $tee = $this->product('Red Cotton Tee', 'SHOP-PRICE-TEE', 9000);
    $parka = $this->product('Cotton Parka', 'SHOP-PRICE-PARKA', 1000);
    $parka->update(['description' => 'Bright red finish']);
    $this->index($tee, $parka->fresh());

    $this->get(route('storefront.shop.index', [
        'q' => 'red cotton',
        'sort' => 'price_asc',
    ]))
        ->assertOk()
        ->assertSeeInOrder([$parka->name, $tee->name]);
}
```

Keep `test_price_sort_overrides_discovery_ranking`, `test_shop_request_loads_discovery_candidates_once_for_listing_and_facets`, and `SearchIndexOperationsIsolationTest::test_shop_controller_passes_one_candidate_list_to_listing_and_facets`.

If `test_candidate_cap_runs_after_coverage` already PASSes after Task 1, that is allowed — it locks slice-after-coverage. If it FAILs, the comparator or slice/sort order is wrong; fix in this task without a new class.

- [ ] **Step 2: Run**

```bash
php artisan test --compact \
  tests/Feature/Product/ProductDiscoveryQueryTest.php \
  tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php \
  tests/Feature/Product/SearchIndexOperationsIsolationTest.php \
  tests/Feature/Storefront/ShopDiscoveryListingTest.php
```

Expected: listing coverage test FAIL only if shop CASE order is not using `candidateUuids` (it should already). Cap-after-coverage FAIL if someone sliced by title then sorted.

- [ ] **Step 3: Fix only if red**

If cap drops `cov-zzz`, `usort` is running after `array_slice` or coverage is not in the comparator — restore Task 1 order: sort full match set, then slice.

If listing shows Parka before Tee, `ShopProductQuery::applySort` is not using the passed `searchUuids`. Do not call `candidateUuids` a second time inside listing when `searchUuids` is provided.

Price test: `applySort` for `price_asc` must `return` before the uuid CASE. Do not add merchandising.

- [ ] **Step 4: Acceptance sweep**

```bash
php artisan test --compact \
  tests/Feature/Product/ProductDiscoveryQueryTest.php \
  tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php \
  tests/Feature/Product/SearchIndexOperationsIsolationTest.php \
  tests/Feature/Storefront/ShopDiscoveryListingTest.php \
  tests/Feature/Product/ProductSuggestQueryTest.php \
  tests/Feature/Storefront/StorefrontSuggestTest.php
```

Expected: PASS. Diff must not include Suggest matcher, expander lifecycle, rebuild, or new merchandising types.

Scan leftover Discovery wording:

```bash
rg -n "same highest field: \`title\`|highest field, then title" \
  docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md \
  docs/superpowers/specs/2026-09-08-catalog-v2-search-index-operations-design.md
```

- [ ] **Step 5: Commit**

```bash
git add \
  tests/Feature/Product/ProductDiscoveryQueryTest.php \
  tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php \
  modules/Product/src/Services/ProductDiscoveryQuery.php
git commit -m "$(cat <<'EOF'
test: lock ranking cap listing and price order

EOF
)"
```

- [ ] **Step 6: Human gate — stop**

---

## Whole-branch review

```bash
php artisan test --compact \
  tests/Feature/Product/ProductDiscoveryQueryTest.php \
  tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php \
  tests/Feature/Product/SearchIndexOperationsIsolationTest.php \
  tests/Feature/Storefront/ShopDiscoveryListingTest.php \
  tests/Feature/Product/ProductSuggestQueryTest.php \
  tests/Feature/Storefront/StorefrontSuggestTest.php
```

```text
Task 1 → Task 2 → whole-branch review → merge
```

---

## Spec coverage

| Spec | Task |
|---|---|
| fieldRank beats coverage; winning-field coverage; DISTINCT tokens | Task 1 |
| Exact SKU first; rank-1 comparator bypasses coverage | Task 1 |
| Equal coverage → title; expand replace fixture | Task 1 |
| Discovery §3 / §6.3 / §12 + Index Ops `usort` line | Task 1 |
| Cap after coverage; listing CASE; facets same list; price_asc | Task 2 |
| No merchandising / pinning / keyword rules | Global constraints |
