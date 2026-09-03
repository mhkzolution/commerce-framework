# WS-002 Shop Listing v1 — Implementation Spec (v1.11.0)

**Date:** 2026-09-03  
**Status:** Locked  
**Tag:** v1.11.0  
**Branch:** `feat/ws002-shop-listing-v1`

Feature: `docs/superpowers/specs/2026-09-03-ws002-shop-listing-feature.md`

```text
git merge feat/commerce-framework-v1
git checkout 84e905c -- .
```

---

## 1. Layout

Shop overrides `@section('main_class', 'storefront-shop-main')` so `<main>` is full width.

Content wraps in:

```blade
<x-storefront.layout.page-container class="storefront-shop">
```

Do not change `layouts/storefront.blade.php` header (`max-w-5xl` stays until v1.12).

Do not wrap Blog.

---

## 2. Toolbar

```text
resources/views/components/storefront/shop/toolbar.blade.php
```

`x-storefront.shop.toolbar`

Props: `count` (int), `sort` (string), `query` (array of current GET params to preserve).

Renders:

```text
result count
sort (latest | price_asc | price_desc) via x-storefront.forms.sort-dropdown
view-mode seam: empty .storefront-shop-toolbar__view (no controls)
```

Sort is a GET form to `storefront.shop.index` with hidden fields for other params.

---

## 3. Empty state

Shop `@empty` uses existing `x-storefront.empty-state`.

No new empty-state component. Add tokenized CSS for `.storefront-empty` (component is currently unstyled).

Search / category / in-stock with zero hits all use the same primitive.

---

## 4. Pagination

```text
resources/views/vendor/pagination/storefront.blade.php
resources/css/storefront/shop.css   (.storefront-pagination)
```

Shop: `$products->withQueryString()->links('pagination.storefront')`.

Do not change Laravel paginator backend. Do not change admin `links()`.

---

## 5. Filters (GET, no Ajax)

Query params:

```text
search          existing
category        slug | omitted (Homepage tabs already emit ?category=)
availability    all (default) | in_stock
sort            latest (default) | price_asc | price_desc
```

No `/category/{slug}` route. No brand. No faceted aggregations.

Category options: all active catalog categories with slugs (not the homepage `take(8)` tab cap). Map to `HomepageNavigationData` (or the same shape). Blade receives DTOs, not `Category` models.

Availability `in_stock`: SQL `(on_hand - reserved) > 0` on `inventory_items` for a default (or first) variant. If the inventory table is missing, skip the constraint.

Search: keep the storefront search index to resolve UUIDs, then apply category / availability / sort / page in SQL. Do not build a faceted index.

---

## 6. Listing query

`Commerce\Cart\Services\ShopProductQuery` — Cart owns listing the way `HomepageProductQuery` owns arrivals.

`ShopController::index` parses request → query → `ProductCardMapper`. `show()` unchanged.

Blade still receives `ProductCardData` only.

Do not edit `x-storefront.cards.product` or `product-card.css`.

---

## 7. CSS

```text
resources/css/storefront/shop.css
```

Vite on the shop view (same pattern as `home.css`). Import empty-state rules here or in a small sheet pulled from `app.css`.

Tokens only: `--store-max-width` comes from page-container; shop uses `--space-*`, `--font-store`, `--color-text` / `--color-muted`. No `77.5rem`. No header restyle.

Replace `x-admin.search-input` with a storefront search field.

---

## 8. Isolation

`tests/Unit/Storefront/Ws002ShopListingIsolationTest.php` first (red).

Update v1.10 assertions that Shop must **not** use `page-container`: Shop **must** use it; Shop still must **not** use `site-header`; product card Blade stays Eloquent-free.

Footer v1.9.1 test that forbids shop `page-container` must drop that assertion (header extract still forbidden).

---

## 9. Sequence

```text
isolation red
shop.css + page-container on Shop
toolbar + empty-state + pagination view
ShopProductQuery filters
full suite
```
