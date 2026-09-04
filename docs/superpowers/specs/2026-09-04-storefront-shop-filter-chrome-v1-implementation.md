# Storefront Header / Shop / Filter / Cart / Login — Implementation Spec (v1.18.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Branch:** `feat/pos-warehouse-scanner-v1` (same working tree until user commits)

Feature: `docs/superpowers/specs/2026-09-04-storefront-shop-filter-chrome-feature.md`

---

## 1. Isolation

```text
Header blade / HeaderViewModelBuilder
  no SiteIdentityServiceInterface
  no AppearanceController / CustomerExperienceController
  no x-site.logo / x-site.favicon
  keep HeaderViewData + storefront-site-header
  keep Chromium ::details-content { content-visibility: visible; display: block; }

Shop blade
  no site-header embed
  no defaultVariant / Eloquent in card
  keep x-storefront.cards.product + ProductCardData

Cart / login
  storefront-shopper-main + page-container + shopper.css
  no cf-btn / cf-input / cf-flash / cf-badge / x-admin. / @auth / Setting::
  cart keeps $cart->lines
  no lg:grid-cols-2 (use storefront-cart__layout)

Layout <main> default stays max-w-5xl until a page sets main_class
```

---

## 2. Shop filters (GET, no Ajax)

- Extend `ShopListingFilters` + `ShopProductQuery` with `brand`, `price_min` / `price_max`, `size`, `color`
- Query `price_min` / `price_max` are **major units** (baht); compare against variant `price` in **cents** (`* 100`)
- Size / color from filterable attributes whose code/name matches size/color groups; exclude language
- Keep category + availability + search + sort
- Sidebar + active breadcrumb on current shop page-container
- No collection, no infinite scroll requirement

---

## 3. Header

- Add `HeaderActionData::$searchQuery` (default `''`) from the current request
- Prefill the header search input
- Hide brand **name** when a logo URL is present (img `alt` remains)
- Do not restore mega-menu composer arrays

---

## 4. Cart

- Optional `ResolvedCartLineData::$imageUrl` via `MediaQueryServiceInterface`
- Two-column `storefront-cart__layout` (lines + summary)
- Breadcrumb Shop → Cart
- Keep shopper classes; no `cf-*` / `x-admin`

---

## 5. Login

- Keep shopper layout
- Welcome heading + lede from storefront lang
- No recaptcha / `cart::layouts.auth` extract
- No forgot-password link unless a named route already exists

---

## 6. Tests

```text
tests/Feature/Storefront/StorefrontShopFilterChromeTest.php
  price + size + color (cents DB, major GET)
  brand + size/color UI; KEEP name="availability"
  breadcrumb on search; none without filters
  header search input prefilled from ?search=

tests/Feature/Storefront/Ws002ShopperChromeContractTest.php
  cart line image when media URL exists
  login welcome lede

existing Ws002ShopListingContractTest, Ws002HeaderIsolationTest,
Ws002ShopperChromeIsolationTest stay green
```
