# WS-002 PDP Refresh v1 — Implementation Spec (v1.14.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Tag:** v1.14.0  
**Branch:** `feat/ws002-pdp-v1`

Feature: `docs/superpowers/specs/2026-09-04-ws002-pdp-refresh-feature.md`

Inventory is in that feature spec (section 1). Design from current `main`, not archive.

```text
git merge feat/commerce-framework-v1
git merge feat/homepage-cms-preservation
git merge feat/barcode-stock-recovery
git checkout 84e905c -- .
```

Do not extract archive PDP markup. Rewrite onto current tokens if a single archive file is still the best *reference*.

---

## 1. Layout

Mirror Shop / Blog:

```blade
@section('main_class', 'storefront-pdp-main')
```

```blade
<x-storefront.layout.page-container class="storefront-pdp">
```

Do not change `layouts/storefront.blade.php` default `<main>` (`max-w-5xl` stays for cart / checkout / account). Do not render `site-header` from the PDP view.

Two-column buy box on large viewports stays (media | info). Use PDP CSS + tokens, not `lg:grid-cols-2` / `cf-btn` / `cf-input`.

Register `resources/css/storefront/pdp.css` in Vite and `@vite` it from the PDP view the same way Shop loads `shop.css` (one extra CSS entry is fine; do not add a second JS `@vite` client).

---

## 2. DTO + builder

```text
packages/commerce/contracts/src/Storefront/ProductDetailData.php
modules/Cart/src/Services/ProductDetailBuilder.php   (or Mapper — Cart-owned)
```

`ProductDetailData` is the only object the Blade may read. Suggested fields (names may match this list):

```text
name
description          (plain text; Blade may nl2br(e()) )
imageUrl             ?string  (primary media, same resolution strategy as ProductCardMapper)
price                int      already converted to display currency
compareAtPrice       ?int
displayCurrency      string
sku                  ?string
available            ?int
inStock              bool
variantUuid          string
shopUrl              string
```

Builder:

- Resolves the storefront product (existing `findStorefrontBySlug`)
- Picks the **default variant** (same rule as `ProductCardMapper`)
- Loads primary image via `MediaQueryServiceInterface`
- Loads availability via `InventoryQueryServiceInterface` (fail-soft like the card)
- Converts price in the builder, not the Blade
- Returns `null` → 404 (controller)

Controller `show` passes only `ProductDetailData` (plus CSRF/form needs nothing else). Do not pass Eloquent `Product`, `ProductVariant`, or `CurrencyConverterInterface` to the view.

Do not invent a second product-card DTO. Do not change `ProductCardData`.

---

## 3. Image

When `imageUrl` is present, render `<img src="..." alt="{name}">`.  
When it is not, render a tokenized placeholder (no "empty box that pretends media exists").

Current bug to fix: `media->isNotEmpty()` draws a blank square and never sets `src`.

No gallery, no zoom, no video.

---

## 4. Cart form

Keep:

```text
POST storefront.cart.items.store
purchasable_uuid = variantUuid
quantity  (number input, min 1, max available when known)
```

Hide the form when `! inStock`. Use storefront classes, not `cf-btn` / `cf-input`. Copy from `storefront::storefront.*` (`add_to_cart`, `in_stock` / `out_of_stock`).

`StorefrontProductTest` currently asserts the hardcoded string `Add to cart`. Update it to the lang key output (`Add` in `en`) when the form uses `storefront::storefront.add_to_cart`.

---

## 5. Breadcrumb

Shop → product name. Local PDP markup or `x-storefront.breadcrumb` **only if** `aria-label` is not hardcoded to `cms::blog.breadcrumb`. Prefer an optional `aria-label` prop on the existing adapter (Blog keeps its default). Do not add CMS blog translation keys to the PDP.

---

## 6. Isolation / contract tests

Add:

```text
tests/Unit/Storefront/Ws002PdpIsolationTest.php
tests/Unit/Cart/ProductDetailBuilderTest.php
tests/Feature/Storefront/Ws002PdpContractTest.php
```

Isolation must fail (red) until production changes land:

```text
product.blade.php uses x-storefront.layout.page-container
product.blade.php uses storefront-pdp-main
product.blade.php contains ProductDetailData
product.blade.php does not contain defaultVariant, CurrencyConverter, cf-btn, cf-input, x-admin.
product.blade.php does not contain site-header / Setting:: / @auth
pdp.css exists, uses tokens, does not set 77.5rem / 87.5rem
Shop / Blog / Header views do not embed PDP chrome
```

`StorefrontProductTest` stays (accessible + 404). Extend or add contract: page-container class in HTML, `<img>` when the product has media, add-to-cart form present when in stock.

Existing WS-002 isolation tests that mention PDP only as "do not restyle" do not need to forbid page-container on the PDP after this tag — v1.14 owns that surface. Product **card** isolation stays: card Blade still has no Eloquent / `defaultVariant`.

---

## 7. Out of this branch

```text
HeaderViewData / site-header markup
Shop listing / blog views
Product card primitive
Variant option picker
Gallery / zoom
Related products
Cart / checkout / account
page.blade.php
Appearance
```

---

## 8. Sequence

```text
isolation tests red
↓
ProductDetailData + builder
↓
ShopController::show returns DTO or 404
↓
product.blade.php page-container + DTO-only
↓
pdp.css + vite entry
↓
image src + storefront cart form
↓
update StorefrontProductTest copy assertion
↓
contract tests
↓
PR squash → tag v1.14.0
```
