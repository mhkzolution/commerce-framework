# Task 1 Report — Exact-code listing predicates

## Result

Shop listing attribute predicates now resolve every entry in
`ShopListingFilters::$attributes` by exact, filterable `attributes.code`.
Reserved `size` and `color` values are no longer applied through catalog
group IDs, while the `paginate` signature and catalog DTO remain unchanged.

## TDD evidence

### RED

```text
php vendor/bin/phpunit tests/Feature/Storefront/ShopFacetUniverseTest.php
FAILED (tests=3, passed=2, assertions=12, failed=1)
```

`?size=s` incorrectly rendered both the apparel-size and shoe-size products.

### GREEN

```text
php vendor/bin/phpunit tests/Feature/Storefront/ShopFacetUniverseTest.php
PASSED (tests=3, assertions=15)

php vendor/bin/phpunit tests/Feature/Storefront/StorefrontShopFilterChromeTest.php tests/Feature/Cart/ShopAttributeFilterTest.php
PASSED (tests=12, assertions=70)
```

IDE lint diagnostics reported no errors in the changed PHP files.

## Coverage

- `?size=s` and `?shoe_size=s` match only their exact attribute codes.
- Facet counts remain isolated per attribute code.
- Unknown and reserved query parameters return normally without attribute lookup failures.

## Files

- `modules/Cart/src/Services/ShopProductQuery.php`
- `tests/Feature/Storefront/ShopFacetUniverseTest.php`
- `.superpowers/sdd/task-1-report.md`

## Concerns

None. Task 2 DTO and grouping cleanup was intentionally not started.

## Follow-up — one predicate per requested code

`applyAttributeFilters` now keys filterable attribute rows by code and iterates
the request attribute map. Missing codes are skipped, and duplicate
tenant-scoped database rows can no longer add multiple predicates for one
requested code.

```text
php vendor/bin/phpunit tests/Feature/Storefront/ShopFacetUniverseTest.php
PASSED (tests=3, assertions=15)
```

IDE lint diagnostics reported no errors in the changed PHP service.

## Follow-up — reserved size alias and deterministic lookup

Added coverage for `GET /shop?size=s` when no filterable `size` attribute
exists. The request returns 200 and keeps an unrelated visible product in the
listing, while a `shoe_size` attribute exists independently.

The filterable attribute lookup now orders by `position`, then `id`, before
`keyBy('code')`, matching facet-universe duplicate-code resolution.

```text
php vendor/bin/phpunit tests/Feature/Storefront/ShopFacetUniverseTest.php
PASSED (tests=4, assertions=17)
```

The regression test passed before the ordering change because missing-code
skip behavior was already implemented. IDE lint diagnostics and
`git diff --check` reported no errors.
