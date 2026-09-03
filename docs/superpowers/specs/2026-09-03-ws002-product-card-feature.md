# WS-002 Product Card System — Feature (v1.10.0)

**Date:** 2026-09-03  
**Status:** Locked as next storefront milestone  
**Owner:** `resources/views/components/storefront/cards` + `resources/css/storefront`  
**Mode:** Feature development (not recovery)

```text
v1.9.0  WS-002 Foundations
v1.9.1  Footer Token Adoption
v1.10.0 Product Card System     ← this file
v1.11.0 Shop Listing Layout
v1.12.0 Header Foundation
v1.13.0 Blog Refresh
```

Do not start Header, Appearance, or Scanner. Product card is the primitive every merchandising surface needs.

Do not merge:

```text
feat/homepage-cms-preservation
feat/barcode-stock-recovery
feat/commerce-framework-v1
```

---

## What this is

One storefront product tile. One DTO. Homepage and Shop both render it.

```blade
<x-storefront.cards.product :product="$card" />
```

`$card` is `Commerce\Contracts\Storefront\ProductCardData`. Never Eloquent.

---

## Problem

Two worlds:

```text
Homepage
  HomepageProductCardData
  home-product-card
  image / name / price / url / inStock

Shop
  $product->defaultVariant()
  Tailwind article + admin cf-btn
  inventory / images in the view
```

```text
Homepage card ≠ Shop card
```

---

## Definition of Done (v1.10)

```text
Primitive     x-storefront.cards.product
DTO           ProductCardData only
Homepage      arrivals use the primitive (no home-product-card markup)
Shop          listing tiles use the same primitive
Blade         no Product model, defaultVariant(), inventory, or images relation
```

Shop **page chrome** (title, admin search, pagination wrapper) stays until v1.11.

---

## Owner

```text
WS-002     primitive + CSS
Cart       maps Product → ProductCardData (Homepage + Shop)
Catalog    product records
Inventory  availability via query service in the mapper, not the Blade
```

---

## Out of scope

```text
Header extract
PDP restyle
Shop filters / toolbar / page-container
Blog cards
Wishlist
Appearance / SiteIdentity
archive design-system merge
```

---

## Implementation spec

`docs/superpowers/specs/2026-09-03-ws002-product-card-v1-implementation.md`
