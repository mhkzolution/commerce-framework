# WS-002 Product Card v1 — Implementation Spec (v1.10.0)

**Date:** 2026-09-03  
**Status:** Locked  
**Tag:** v1.10.0  
**Branch:** `feat/ws002-product-card-v1`

Feature: `docs/superpowers/specs/2026-09-03-ws002-product-card-feature.md`

```text
git merge feat/commerce-framework-v1
git checkout 84e905c -- .
```

---

## 1. DTO

```text
packages/commerce/contracts/src/Storefront/ProductCardData.php
```

```text
uuid
name
slug
url
variantUuid
price              int, minor units
compareAtPrice     ?int
imageUrl           ?string
available          ?int
inStock            bool
```

Same fields as today's `HomepageProductCardData`. Replace that Cart DTO. Do not keep two card types.

---

## 2. Primitive

```text
resources/views/components/storefront/cards/product.blade.php
resources/css/storefront/product-card.css
```

Import `product-card.css` from `app.css` after tokens.

Class: `storefront-product-card` (home.css already targets this under arrivals slides).

Props: `product` (`ProductCardData`), `displayCurrency`, optional `baseCurrency` / `currencyConverter`, `quickAdd` (default false), `priority` (default false).

Forbidden in the Blade:

```text
defaultVariant()
Commerce\Product\Models\Product
->inventory
->images
->media
->variants
```

Tokens: `--radius-store` on media, `--font-store`, `--color-text` / `--color-muted`.

Homepage: `quickAdd=false` (link tile). Shop: `quickAdd=true` (Add posts `variantUuid`).

`storefront.product.card` hook may run in the primitive with the **DTO** as `product` (not Eloquent).

---

## 3. Mapper

`Commerce\Cart\Services\ProductCardMapper` — the only place that reads Product / variant / media / inventory.

HomepageProductQuery and ShopController call it. Views do not.

Shop paginator collection becomes `ProductCardData` (skip products with no variant, same as today's `@if ($variant)`).

---

## 4. Consumers

```text
home-product-slides     x-storefront.cards.product
home-product-card       delete or one-line alias to the primitive
shop.blade.php          same primitive; no defaultVariant
```

v1.11 still owns: page-container on Shop, filters, replacing `x-admin.search-input`.

---

## 5. Isolation

`tests/Unit/Storefront/Ws002ProductCardIsolationTest.php` first (red).

v1.9.0 `Ws002IsolationTest` asserted Shop must **not** use the primitive. Update that assertion in this milestone: Shop **must** use it; Shop still must **not** use `page-container` or `site-header`.

---

## 6. Sequence

```text
isolation red
ProductCardData
primitive + CSS
mapper
Homepage + Shop consume
full suite
```
