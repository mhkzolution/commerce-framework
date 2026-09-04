# Storefront Header / Shop / Filter / Cart / Login — Feature (v1.18.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Mode:** Path-extract from `commerce-framework-old/` onto current working tree. Not archive merge. Not wholesale copy.

```text
v1.16.0 POS + Warehouse Scanner
v1.17.0 CX / Appearance / header / workspace / media bulk
v1.18.0 Header polish, shop listing, GET shop filters, cart, login  ← this file
```

Implementation: `docs/superpowers/specs/2026-09-04-storefront-shop-filter-chrome-v1-implementation.md`

Do not commit `commerce-framework-old/`.

---

## 1. Surfaces

```text
GET /shop                         filters + listing chrome (no Ajax partial)
GET /cart                         shopper chrome, CartData lines
GET /account/login                shopper chrome (not archive auth layout)
site-header                       HeaderViewData only
```

---

## 2. Definition of Done

```text
Shop GET     brand, price_min/max (major units in query, cents in DB), size, color
Shop GET     keep category, availability, search, sort already in WS-002
Shop UI      sidebar + breadcrumb when filters/search active; keep availability
Header       prefill search from ?search=; no mega-menu / SiteIdentity
Cart         two-column layout + line image when media URL exists; no cf-* / x-admin
Login        welcome copy on shopper chrome; no recaptcha / cart::layouts.auth
Prices       CreateProductData / variant.price stay minor units (cents)
```

---

## 3. Out of this restore

```text
mega-menu / primary-nav sliders / StorefrontHeaderComposer arrays
Ajax infinite scroll (?partial=1) / shop.js
collections / catalog landing routes
hiding availability (current tests require it)
recaptcha, cart::layouts.auth, x-storefront.auth.*
recommendation rails / recently viewed on cart
WooCommerce CSV / translation manager
```
