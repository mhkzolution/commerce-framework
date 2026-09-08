# Catalog V2 Facet Universe Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Shop listing and facet SQL filter each `attributes.code` independently so `?size=` does not match `shoe_size`.

**Architecture:** Stop applying reserved `size`/`color` through config group id lists. Apply `$filters->attributes` only: one filterable attribute per exact code, `whereIn` / skip missing codes, never `firstOrFail()`. Remove `ShopFilterCatalog` group fields once listing and facets no longer read them.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, existing shop listing + relation facets

**Spec:** `docs/superpowers/specs/2026-09-08-catalog-v2-facet-universe-design.md` (Locked)

**Start gate:** Catalog V2 Phase 3 Complete on `main` (`90f7090`). Work on `feat/catalog-v2-facet-universe`.

## Global Constraints

- Do not change discovery ranking, synonyms, suggest, `constrainInStock()`, or PDP combination rules.
- Do not merge `size` and `shoe_size` into one facet.
- Do not `Attribute::where('code', $code)->firstOrFail()` for request params.
- Do not delete `cart.storefront.filters.groups` from config (may remain unread).
- Human gate after the wave. Do not start other Phase 2 follow-ups in this plan.

---

## File map

| Area | Files |
|---|---|
| Listing | Modify `modules/Cart/src/Services/ShopProductQuery.php` |
| Facet catalog | Modify `modules/Cart/src/Services/ShopFilterCatalogService.php` |
| DTO | Modify `modules/Cart/src/DTO/ShopFilterCatalog.php` |
| Tests | Create `tests/Feature/Storefront/ShopFacetUniverseTest.php`; modify `tests/Feature/Cart/ShopAttributeFilterTest.php`, `tests/Feature/Product/CatalogV2Phase1RegressionTest.php`, `tests/Feature/Cart/ShopFacetCountTest.php` |

---

## Shared helpers (use in tests)

Value identity is `attribute_values.code`. Create values with explicit `code: 's'` (or allocate and assert). Filterable attributes `size` and `shoe_size` must both exist. Products need `visibleOnStorefront()` (published + public + in-stock as in other shop tests).

Facet counts: `collect($catalog->facets)->firstWhere('code', $code)` then map `values` to `code => count`.

`ShopListingFilters` already copies request `size`/`color` into `$attributes`. Listing must use that map only.

---

### Task 1: Exact-code listing predicates

**Files:**
- Modify: `modules/Cart/src/Services/ShopProductQuery.php`
- Test: `tests/Feature/Storefront/ShopFacetUniverseTest.php`

**Interfaces:**
- `ShopProductQuery::paginate(ShopListingFilters $filters, ShopFilterCatalog $catalog, int $perPage = 24, ?array $searchUuids = null)` signature stays. Do not read `$catalog->sizeAttributeIds` or `$catalog->colorAttributeIds`.
- Attribute filters: `foreach` codes in `$filters->attributes`; load filterable attributes with `whereIn('code', array_keys($attributes))`; skip codes with no row.

- [ ] **Step 1: Failing tests**

```php
public function test_size_param_does_not_match_shoe_size(): void
{
    [$apparel, $shoe] = $this->sizeAndShoeProducts();

    $this->get(route('storefront.shop.index', ['size' => 's']))
        ->assertOk()
        ->assertSee($apparel->name)
        ->assertDontSee($shoe->name);

    $this->get(route('storefront.shop.index', ['shoe_size' => 's']))
        ->assertOk()
        ->assertSee($shoe->name)
        ->assertDontSee($apparel->name);
}

public function test_size_and_shoe_size_facets_do_not_share_counts(): void
{
    [$apparel, $shoe] = $this->sizeAndShoeProducts();

    $catalog = $this->get(route('storefront.shop.index'))
        ->assertOk()
        ->viewData('filterCatalog');

    $this->assertSame(['s' => 1], $this->facetCounts($catalog, 'size'));
    $this->assertSame(['s' => 1], $this->facetCounts($catalog, 'shoe_size'));
    $this->assertNotSame($apparel->id, $shoe->id);
}

public function test_unknown_and_reserved_params_do_not_abort_or_attribute_filter(): void
{
    $visible = $this->sizeAndShoeProducts()[0];

    $this->get(route('storefront.shop.index', ['foo' => 'bar']))
        ->assertOk()
        ->assertSee($visible->name);

    $this->get(route('storefront.shop.index', ['page' => 2]))
        ->assertOk();

    $this->get(route('storefront.shop.index', ['brand' => 'red']))
        ->assertOk();
}
```

Helper: two filterable attributes `size` and `shoe_size`, each with value code `s`. Product A product-level PAV on `size`. Product B product-level PAV on `shoe_size`. Follow `StorefrontShopFilterChromeTest::attachAttributeValue` (non-axis, `product_variant_id` null).

- [ ] **Step 2: Run, fail**

`php vendor/bin/phpunit tests/Feature/Storefront/ShopFacetUniverseTest.php`

Expect: `?size=s` still shows the shoe product (group `str_contains`).

- [ ] **Step 3: Implement listing**

In `paginate`, delete:

```php
$this->applyAttributeGroupFilter($query, $catalog->sizeAttributeIds, $filters->size);
$this->applyAttributeGroupFilter($query, $catalog->colorAttributeIds, $filters->color);
```

Call one method on `$filters->attributes` (rename `applyAdditionalAttributeFilters` → `applyAttributeFilters` if you touch it). **Do not** `unset($attributes['size'], $attributes['color'])`. Keep:

```php
$filterableAttributes = Attribute::query()
    ->where('is_filterable', true)
    ->whereIn('code', array_keys($attributes))
    ->get(['id', 'code']);
```

No `firstOrFail()`. Empty `$attributes` → return.

`$catalog` may be unused for ids; leave the parameter.

- [ ] **Step 4: Pass** `php vendor/bin/phpunit tests/Feature/Storefront/ShopFacetUniverseTest.php`

- [ ] **Step 5: Commit** `fix: apply shop attribute filters by exact code`

---

### Task 2: Drop group ids from the catalog DTO

**Files:**
- Modify: `modules/Cart/src/DTO/ShopFilterCatalog.php`
- Modify: `modules/Cart/src/Services/ShopFilterCatalogService.php`
- Modify: `tests/Feature/Cart/ShopAttributeFilterTest.php`
- Modify: `tests/Feature/Product/CatalogV2Phase1RegressionTest.php`
- Modify: `tests/Feature/Cart/ShopFacetCountTest.php`

**Interfaces:**
- `ShopFilterCatalog` constructor no longer has `sizes`, `colors`, `sizeAttributeIds`, `colorAttributeIds`. Facets stay.
- `buildFor` does not call `groupAttributes` / `legacyOptions` / `resolveGroup`. Do not read `cart.storefront.filters.groups`. Leave the config key in `modules/Cart/config/cart.php`.
- `filteredProducts` already applies `$filters->attributes[$code]` per exact code — keep that.

- [ ] **Step 1: Failing / red from leftover callers**

Update tests first so they compile against the new DTO:

`ShopAttributeFilterTest::test_simple_product_matches_non_axis_material_by_value_code`:

```php
$results = app(ShopProductQuery::class)->paginate(
    new ShopListingFilters(attributes: ['material' => 'cotton']),
    new ShopFilterCatalog,
);
```

(Use the actual allocated value code if it is not `cotton`.)

`CatalogV2Phase1RegressionTest` simple-product `paginate`: same pattern with `$material->code` and the cotton value code — not `color:` + `colorAttributeIds`.

HTTP `?color=red` in that file: replace with `[$color->code => 'red']` (and `'green'`) because the fixture code is `color-p1-{uniqid}`. Production `?color=` stays valid only when `attributes.code === 'color'`.

Delete `ShopFacetCountTest::test_legacy_colors_include_values_from_grouped_colour_attribute` (group merge is out of spec). Optional: assert a facet with `code === 'colour'` instead.

- [ ] **Step 2: Run** `php vendor/bin/phpunit tests/Feature/Cart/ShopAttributeFilterTest.php tests/Feature/Product/CatalogV2Phase1RegressionTest.php tests/Feature/Cart/ShopFacetCountTest.php`

Expect failures/errors until DTO + service match.

- [ ] **Step 3: Strip grouping**

`ShopFilterCatalogService::buildFor`: stop computing `$grouped`; stop passing the four removed constructor args.

Delete `groupAttributes`, `resolveGroup`, `matchesCodes`, `legacyOptions` if nothing else calls them.

- [ ] **Step 4: Pass plus regressions**

```
php vendor/bin/phpunit tests/Feature/Storefront/ShopFacetUniverseTest.php tests/Feature/Cart/ShopAttributeFilterTest.php tests/Feature/Product/CatalogV2Phase1RegressionTest.php tests/Feature/Cart/ShopFacetCountTest.php tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php tests/Feature/Storefront/StorefrontShopFilterChromeTest.php
```

- [ ] **Step 5: Commit** `fix: remove shop filter attribute group ids`

**Stop for human Wave review.**

---

## Spec coverage (plan self-review)

| Spec | Task |
|---|---|
| Exact `?size=` vs `shoe_size` | 1 |
| Facet counts per code | 1 |
| Unknown / reserved params no `firstOrFail` | 1 |
| Listing does not read group ids | 1, 2 |
| Remove DTO fields if unused | 2 |
| Phase 1 tests use actual attribute code | 2 |
| Discovery / suggest / stock untouched | both (do not edit those modules except if a test import requires it) |

## Out of this plan

Octane synonym map, attribute code rewrite on update, index rebuild/cap, suggest ARIA/throttle/i18n, deleting `filters.groups` from config.
