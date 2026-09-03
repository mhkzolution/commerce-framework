# WS-002 Shop Listing Layout — Feature (v1.11.0)

**Date:** 2026-09-03  
**Status:** Locked as next storefront milestone  
**Owner:** `resources/views/components/storefront` + `resources/css/storefront` + Cart shop listing  
**Mode:** Feature development (not recovery)

```text
v1.10.0 Product Card System
v1.11.0 Shop Listing Layout     ← this file
v1.12.0 Header Foundation
v1.13.0 Blog Refresh
```

Do not start Header, Appearance, Scanner, or restyle the product card / PDP.

Do not merge:

```text
feat/homepage-cms-preservation
feat/barcode-stock-recovery
feat/commerce-framework-v1
```

---

## What this is

The first storefront release the shopper should *feel*. Shop listing leaves the admin-width chrome and joins Homepage's container system. Product tiles stay `x-storefront.cards.product`.

```text
Homepage  page-container
Shop      page-container     ← this tag
Blog      waits for v1.13 (no blog redesign)
```

---

## Problem

Shop is still a different era from Homepage:

```text
main max-w-5xl
x-admin.search-input
<p> empty copy
Laravel default pagination
/shop?category= ignored (Homepage tabs already link here)
```

---

## Definition of Done (v1.11)

```text
Layout        Shop uses x-storefront.layout.page-container
              Shop main is full-bleed (not max-w-5xl)
Toolbar       x-storefront.shop.toolbar (count, sort, view-mode seam)
Empty         Shop uses x-storefront.empty-state
Pagination    storefront pagination view (not Laravel default look)
Filters       GET category + availability (no Ajax, no faceted search)
Card          unchanged
Header        unchanged (still inline max-w-5xl)
```

---

## Owner

```text
WS-002     page-container (already), shop toolbar, shop CSS, pagination view, empty-state styles
Cart       ShopController + listing query + shop.blade.php
Catalog    category options (via existing query / HomepageNavigationQuery)
Inventory  in-stock constraint in the listing query, not the Blade
```

---

## Out of scope

```text
Header extract / mega menu / mobile drawer
Product card changes
PDP redesign
Blog redesign / Blog page-container
Appearance / theme engine
Faceted search / Ajax
Brand filter
New /category/{slug} route
```

---

## Implementation spec

`docs/superpowers/specs/2026-09-03-ws002-shop-listing-v1-implementation.md`
