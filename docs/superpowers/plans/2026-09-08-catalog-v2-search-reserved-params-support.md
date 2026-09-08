# Catalog V2 SearchReservedParams Support Move Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move `SearchReservedParams` to `commerce/support` so Catalog and Cart no longer import Product for the reserved shop-URL key list.

**Architecture:** Copy `KEYS` byte-for-byte into `Commerce\Support\SearchReservedParams`. Update every PHP reference. Delete the Product class. Declare `commerce/support` on Catalog and Product. No alias. No behavior change.

**Tech Stack:** Laravel 13, PHP 8.4, Composer PSR-4, PHPUnit

**Spec:** `docs/superpowers/specs/2026-09-08-catalog-v2-search-reserved-params-support-design.md` (Locked)

**Start gate:** `main` at `a6daf97`. Work on `feat/catalog-v2-search-reserved-params-support`.

## Global Constraints

- `KEYS` stays `['q', 'category', 'brand', 'sort', 'availability', 'price_min', 'price_max', 'page']`.
- Hard cut: delete `modules/Product/src/Support/SearchReservedParams.php`. No `class_alias`.
- All PHP references to `SearchReservedParams` must resolve to `Commerce\Support\SearchReservedParams`. Extra callers found during grep are in scope.
- Do not change create/update reserved semantics, importer, discovery, suggest, facets, or expand `KEYS`.
- Human gate after the wave.

---

## File map

| Area | Files |
|---|---|
| New | Create `packages/commerce/support/src/SearchReservedParams.php` |
| Delete | `modules/Product/src/Support/SearchReservedParams.php` |
| Callers (known) | Modify `modules/Catalog/src/Services/AttributeService.php`, `modules/Catalog/src/Http/Requests/StoreAttributeRequest.php`, `modules/Product/src/Http/Requests/StoreVariantOptionPresetRequest.php`, `modules/Cart/src/DTO/ShopListingFilters.php` |
| Composer | Modify `modules/Catalog/composer.json`, `modules/Product/composer.json` |

Grep `SearchReservedParams` across `*.php` before finishing. Update any extra hit.

---

### Task 1: Move the class and cut Product

**Files:**
- Create: `packages/commerce/support/src/SearchReservedParams.php`
- Delete: `modules/Product/src/Support/SearchReservedParams.php`
- Modify: the four known callers + any extra PHP hits
- Modify: `modules/Catalog/composer.json`, `modules/Product/composer.json`

**Interfaces:**
- Consumes: current `KEYS` array from Product (copy verbatim).
- Produces: `final class Commerce\Support\SearchReservedParams` with `public const KEYS = [...]`.

- [ ] **Step 1: Failing check — old FQCN still exists**

```bash
rg -n "Commerce\\\\Product\\\\Support\\\\SearchReservedParams" --glob '*.php'
```

Expected: hits in Catalog, Product, and Cart PHP (today). After the task this command must print nothing.

- [ ] **Step 2: Add the Support class**

Create `packages/commerce/support/src/SearchReservedParams.php`:

```php
<?php

declare(strict_types=1);

namespace Commerce\Support;

final class SearchReservedParams
{
    public const KEYS = ['q', 'category', 'brand', 'sort', 'availability', 'price_min', 'price_max', 'page'];
}
```

Do not add methods, comments, or extra keys.

- [ ] **Step 3: Point every PHP caller at Support; delete Product class**

Replace

```php
use Commerce\Product\Support\SearchReservedParams;
```

with

```php
use Commerce\Support\SearchReservedParams;
```

in every PHP file `rg` finds (at least the four known callers). Delete `modules/Product/src/Support/SearchReservedParams.php`. Do not leave a stub or alias.

Add to `require` in `modules/Catalog/composer.json` and `modules/Product/composer.json`:

```json
"commerce/support": "*"
```

Keep existing requires. Cart already has `commerce/support`.

- [ ] **Step 4: Autoload and tests**

```bash
composer dump-autoload
php artisan test --compact \
  tests/Feature/Catalog/AttributeReservedCodeTest.php \
  tests/Feature/Catalog/VariantOptionReservedCodeTest.php \
  tests/Feature/Cart/ShopAttributeFilterTest.php
```

Expected: PASS. Confirm:

```bash
rg -n "Commerce\\\\Product\\\\Support\\\\SearchReservedParams" --glob '*.php'
php -r "require 'vendor/autoload.php'; echo Commerce\\Support\\SearchReservedParams::class;"
```

First command: no PHP hits (this spec markdown may still mention the old FQCN). Second: prints `Commerce\Support\SearchReservedParams` without loading Product.

- [ ] **Step 5: Commit**

```bash
git add packages/commerce/support/src/SearchReservedParams.php modules/Product/src/Support/SearchReservedParams.php modules/Catalog/src/Services/AttributeService.php modules/Catalog/src/Http/Requests/StoreAttributeRequest.php modules/Product/src/Http/Requests/StoreVariantOptionPresetRequest.php modules/Cart/src/DTO/ShopListingFilters.php modules/Catalog/composer.json modules/Product/composer.json
git commit -m "$(cat <<'EOF'
refactor: move SearchReservedParams to commerce/support

EOF
)"
```

Include any extra caller files `rg` found.

---

## Spec coverage

| Spec | Task |
|---|---|
| New class in Support | Task 1 Step 2 |
| Hard cut Product class | Task 1 Step 3 |
| All PHP refs → `Commerce\Support` | Task 1 grep |
| Catalog + Product `commerce/support` | Task 1 composer |
| dump-autoload, no alias | Task 1 Step 4 |
| Reserved-code + shop filter regression | Task 1 Step 4 |
| No KEYS / importer / discovery change | Global constraints |
