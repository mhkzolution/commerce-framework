# WS-002 Header Foundation — Feature (v1.12.0)

**Date:** 2026-09-03  
**Status:** Locked  
**Owner:** `resources/views/components/storefront/layout` + `resources/css/storefront`  
**Mode:** Feature development on the current release line (not recovery)

```text
v1.9.0  Foundations
v1.9.1  Footer Tokens
v1.10.0 Product Card
v1.11.0 Shop Listing            ← done
v1.12.0 Header Foundation       ← this file
v1.13.0 Blog Refresh
```

Dependencies Header needs are already on `main`:

```text
✓ Design tokens
✓ page-container
✓ NavigationQueryService::links('main')
✓ Website Settings (store.name, logo)
✓ Product Card system
✓ Shop listing layout
✓ Footer composition
```

v1.12 is not "recover the archive header". It is a new header designed on this release line.

```text
git merge feat/commerce-framework-v1
git merge feat/homepage-cms-preservation
git merge feat/barcode-stock-recovery
git checkout 84e905c -- .
```

Do not path-extract a wholesale header from archive. Current storefront on `main` is ahead of that snapshot.

Implementation spec: `docs/superpowers/specs/2026-09-03-ws002-header-v1-implementation.md`  
Branch after both specs are committed on `main`: `feat/ws002-header-v1`

---

## 1. Layout inventory (from `main`, not archive)

Source: `modules/Cart/resources/views/layouts/storefront.blade.php`  
Chrome CSS: `resources/css/storefront/shell.css` (border / surface / brand / nav-link colors only)  
Live check: Homepage and Shop at 375 / 768 / 1024 (local storefront).

The header is **inline in the Cart layout**. There is no `x-storefront.layout.partials.site-header`. Isolation tests still forbid extracting it.

### Structure today

```text
header.storefront-header
  div.mx-auto.flex.max-w-5xl.items-center.justify-between.px-6.py-4
    left   brand text link
    right  nav.flex.items-center.gap-4.text-sm
             Shop          hardcoded
             Blog          if route storefront.cms.posts.index
             Cart          hardcoded, no count
             currency      <select> if $storeCurrencies (Currency view composer)
             Account + Sign out   OR   Sign in     (@auth('customer'))
```

Inner width is **`max-w-5xl` (64rem / 1024px) + `px-6` (1.5rem)**.  
Homepage inners, Shop listing, and Footer already use **`--store-max-width: 80rem` (1280px) + `--store-gutter: 1.5rem`**.  
Header is **narrower than page content**. That gap is a primary reason v1.12 exists.

### Desktop (≥1024)

```text
logo / brand     left, text only
navigation       right, same row (Shop, Blog)
search           not in the header (Shop page owns the GET search field)
account          Sign in, or Account + Sign out
cart             text link, no badge
```

One flex row, `justify-between`, no wrap rule.

### Tablet (~768)

```text
wrap behavior    none — still one row
spacing          same `px-6` / `gap-4` as desktop
currency         visible as a compact native <select> (e.g. USD)
```

No tablet-specific CSS. At 768 the row fits; it is not a designed breakpoint.

### Mobile (~375)

```text
collapse         none — no hamburger, no drawer, no <details>
behavior         same flex row; items stay in one line and squeeze
overflow         document overflowX was 0 in review; cramped, not wrapping
currency         native select collapses to a small control
```

There is no mobile header. v1.12 must add a **simple collapse** for primary nav. That is not a mega-drawer product.

### Brand today

```text
text    config('commerce.name', 'Commerce Framework')
logo    none
```

Footer and Homepage already resolve `store.name` + `store.logo_media_uuid` via query services + `MediaQueryServiceInterface`. Header still ignores Website Settings.

### Navigation today

Hardcoded Shop / Blog / Cart. Does **not** call `NavigationQueryServiceInterface::links('main')`.  
`NavigationLinkData` already exists. The `main` menu handle is seeded empty.

### Search today

Not in the header. Shop listing already has GET `?search=` on `storefront.shop.index`.  
v1.12 search is an **entry** to that existing Shop search, not a new engine.

### Cart today

Text link to `storefront.cart.index`. `CartData::itemCount` exists; the header does not show it.

### Account today

Blade calls `@auth('customer')` and customer account routes directly.

### Currency today

`CurrencyServiceProvider` composes `cart::layouts.storefront` with `$storeCurrencies`, `$storeBaseCurrency`, `$storeDisplayCurrency`.  
Currency is **existing chrome**, not in the Header-owner list below. v1.12 preserves it as an optional action mapped through the header DTO. It is not a theme switcher.

---

## 2. Information architecture

### Header owns

```text
Brand                 store.name + logo (fail-soft)
Primary navigation    NavigationQueryService::links('main')
Search entry          trigger to Shop GET search
Account entry         sign in / account / sign out
Cart entry            cart URL + count
```

### Header does not own

```text
Product categories    Catalog / Homepage tabs / Shop filters
Footer links          Footer composition
Website Settings UI   admin
Shop listing chrome   toolbar, pagination, listing search field
Blog chrome
```

Cart is an **action**, not a primary nav item. Shop / Blog belong in `main` (or the empty-menu fail-soft), not duplicated as hardcoded extras once `links('main')` has items.

---

## 3. Contract before UI

Blade consumes DTOs, same pattern as Footer (`FooterPageData`) and Product Card (`ProductCardData`).

```php
HeaderBrandData
HeaderNavigationData
HeaderActionData
HeaderViewData
```

Live in `Commerce\Contracts\Storefront`, next to `ProductCardData`.

| DTO | Fields |
| --- | --- |
| `HeaderBrandData` | `name`, `?logoUrl`, `homeUrl` |
| `HeaderNavigationData` | `links`: `list<NavigationLinkData>` — reuse the Navigation contract, do not invent a second link type |
| `HeaderActionData` | search URL, cart URL, cart count, authenticated flag, account / login / logout URLs, optional currency codes + current + action URL |
| `HeaderViewData` | `brand`, `navigation`, `actions` |

Forbidden in the header Blade:

```php
Setting::...
Navigation::...
User::...
@auth
config('commerce.name')
$storeCurrencies as Eloquent
```

A composer (Footer pattern) or Cart-owned builder maps query services → `HeaderViewData`. Blade reads that DTO only.

---

## 4. Scope (v1.12)

### In

```text
Desktop header          one row, 80rem / 24px gutter
Mobile header           simple nav collapse (<details> or equivalent, no JS animation system)
Navigation              links('main'), fail-soft Shop (+ Blog if route exists) when main is empty
Store logo / name       Website Settings, same fail-soft as Homepage/Footer branding
Account button          from HeaderActionData
Cart button             from HeaderActionData, show count when > 0
Search trigger          GET to storefront.shop.index (?search=)
Currency switcher       preserve current behavior via HeaderActionData (not a new product)
```

### Out (later releases)

```text
Mega menu
Sticky smart header
Animation system
Theme switcher
Notification center
Wishlist
PDP restyle
Blog redesign
Appearance / theme engine
Product card changes
Shop listing changes
```

---

## 5. Sequence

```text
Header feature spec          ← this file
↓
Header implementation spec
↓
commit docs on main
↓
feat/ws002-header-v1
↓
Header isolation tests (red)
↓
DTO contracts
↓
Header render
↓
responsive behavior
↓
PR
↓
v1.12.0
```

Do not implement on `main`. Isolation tests before production classes.
