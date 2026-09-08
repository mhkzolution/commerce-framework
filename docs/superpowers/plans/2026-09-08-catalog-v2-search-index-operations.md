# Catalog V2 Search Index Operations Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lock flush-first rebuild fail-fast (no rollback, non-success on throw), cap discovery candidates at 500 after rank, and eager-load label-change reindex from one relation constant.

**Architecture:** Keep `ProductSearchIndexer::rebuild()` as flush then `reindexAll()`. Cap only inside `ProductDiscoveryQuery::candidateUuids` after `usort`. `INDEX_RELATIONS` on the indexer is the single `with()` list for `reindexAll` and `AttributeValueService` label rename. Amend Discovery in Task 1.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, existing `search_documents` + `SearchIndexInterface`

**Spec:** `docs/superpowers/specs/2026-09-08-catalog-v2-search-index-operations-design.md` (Locked)

**Start gate:** Locked spec on `main`. Work on `feat/catalog-v2-search-index-operations`.

## Global Constraints

- Flush-first rebuild. No `products_next` / swap, no skip-and-continue, no transaction around flush+rebuild, no rebuild lock, no new queue.
- Documents written before a rebuild exception stay. No rollback / cleanup delete of those rows.
- CLI non-zero and admin must not flash `Rebuilt search documents for …` when rebuild throws.
- Cap after final rank, before `candidateUuids` returns. Value `500`. `public const` on `ProductDiscoveryQuery`. Not SQL `LIMIT`, not config.
- Facet aggregation for q-based discovery uses that same capped list. Not a second uncapped discovery call.
- Suggest, ranking formula, synonym expander, merchandising, listing/PDP N+1, search platforms: do not change.
- Human gates: Task 1 review → Task 2 review → Task 3 review → whole-branch review.

---

## File map

| Area | Files |
|---|---|
| Rebuild | `modules/Product/src/Services/ProductSearchIndexer.php`, `modules/Product/src/Console/ReindexProductsCommand.php`, `modules/Product/src/Http/Controllers/Admin/ProductSettingsController.php` (touch production only if tests fail) |
| Cap | `modules/Product/src/Services/ProductDiscoveryQuery.php`, `modules/Cart/src/Http/Controllers/ShopController.php` (controller already shares `$searchUuids`; lock with a source test) |
| Label path | `modules/Catalog/src/Services/AttributeValueService.php` |
| Tests | `tests/Feature/Product/SearchReindexTriggersTest.php`, `tests/Feature/Product/ProductDiscoveryQueryTest.php`, `tests/Feature/Product/SearchIndexOperationsIsolationTest.php` (create) |
| Discovery | `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md` (Task 1) |

---

### Task 1: Rebuild failure semantics + Discovery amendment

**Files:**
- Modify: `tests/Feature/Product/SearchReindexTriggersTest.php`
- Modify: `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`
- Modify production rebuild/CLI/admin **only if** a new test fails

**Interfaces:**
- Consumes: `ProductSearchIndexer::rebuild(): int` = `flush` then `reindexAll`; `product:reindex`; `POST` `admin.products.settings.reindex`.
- Produces: Fail-fast leftover documents; CLI exit ≠ 0; no success flash; Discovery §3 / §8 / §10 / §12 match spec §5.1.

If new tests pass with no production PHP diff, that is expected. Do not add `try/catch` that swallows or cleans up documents.

- [ ] **Step 1: Failing (or locking) rebuild tests**

Add to `tests/Feature/Product/SearchReindexTriggersTest.php` (keep existing tests). After the test class, add this collaborator in the same file:

```php
use Commerce\Contracts\Search\SearchIndexInterface;
use Commerce\Core\Search\DatabaseSearchIndex;
use RuntimeException;

final class ThrowsAfterFirstIndex implements SearchIndexInterface
{
    public function __construct(
        private readonly SearchIndexInterface $inner,
        private int $indexCalls = 0,
    ) {}

    public function index(string $index, string $id, array $document): void
    {
        $this->indexCalls++;

        if ($this->indexCalls > 1) {
            throw new RuntimeException('rebuild aborted');
        }

        $this->inner->index($index, $id, $document);
    }

    public function delete(string $index, string $id): void
    {
        $this->inner->delete($index, $id);
    }

    public function flush(string $index): void
    {
        $this->inner->flush($index);
    }
}
```

In the test class:

```php
use Commerce\Product\Services\ProductSearchIndexer;
use Symfony\Component\HttpKernel\Exception\HttpException;

private function bindThrowAfterFirstIndex(): void
{
    $this->app->forgetInstance(ProductSearchIndexer::class);
    $this->app->bind(
        SearchIndexInterface::class,
        static fn (): SearchIndexInterface => new ThrowsAfterFirstIndex(new DatabaseSearchIndex),
    );
}

public function test_rebuild_abort_keeps_documents_indexed_before_the_exception(): void
{
    $first = $this->createPurchasableProduct(sku: 'REBUILD-KEEP')->product;
    $second = $this->createPurchasableProduct(sku: 'REBUILD-SKIP')->product;
    app(ProductSearchIndexer::class)->index($first);
    app(ProductSearchIndexer::class)->index($second);
    $this->staleDocument('stale-abort');

    $this->bindThrowAfterFirstIndex();

    try {
        app(ProductSearchIndexer::class)->rebuild();
        $this->fail('Expected rebuild to throw.');
    } catch (RuntimeException $exception) {
        $this->assertSame('rebuild aborted', $exception->getMessage());
    }

    $this->assertDatabaseMissing('search_documents', [
        'index_name' => ProductSearchIndexer::INDEX,
        'document_id' => 'stale-abort',
    ]);
    $this->assertDatabaseHas('search_documents', [
        'index_name' => ProductSearchIndexer::INDEX,
        'document_id' => $first->uuid,
    ]);
    $this->assertDatabaseMissing('search_documents', [
        'index_name' => ProductSearchIndexer::INDEX,
        'document_id' => $second->uuid,
    ]);
}

public function test_reindex_command_exits_nonzero_when_rebuild_throws(): void
{
    $this->createPurchasableProduct(sku: 'CLI-KEEP');
    $this->createPurchasableProduct(sku: 'CLI-SKIP');
    $this->bindThrowAfterFirstIndex();

    $this->artisan('product:reindex')
        ->expectsOutputToContain('rebuild aborted')
        ->assertFailed();
}

public function test_admin_rebuild_does_not_flash_success_when_rebuild_throws(): void
{
    $this->createPurchasableProduct(sku: 'HTTP-KEEP');
    $this->createPurchasableProduct(sku: 'HTTP-SKIP');
    $this->bindThrowAfterFirstIndex();

    $this->withoutExceptionHandling();

    try {
        $this->actingAs(User::query()->firstOrFail())
            ->post(route('admin.products.settings.reindex'));
        $this->fail('Expected rebuild to throw.');
    } catch (RuntimeException) {
        // uncaught is the failure response
    }

    $this->assertNull(session('status'));
}
```

`expectsOutputToContain('rebuild aborted')` may fail if Laravel prints the exception class only. If so, drop that assertion and keep `assertFailed()`. Do **not** `assertSuccessful()`. Do not add a catch in `ReindexProductsCommand` just to print a friendlier line.

If `assertFailed()` is not available: `->assertExitCode(1)` (or any non-zero).

- [ ] **Step 2: Run Task 1 tests**

```bash
php artisan test --compact tests/Feature/Product/SearchReindexTriggersTest.php
```

Expected: new tests PASS (or FAIL only until you drop a too-strict output assertion). Existing flush-stale and permission tests stay green. If leftover-document test fails because `chunkById` order is not `first` then `second`, order by creating `$first` before `$second` (lower id first).

Do not wrap `rebuild()` in a transaction. Do not delete leftover documents in a `catch`.

- [ ] **Step 3: Amend Discovery in-place**

Edit `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md` at all four targets.

**§3** — after the Ranking row, add:

```text
| Candidate cap | After rank (highest field, then title), `ProductDiscoveryQuery` returns at most 500 uuids. Facets for non-empty `q` use that same list. Not SQL LIMIT. Empty `q` does not query the index. |
```

**§8** — replace:

```text
After the index returns candidate product ids (or the full published catalog when `q` is empty):
```

with:

```text
When `q` is non-empty, listing and facet aggregation use the same post-rank capped candidate set from `ProductDiscoveryQuery` (at most 500 uuids). When `q` is empty, skip the index; browse + facets run on the published catalog:
```

**§10 Admin rebuild row** — replace:

```text
| Admin “Rebuild Search Index” | Flush + rebuild all product documents |
```

with:

```text
| Admin “Rebuild Search Index” | Flush + rebuild all product documents. After flush, an aborted rebuild leaves empty or partial documents; documents indexed before the exception remain; no rollback. CLI exits non-zero; admin does not flash success. Operator re-runs. |
```

**§12** — append:

```text
16. After a thrown full rebuild, documents already indexed in that run remain; pre-flush stale rows that were flushed stay gone; the pre-flush index is not restored.
17. `candidateUuids` returns at most 500 uuids in Discovery rank order. Facets for that `q` use the same list.
```

Then scan the file. These must not remain as unbounded/rollback claims:

```text
returns candidate product ids
Flush + rebuild all product documents
```

(the old unbounded/short rebuild sentences). Synonym `Query-time only` stays.

```bash
rg -n "every matching|unbounded|restores the previous|next query expands" \
  docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md
```

Expected: no rollback/unbounded leftover. Cap 500 and fail-fast appear in §3, §8, §10, §12.

- [ ] **Step 4: Commit**

```bash
git add \
  tests/Feature/Product/SearchReindexTriggersTest.php \
  docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md \
  modules/Product/src/Services/ProductSearchIndexer.php \
  modules/Product/src/Console/ReindexProductsCommand.php \
  modules/Product/src/Http/Controllers/Admin/ProductSettingsController.php
git commit -m "$(cat <<'EOF'
test: lock flush-first rebuild fail-fast leftover

EOF
)"
```

Omit production PHP from `git add` if unchanged.

- [ ] **Step 5: Human gate — stop**

```text
Task 1
  ↓ review
```

---

### Task 2: Candidate cap = 500

**Files:**
- Modify: `modules/Product/src/Services/ProductDiscoveryQuery.php`
- Modify: `tests/Feature/Product/ProductDiscoveryQueryTest.php`
- Create: `tests/Feature/Product/SearchIndexOperationsIsolationTest.php`

**Interfaces:**
- Consumes: `candidateUuids(string $q): list<string>` (Task 1 does not change this signature).
- Produces: `ProductDiscoveryQuery::CANDIDATE_CAP = 500`. After `usort`, `array_slice(..., 0, self::CANDIDATE_CAP)`. Shop listing and facets still receive that one array.

- [ ] **Step 1: Failing tests**

Add to `tests/Feature/Product/ProductDiscoveryQueryTest.php`:

```php
use Illuminate\Support\Facades\DB;

public function test_candidate_cap_keeps_rank_prefix_and_drops_the_tail(): void
{
    $ids = [];

    for ($i = 0; $i <= ProductDiscoveryQuery::CANDIDATE_CAP; $i++) {
        $uuid = sprintf('cap-%03d', $i);
        $ids[] = $uuid;
        SearchDocument::query()->create([
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => $uuid,
            'title' => sprintf('Cap %03d', $i),
            'body' => '',
            'payload' => ['skus' => [], 'attributes' => []],
        ]);
    }

    $uuids = app(ProductDiscoveryQuery::class)->candidateUuids('cap');

    $this->assertCount(ProductDiscoveryQuery::CANDIDATE_CAP, $uuids);
    $this->assertSame(array_slice($ids, 0, ProductDiscoveryQuery::CANDIDATE_CAP), $uuids);
    $this->assertNotContains(sprintf('cap-%03d', ProductDiscoveryQuery::CANDIDATE_CAP), $uuids);
}

public function test_candidate_cap_does_not_limit_the_sql_scan(): void
{
    SearchDocument::query()->create([
        'index_name' => ProductSearchIndexer::INDEX,
        'document_id' => 'cap-sql',
        'title' => 'Cap',
        'body' => '',
        'payload' => ['skus' => [], 'attributes' => []],
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    app(ProductDiscoveryQuery::class)->candidateUuids('cap');
    $sql = array_column(DB::getQueryLog(), 'query');

    $this->assertFalse(
        collect($sql)->contains(
            static fn (string $query): bool => str_contains(strtolower($query), 'limit')
                && str_contains($query, 'search_documents'),
        ),
    );
}
```

Titles `Cap 000` … `Cap 500` sort as title ascending; all match token `cap`. Uncapped order is `cap-000` … `cap-500`. After cap, last uuid `cap-500` is gone.

Create `tests/Feature/Product/SearchIndexOperationsIsolationTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use PHPUnit\Framework\TestCase;

final class SearchIndexOperationsIsolationTest extends TestCase
{
    public function test_shop_controller_passes_one_candidate_list_to_listing_and_facets(): void
    {
        $path = dirname(__DIR__, 3).'/modules/Cart/src/Http/Controllers/ShopController.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertSame(1, substr_count($contents, 'candidateUuids('));
        $this->assertStringContainsString('buildFor($filters, $searchUuids)', $contents);
        $this->assertStringContainsString('searchUuids: $searchUuids', $contents);
    }
}
```

`dirname(__DIR__, 3)` from `tests/Feature/Product` is the repo root.

- [ ] **Step 2: Run tests (RED)**

```bash
php artisan test --compact \
  tests/Feature/Product/ProductDiscoveryQueryTest.php \
  tests/Feature/Product/SearchIndexOperationsIsolationTest.php
```

Expected: cap tests FAIL (`assertCount` 501 vs 500) until the slice exists. Isolation test should already PASS (controller already shares `$searchUuids`). Existing empty-q and ranking tests stay green.

- [ ] **Step 3: Slice after sort**

In `modules/Product/src/Services/ProductDiscoveryQuery.php`:

```php
public const CANDIDATE_CAP = 500;
```

After the existing `usort`, before `return`:

```php
$matches = array_slice($matches, 0, self::CANDIDATE_CAP);

return array_values(array_column($matches, 'uuid'));
```

Do not slice before `usort`. Do not add `limit()` on `SearchDocument::query()`. Do not cap inside `ShopController`. Do not change Suggest.

- [ ] **Step 4: Re-run**

```bash
php artisan test --compact \
  tests/Feature/Product/ProductDiscoveryQueryTest.php \
  tests/Feature/Product/SearchIndexOperationsIsolationTest.php \
  tests/Feature/Product/SearchReindexTriggersTest.php \
  tests/Feature/Product/ProductSuggestQueryTest.php
```

Expected: PASS. `CANDIDATE_CAP` is 500. Suggest unchanged.

- [ ] **Step 5: Commit**

```bash
git add \
  modules/Product/src/Services/ProductDiscoveryQuery.php \
  tests/Feature/Product/ProductDiscoveryQueryTest.php \
  tests/Feature/Product/SearchIndexOperationsIsolationTest.php
git commit -m "$(cat <<'EOF'
feat: cap discovery candidates at 500 after rank

EOF
)"
```

- [ ] **Step 6: Human gate — stop**

---

### Task 3: Label-change reindex eager loading

**Files:**
- Modify: `modules/Product/src/Services/ProductSearchIndexer.php`
- Modify: `modules/Catalog/src/Services/AttributeValueService.php`
- Modify: `tests/Feature/Product/SearchReindexTriggersTest.php`

**Interfaces:**
- Consumes: `ProductSearchIndexer::index(Product $product): void`; `reindexAll()` currently `with([...])`.
- Produces: `ProductSearchIndexer::INDEX_RELATIONS` used by `reindexAll()` and `AttributeValueService::update` label branch. Label rename still reindexes; no duplicated relation literal in Catalog.

- [ ] **Step 1: Isolation + query-log tests**

Add to `tests/Feature/Product/SearchIndexOperationsIsolationTest.php`:

```php
public function test_label_reindex_uses_indexer_relation_constant(): void
{
    $indexer = file_get_contents(dirname(__DIR__, 3).'/modules/Product/src/Services/ProductSearchIndexer.php');
    $service = file_get_contents(dirname(__DIR__, 3).'/modules/Catalog/src/Services/AttributeValueService.php');
    $this->assertNotFalse($indexer);
    $this->assertNotFalse($service);

    $this->assertStringContainsString('INDEX_RELATIONS', $indexer);
    $this->assertStringContainsString('with(self::INDEX_RELATIONS)', $indexer);
    $this->assertStringContainsString('ProductSearchIndexer::INDEX_RELATIONS', $service);
    $this->assertStringNotContainsString(
        "with(['variants', 'categories', 'brand', 'attributeValues.attributeValue'])",
        $service,
    );
}
```

Add to `tests/Feature/Product/SearchReindexTriggersTest.php`:

```php
use Illuminate\Support\Facades\DB;

public function test_label_change_eager_loads_index_relations_for_multiple_products(): void
{
    $color = Attribute::query()->create([
        'code' => 'color-nplus',
        'name' => 'Color',
        'type' => 'select',
        'is_filterable' => true,
        'is_visible' => true,
        'options' => [],
    ]);
    $red = AttributeValue::query()->create([
        'tenant_id' => $color->tenant_id,
        'attribute_id' => $color->id,
        'code' => app(AttributeValueService::class)->allocateCode($color->id, 'Red'),
        'label' => 'Red',
        'position' => 0,
    ]);

    foreach (['NPLUS-A', 'NPLUS-B'] as $sku) {
        $product = $this->createPurchasableProduct(sku: $sku)->product;
        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'used_for_variations' => false,
            'position' => 0,
        ]);
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'product_variant_id' => null,
            'attribute_value_id' => $red->id,
            'value' => $red->label,
        ]);
        app(ProductSearchIndexer::class)->index($product->fresh());
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    app(AttributeValueService::class)->update($red->uuid, 'Crimson Red');

    $sql = array_column(DB::getQueryLog(), 'query');
    $attributeValueSelects = collect($sql)->filter(
        static fn (string $query): bool => str_contains($query, 'attribute_values'),
    );
    $this->assertLessThanOrEqual(2, $attributeValueSelects->count());

    $productUuids = Product::query()
        ->whereIn(
            'id',
            ProductAttributeValue::query()
                ->where('attribute_value_id', $red->id)
                ->pluck('product_id'),
        )
        ->pluck('uuid');

    foreach ($productUuids as $uuid) {
        $document = SearchDocument::query()
            ->where('index_name', ProductSearchIndexer::INDEX)
            ->where('document_id', $uuid)
            ->firstOrFail();
        $this->assertContains(
            ['code' => $red->fresh()->code, 'label' => 'Crimson Red'],
            $document->payload['attributes'],
        );
    }
}
```

Keep `test_label_change_reindexes_affected_products_without_changing_filter_code` green.

- [ ] **Step 2: Run (RED on isolation)**

```bash
php artisan test --compact \
  tests/Feature/Product/SearchIndexOperationsIsolationTest.php \
  tests/Feature/Product/SearchReindexTriggersTest.php
```

Expected: isolation FAIL until `INDEX_RELATIONS` exists. Label-change crimson test still PASS.

- [ ] **Step 3: Implement**

In `modules/Product/src/Services/ProductSearchIndexer.php`:

```php
/**
 * @var list<string>
 */
public const INDEX_RELATIONS = [
    'variants',
    'categories',
    'brand',
    'attributeValues.attributeValue',
];
```

`reindexAll()`:

```php
Product::query()
    ->with(self::INDEX_RELATIONS)
    ->chunkById(100, function ($products) use (&$count): void {
```

`index()` may keep `loadMissing(self::INDEX_RELATIONS)` instead of repeating the array.

In `modules/Catalog/src/Services/AttributeValueService.php` label-changed branch:

```php
if ($labelChanged) {
    $productIds = DB::table('product_attribute_values')
        ->where('attribute_value_id', $value->id)
        ->distinct()
        ->pluck('product_id');

    Product::query()
        ->whereKey($productIds)
        ->with(ProductSearchIndexer::INDEX_RELATIONS)
        ->chunkById(100, function ($products): void {
            foreach ($products as $product) {
                $this->productSearchIndexer->index($product);
            }
        });
}
```

Do not copy the relation list as a literal in this file.

- [ ] **Step 4: Re-run**

```bash
php artisan test --compact \
  tests/Feature/Product/SearchReindexTriggersTest.php \
  tests/Feature/Product/SearchIndexOperationsIsolationTest.php \
  tests/Feature/Product/ProductDiscoveryQueryTest.php
```

Expected: PASS. Crimson / filter-code still green.

- [ ] **Step 5: Commit**

```bash
git add \
  modules/Product/src/Services/ProductSearchIndexer.php \
  modules/Catalog/src/Services/AttributeValueService.php \
  tests/Feature/Product/SearchReindexTriggersTest.php \
  tests/Feature/Product/SearchIndexOperationsIsolationTest.php
git commit -m "$(cat <<'EOF'
feat: eager-load search index relations on label reindex

EOF
)"
```

- [ ] **Step 6: Human gate — stop**

---

## Whole-branch review

```bash
php artisan test --compact \
  tests/Feature/Product/SearchReindexTriggersTest.php \
  tests/Feature/Product/ProductDiscoveryQueryTest.php \
  tests/Feature/Product/SearchIndexOperationsIsolationTest.php \
  tests/Feature/Product/ProductSuggestQueryTest.php \
  tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php \
  tests/Feature/Storefront/ShopDiscoveryListingTest.php
```

Diff must not include: swap index, Suggest ranking changes, synonym expander, merchandising, `LIMIT` on discovery SQL, listing-only cap.

```text
Task 1 → Task 2 → Task 3 → whole-branch review → merge
```

---

## Spec coverage

| Spec | Task |
|---|---|
| Flush-first; leftover docs remain; no rollback | Task 1 leftover test |
| CLI non-zero; admin no success flash | Task 1 artisan + HTTP tests |
| Discovery §10 / §8 / §12 / §3 in-place + scan | Task 1 Discovery edits |
| Cap after `usort`, 500, tail dropped | Task 2 |
| Facets use same capped list | Task 2 isolation + ShopController |
| `INDEX_RELATIONS` shared | Task 3 |
| No Suggest / ranking formula / swap | Global constraints |
