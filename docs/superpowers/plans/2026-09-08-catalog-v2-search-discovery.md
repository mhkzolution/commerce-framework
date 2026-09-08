# Catalog V2 Phase 2 Search & Discovery Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Discovery search on Phase 1 relations: `SearchIndexInterface` returns ranked product ids; facets and filters stay SQL on relations.

**Architecture:** One `search_documents` row per product. `ProductDiscoveryQuery` runs the spec pipeline only when `q` is non-empty. Facet counts are self-excluding on the current result set. No Meilisearch/Typesense.

**Tech Stack:** Laravel 13, PHP 8.4 (`ext-intl` for NFC), PHPUnit, existing `search_documents` + `SearchIndexInterface`

**Spec:** `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md` (Locked)

**Start gate:** Catalog V2 Phase 1 Complete on `main`. Work on `feat/catalog-search-discovery-v1`.

## Global Constraints

- Do not change inventory tables, `setOnHand()`, backorder policy, SKU uniqueness, or `constrainInStock()`.
- Do not infer `products.type` from variant count.
- `attribute_values.code` is immutable; filter/URL identity is `code` only.
- Indexer does not read `variant_options`, `options`, or `specifications`.
- No Meilisearch, Typesense, suggest, merchandising, multi-select facets, prefix/substring tokens.
- Ranking = **highest matched field only**, not a sum of fields.
- Query pipeline runs **only when `q` is non-empty** (alias `search`; whitespace-only is empty).
- Human gate after Wave 1, Wave 2, and Wave 3. Do not start the next wave until approved.
- Do not change PDP combination disable rules or workspace generate.

---

## File map

| Area | Files |
|---|---|
| Normalize | Create `modules/Product/src/Support/SearchNormalizer.php` |
| Indexer | Modify `modules/Product/src/Services/ProductSearchIndexer.php` |
| Synonyms | Create model + migration `product_search_synonyms`; `SearchSynonymExpander` |
| Discovery query | Create `modules/Product/src/Services/ProductDiscoveryQuery.php` |
| Reserved codes | Modify `StoreAttributeRequest` / `UpdateAttributeRequest` |
| Admin | Synonym CRUD under catalog; rebuild on product settings |
| Label reindex | Modify `AttributeValueService::update` |
| Storefront | `ShopListingFilters`, `ShopFilterCatalog`, `ShopFilterCatalogService`, `ShopProductQuery`, shop Blade |

---

## Wave 1 — Schema / indexer / query pipeline

### Task 1: Shared Unicode normalization

**Files:**
- Create: `modules/Product/src/Support/SearchNormalizer.php`
- Test: `tests/Unit/Product/SearchNormalizerTest.php`

**Interfaces:**
- Produces: `SearchNormalizer::nfc`, `skuNormalize`, `textNormalize`, `tokenize` (exact spec §6.0)

- [ ] **Step 1: Failing tests** for NFC + case + tokenize (no hyphen split).

```php
public function test_text_normalize_uses_nfc_and_lowercase(): void
{
    $composed = "É";
    $decomposed = "E\u{0301}";
    $this->assertSame(
        SearchNormalizer::textNormalize($composed),
        SearchNormalizer::textNormalize($decomposed),
    );
}

public function test_sku_normalize_trim_uppercase_nfc(): void
{
    $this->assertSame('ABC123', SearchNormalizer::skuNormalize(' abc123 '));
}

public function test_tokenize_splits_whitespace_not_hyphens(): void
{
    $this->assertSame(['tshirt-red', 'tee'], SearchNormalizer::tokenize('  TSHIRT-RED   tee '));
}
```

- [ ] **Step 2: Run, fail** (`php vendor/bin/phpunit tests/Unit/Product/SearchNormalizerTest.php`)

- [ ] **Step 3: Implement** with `Normalizer::normalize($value, Normalizer::FORM_C)`, `mb_strtolower` / `mb_strtoupper` UTF-8, `preg_split('/\s+/u', ...)`.

- [ ] **Step 4: Pass tests.**

- [ ] **Step 5: Commit** `feat: add shared search unicode normalizer`

---

### Task 2: Product search document (all SKUs, attributes deduped)

**Files:**
- Modify: `modules/Product/src/Services/ProductSearchIndexer.php`
- Test: `tests/Feature/Product/ProductSearchIndexerTest.php` (new) — do not rely on `LIKE` title/body for attributes

**Interfaces:**
- Consumes: `SearchNormalizer`; relations `variants`, `categories`, `brand`, `attributeValues.attributeValue`
- Produces: payload per spec §5; `attributes[]` unique by `code`; no JSON keys

- [ ] **Step 1: Failing test** — two Red variants → one `{code: red, label: Red}`; non-default SKU in `skus[]`; lying `meta.specifications` not copied.

```php
public function test_indexer_dedupes_attribute_codes_and_lists_every_sku(): void
{
    // variable product, default Blue + extra Red; both Color
    $doc = SearchDocument::query()
        ->where('index_name', ProductSearchIndexer::INDEX)
        ->where('document_id', $product->uuid)
        ->firstOrFail();
    $codes = array_column($doc->payload['attributes'], 'code');
    $this->assertSame(['red', 'blue'], array_values(array_unique($codes)));
    $this->assertCount(2, $doc->payload['attributes']);
    $this->assertContains($redSku, $doc->payload['skus']);
    $this->assertArrayNotHasKey('specifications', $doc->payload);
}
```

- [ ] **Step 2: Run, fail** (current indexer only default SKU + first category).

- [ ] **Step 3: Implement** `index()`: `loadMissing(['variants', 'categories', 'brand', 'attributeValues.attributeValue'])`; build payload; attributes keyed by `attributeValue->code`. `reindexAll` must load the same relations.

- [ ] **Step 4: Pass tests.** Existing `ProductSearchTest` / chrome tests that only index name still pass (`title` still name).

- [ ] **Step 5: Commit** `feat: index all skus and relation attribute tokens`

**Stop for Wave 1 if Task 4 is not done yet — continue Task 3–4 before the human gate.**

---

### Task 3: Directed synonym replace

**Files:**
- Create: `modules/Product/database/migrations/2026_09_08_200000_create_product_search_synonyms_table.php`
- Create: `modules/Product/src/Models/SearchSynonym.php`
- Create: `modules/Product/src/Services/SearchSynonymExpander.php`
- Test: `tests/Unit/Product/SearchSynonymExpanderTest.php`

**Interfaces:**
- Produces: `SearchSynonymExpander::expand(list<string> $tokens): list<string>` — replace using `text_normalize`; `to_term` run through `tokenize` (flatten). Unique `from_term`. No reindex.

- [ ] **Step 1: Failing test** — `['ผ้าฝ้าย', 'tee']` + row `ผ้าฝ้าย → cotton` → `['cotton', 'tee']`. Reverse not implied. Changing the row does not touch `search_documents`.

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Migration** `from_term` string unique, `to_term` string. Expander loads map once per request.

- [ ] **Step 4: Pass tests.**

- [ ] **Step 5: Commit** `feat: expand search queries with directed synonym replace`

---

### Task 4: Discovery query pipeline

**Files:**
- Create: `modules/Product/src/Services/ProductDiscoveryQuery.php`
- Test: `tests/Feature/Product/ProductDiscoveryQueryTest.php`

**Interfaces:**
- Consumes: `SearchDocument` payload from Task 2, expander from Task 3, `SearchNormalizer`
- Produces: `candidateUuids(string $q): list<string>` ordered by highest-field rank then title. **If `trim($q) === ''`, return `[]` without reading the table** (callers must not use this for empty browse).

Field ranks: exact SKU=1, name=2, brand=3, category=4, attribute=5, description=6. Document rank = min rank among fields that matched ≥1 token (or 1 if exact SKU). Not a sum.

- [ ] **Step 1: Failing tests** covering spec §12 items 1, 4, 5, 6, 14, 15:

```php
public function test_empty_query_does_not_read_search_documents(): void
{
    SearchDocument::query()->delete();
    $this->assertSame([], app(ProductDiscoveryQuery::class)->candidateUuids('  '));
}

public function test_highest_field_rank_is_not_cumulative(): void
{
    // A: cotton in name, red in description
    // B: red and cotton only in description
    $uuids = app(ProductDiscoveryQuery::class)->candidateUuids('red cotton');
    $this->assertSame($productA->uuid, $uuids[0]);
    $this->assertContains($productB->uuid, $uuids);
}

public function test_non_default_sku_exact_match_ranks_first(): void { /* spec 1 */ }
public function test_cot_does_not_match_cotton(): void { /* spec 5 */ }
```

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Implement** in PHP over `SearchDocument::query()->where('index_name', ProductSearchIndexer::INDEX)`. Do **not** change `DatabaseSearchQuery` (admin global search stays LIKE on title/body).

- [ ] **Step 4: Pass tests.**

- [ ] **Step 5: Commit** `feat: rank discovery search by highest matched field`

**Stop for human Wave 1 review.** Do not start admin synonyms until approved.

---

## Wave 2 — Admin / reindex

### Task 5: Reserved attribute codes + synonym admin

**Files:**
- Create: `modules/Product/src/Support/SearchReservedParams.php` (`q`, `category`, `brand`, `sort`, `availability`, `price_min`, `price_max`, `page`)
- Modify: `modules/Catalog/src/Http/Requests/StoreAttributeRequest.php`, `UpdateAttributeRequest.php`
- Create: synonym admin controller/views under catalog (index/create/destroy), routes in `modules/Product/routes/admin.php` or Catalog routes — **prefer Product module** `admin.catalog.search-synonyms.*` and a catalog subnav link
- Test: `tests/Feature/Catalog/AttributeReservedCodeTest.php`, `tests/Feature/Product/SearchSynonymAdminTest.php`

**Interfaces:**
- Consumes: Task 3 table
- Produces: staff can CUD synonyms; `code=brand` rejected; synonym CUD does **not** call `ProductSearchIndexer`

- [ ] **Step 1: Failing tests** — POST attribute `code=brand` 422; create synonym `ผ้าฝ้าย → cotton`; update/delete; `search_documents` timestamps unchanged.

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Implement** `Rule::notIn(SearchReservedParams::KEYS)` on attribute `code`. Thin CRUD. Persist `SearchNormalizer::textNormalize` on both terms.

- [ ] **Step 4: Pass tests.**

- [ ] **Step 5: Commit** `feat: add search synonyms admin and reserved attribute codes`

---

### Task 6: Label-change reindex + rebuild control

**Files:**
- Modify: `modules/Catalog/src/Services/AttributeValueService.php` (`update`)
- Modify: `modules/Product/src/Http/Controllers/Admin/ProductSettingsController.php` + `modules/Product/resources/views/admin/settings/index.blade.php` — button POST that runs `ProductSearchIndexer::reindexAll()` (same as `product:reindex`)
- Test: `tests/Feature/Product/SearchReindexTriggersTest.php`

**Interfaces:**
- Label update → `ProductSearchIndexer::index` each product that has PAV with that `attribute_value_id`
- Rebuild → flush+reindex all (existing `reindexAll`; add `SearchIndexInterface::flush` if missing then reindex)

- [ ] **Step 1: Failing tests** — rename Red → Crimson Red; search `crimson` finds the product; `?color=red` still matches; synonym change does not rewrite documents.

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: After `$value->update`, query distinct `product_id` from `product_attribute_values` and index those products. Settings form: authorize `product.product.view` (same as settings page).

- [ ] **Step 4: Pass tests.** `php artisan product:reindex` still works.

- [ ] **Step 5: Commit** `feat: reindex products when attribute labels change`

**Stop for human Wave 2 review.** Do not start storefront URL/facet chrome until approved.

---

## Wave 3 — Storefront search / facets / URL

### Task 7: URL contract (`q` + one param per filterable attribute)

**Files:**
- Modify: `modules/Cart/src/DTO/ShopListingFilters.php` — `q` (alias `search`), `attributes: array<string, string>` keyed by `attributes.code`; keep `size`/`color` as aliases that copy into `attributes` when those keys are absent so old `?color=` links work
- Test: `tests/Feature/Storefront/ShopListingFiltersTest.php`

**Interfaces:**
- `fromRequest`: `q` else `search`; skip reserved keys; remaining keys that match a filterable attribute code (single string value) go in `attributes`
- `toQueryArray` emits `q` and each attribute param; does not emit empty

- [ ] **Step 1: Failing test** — `?q=tee&material=cotton&search=ignored` uses `q=tee` when both present (`q` wins); `?search=tee` fills `q`; `?color=red` still works.

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Implement.** Do not treat `brand`/`category` as attributes.

- [ ] **Step 4: Pass tests.**

- [ ] **Step 5: Commit** `feat: parse shop search q and filterable attribute params`

---

### Task 8: Self-excluding facet counts + all filterable attributes

**Files:**
- Modify: `modules/Cart/src/DTO/ShopFilterCatalog.php` — add `facets: list<array{code: string, name: string, values: list<array{code: string, label: string, count: int}>}>`
- Modify: `modules/Cart/src/Services/ShopFilterCatalogService.php` — `buildFor(ShopListingFilters $filters, list<string> $searchUuids): ShopFilterCatalog` **or** a new `ShopFacetCounter` used by the shop controller so counts see the current search set
- Test: `tests/Feature/Cart/ShopFacetCountTest.php`

**Interfaces:**
- Facets = every `is_filterable` attribute (not only size/color config groups)
- Counts: search set (all published if `q` empty; else discovery uuids) + other filters except this dimension
- Attribute filter rules: Phase 1 `applyAttributeGroupFilter` generalized to any attribute id list + value code
- `constrainInStock` unchanged

- [ ] **Step 1: Failing test** — spec §12.7 (`q=tee&color=red` → Color still counts `blue`; Size restricted to red tees). Spec §12.8 `material=cotton`.

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Implement SQL counts. Keep `sizes`/`colors` populated from the same facet list for any leftover Blade during the same task, then point shop sidebar at `facets`.

- [ ] **Step 4: Pass tests.** Existing `StorefrontShopFilterChromeTest` updated to codes + new facet list.

- [ ] **Step 5: Commit** `feat: compute self-excluding shop facet counts from relations`

---

### Task 9: Wire listing to discovery query + regression

**Files:**
- Modify: `modules/Cart/src/Services/ShopProductQuery.php` — empty `q`: no `ProductDiscoveryQuery`. Non-empty: `candidateUuids`; if `[]` empty paginator; `whereIn` + preserve order when `sort === 'latest'` via `orderByRaw('FIELD(products.uuid, ...)')`; price sort still overrides
- Modify: shop Blade search input `name="q"` (keep reading alias)
- Test: `tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php` covering spec §12.1–15 that are storefront-visible; run with `CatalogV2Phase1RegressionTest` to ensure Phase 1 still green

**Interfaces:**
- Consumes: Task 4 + 7 + 8
- Do not inject `SearchQueryInterface` for storefront listing anymore

- [ ] **Step 1: Failing tests** for empty `q` (no index), non-default SKU rank on `/shop?q=`, `material=cotton`, in-stock still `constrainInStock`.

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Wire. Cap `FIELD()` lists reasonably or join a ranked temp — for V1 `whereIn` + `FIELD` on the candidate list is enough.

- [ ] **Step 4: Run** `php vendor/bin/phpunit tests/Feature/Product/CatalogV2Phase1RegressionTest.php tests/Feature/Product/ProductDiscoveryQueryTest.php tests/Feature/Cart/ShopFacetCountTest.php tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php tests/Feature/Storefront/StorefrontShopFilterChromeTest.php tests/Feature/Storefront/Ws002ShopListingContractTest.php`

- [ ] **Step 5: Commit** `feat: drive shop listing search from discovery query`

**Stop for human Wave 3 / Phase 2 review.**

---

## Spec coverage (plan self-review)

| Spec | Task |
|---|---|
| NFC + shared normalize | 1 |
| Document fields + attribute dedupe | 2 |
| Directed synonym replace | 3 |
| Pipeline only when `q` non-empty | 4, 9 |
| Exact SKU wins; AND/OR; exact token | 4 |
| Highest-field ranking | 4, 9 |
| Reserved URL namespace | 5, 7 |
| Synonym admin; no reindex on CUD | 5, 6 |
| Label reindex; full rebuild | 6 |
| URL one param per attribute; `q` alias | 7 |
| Self-excluding facets; all `is_filterable` | 8 |
| `constrainInStock` / Phase 1 filter rules | 8, 9 |
| No JSON in indexer | 2 |

---

## Out of this plan

Suggest/autocomplete, Meilisearch, merchandising, multi-select, prefix tokens, CSV/API/feeds, PDP edits, Stock V1, deleting JSON columns, upgrading `AdminGlobalSearchService` off `LIKE`.
