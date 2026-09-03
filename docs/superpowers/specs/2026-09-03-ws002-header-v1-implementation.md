# WS-002 Header Foundation v1 — Implementation Spec (v1.12.0)

**Date:** 2026-09-03  
**Status:** Locked  
**Tag:** v1.12.0  
**Branch:** `feat/ws002-header-v1`

Feature: `docs/superpowers/specs/2026-09-03-ws002-header-foundation-feature.md`

Inventory is in that feature spec (section 1). Design from current `main`, not archive.

```text
git merge feat/commerce-framework-v1
git merge feat/homepage-cms-preservation
git merge feat/barcode-stock-recovery
git checkout 84e905c -- .
```

Do not extract archive header markup. Rewrite onto current tokens if a single archive file is still the best *reference*.

---

## 1. Primitive

```text
resources/views/components/storefront/layout/partials/site-header.blade.php
```

```blade
<x-storefront.layout.partials.site-header />
```

Cart layout replaces the inline `<header class="storefront-header">` block with that include. Shop / Homepage / Blog views do **not** render the header themselves.

Inner width uses `x-storefront.layout.page-container` (same 80rem / 24px as Shop and Homepage inners). Do not keep `max-w-5xl` on the header inner.

```blade
<header class="storefront-site-header">
    <x-storefront.layout.page-container class="storefront-site-header__inner">
        {{-- brand | nav | actions from HeaderViewData --}}
    </x-storefront.layout.page-container>
</header>
```

`<main>` default `max-w-5xl` stays. v1.12 does not restyle PDP, cart, checkout, account, or Blog page bodies.

---

## 2. DTOs

```text
packages/commerce/contracts/src/Storefront/HeaderBrandData.php
packages/commerce/contracts/src/Storefront/HeaderNavigationData.php
packages/commerce/contracts/src/Storefront/HeaderActionData.php
packages/commerce/contracts/src/Storefront/HeaderViewData.php
```

Plain `final readonly` classes, same as `ProductCardData`. Do not put Eloquent on them.

```text
HeaderBrandData
  name          string
  logoUrl       ?string
  homeUrl       string

HeaderNavigationData
  links         list<NavigationLinkData>   // reuse Commerce\Contracts\Navigation\NavigationLinkData

HeaderActionData
  searchUrl           string               // storefront.shop.index
  cartUrl             string
  cartCount           int
  authenticated       bool
  accountUrl          string
  loginUrl            string
  logoutUrl           string
  currencyCodes       list<string>         // empty = hide switcher
  currentCurrency     ?string
  currencyActionUrl   ?string              // POST storefront.cart.currency

HeaderViewData
  brand         HeaderBrandData
  navigation    HeaderNavigationData
  actions       HeaderActionData
```

Blade `@props(['header' => null])` and require `HeaderViewData`. If missing, render nothing (fail-soft, same idea as Footer).

---

## 3. Builder + composer

Cart owns the storefront layout, so Cart owns the mapper:

```text
modules/Cart/src/Services/HeaderViewModelBuilder.php
```

Maps:

```text
HomepageBrandingQuery          → HeaderBrandData (name, logoUrl)
route storefront.home|shop     → homeUrl
NavigationQueryService::links('main')
  if empty                     → fail-soft Shop + Blog (if route exists)
                               → do not fail-soft Cart (Cart is an action)
CartServiceInterface::get()    → cartCount (0 on failure)
Auth::guard('customer')        → authenticated + URLs
CurrencyConverterInterface     → currencyCodes / current / action URL
```

Do not call Settings / Navigation / User models from the Blade. Do not add a Settings-owned Header builder (Footer stays Settings; Header stays Cart + WS-002 primitive).

Composer (Footer pattern) on the header view:

```php
View::composer('components.storefront.layout.partials.site-header', ...)
```

Register from `CartServiceProvider`. Skip if the view already received `HeaderViewData`.

Currency composer on `cart::layouts.storefront` may remain for other consumers. Header Blade must not read `$storeCurrencies`.

---

## 4. Blade rules

Header Blade may render:

```text
brand name / logo img
nav links from HeaderNavigationData
GET search form → actions.searchUrl, input name="search"
cart link + count when cartCount > 0
account / sign in / sign out from HeaderActionData
currency POST form from HeaderActionData (codes only)
mobile <details> (or equivalent) wrapping primary nav
```

Header Blade must not contain:

```text
Setting::
Navigation::
User::
@auth
config('commerce.name')
defaultVariant
Appearance
```

`<title>` in the layout may keep `config('commerce.name')`. That is not the header primitive.

Search trigger is a GET form (or equivalent) to Shop. Do not add typeahead, faceted search, or a new index.

---

## 5. CSS

```text
resources/css/storefront/shell.css     (keep chrome colors; add header layout)
resources/css/storefront/header.css    (optional if shell.css would mix concerns)
```

If `header.css` is added, import it from `app.css` after tokens (same order as footer/shop) and add it to `vite.config.js` if it is a page-level Vite input. Prefer importing from `app.css` like `shell.css` so every storefront page gets it.

Tokens only:

```text
max-width     var(--store-max-width) via page-container
gutter        var(--store-gutter) via page-container
spacing       --space-*
type          --font-store
colors        --color-text / --color-muted / --color-border / --color-surface / --color-primary
radius        --radius-* for controls if needed
```

No `77.5rem`. No `max-w-5xl` on the header inner. No archive class dump (`.cf-header`, Prompt font, sticky smart header).

### Responsive

```text
Desktop / tablet (≥768)
  brand | primary nav | actions on one row
  details/summary toggle hidden

Mobile (<768)
  brand | actions (search trigger, cart, account) visible
  primary nav inside <details> (simple collapse)
  no drawer, no overlay, no JS animation system
```

768px / `48rem` is the only new breakpoint. Do not add a sticky header.

Existing `.storefront-header` class may be renamed to `.storefront-site-header` so it matches `.storefront-site-footer`. Update isolation tests that look for the old markup.

---

## 6. Isolation tests (red first)

New:

```text
tests/Unit/Storefront/Ws002HeaderIsolationTest.php
tests/Feature/Storefront/Ws002HeaderContractTest.php
```

Red assertions for the unit file:

```text
site-header.blade.php exists
layout includes x-storefront.layout.partials.site-header
layout header inner is not max-w-5xl
header Blade contains HeaderViewData
header Blade does not contain Setting::, Navigation::, User::, @auth
header Blade does not contain config('commerce.name')
DTOs exist under contracts/Storefront
builder exists
no packages/storefront-design-system
no Appearance / archive types in header files
CSS uses --store-max-width or page-container, not 77.5rem
```

Feature contract: Homepage and Shop GET 200, see the header landmark, see brand, do not see inline `max-w-5xl` header wrapper.

### Historical tests to update (same PR)

These currently **forbid** `site-header`. v1.12 owns that surface; update them so they keep their other locks:

```text
tests/Unit/Storefront/Ws002IsolationTest.php
  test_m1_does_not_extract_site_header_or_redesign_navigation
  → layout uses the primitive; M1 token/page-container files still do not own header CSS

tests/Unit/Storefront/Ws002FooterTokenAdoptionTest.php
  test_phase_2_does_not_extract_header
  → footer CSS still does not own header; layout uses site-header

tests/Unit/Storefront/Ws002ProductCardIsolationTest.php
  test_no_header_extract_this_milestone
  → drop; product card still must not grow header markup

tests/Unit/Storefront/Ws002ShopListingIsolationTest.php
  test_header_is_not_extracted
  FORBIDDEN site-header on shop.blade.php stays (Shop view is not the header)
  → layout uses the primitive

tests/Feature/Storefront/Ws002HomepageContractTest.php
  → stop asserting site-header file is absent
```

Shop listing still must not embed the header component in `shop.blade.php`.

---

## 7. Sequence

```text
docs committed on main
feat/ws002-header-v1 from main
isolation tests red
DTOs
HeaderViewModelBuilder + composer
site-header Blade + CSS
layout include + drop inline header
update historical isolation tests
feature contract
full suite
PR
tag v1.12.0 on the squash SHA
```

Do not implement on `main`. Do not start Blog, PDP, Appearance, Scanner.
