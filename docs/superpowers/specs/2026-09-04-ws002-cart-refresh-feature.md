# WS-002 Cart Refresh — Feature (v1.15.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Owner:** `modules/Cart/resources/views/storefront/cart.blade.php` + `resources/css/storefront` + Cart storefront controller  
**Mode:** Feature development on the current release line (not recovery)

```text
v1.12.0 Header Foundation       ← done
v1.13.0 Blog Refresh            ← done
v1.14.0 PDP Refresh             ← done
v1.15.0 Cart Refresh            ← this file
later   Checkout, Account, Appearance/Theming
```

Dependencies Cart needs are already on `main`:

```text
✓ Design tokens (--store-max-width 80rem, --store-gutter)
✓ page-container
✓ site-header / site-footer
✓ ProductCardData + Shop listing + Blog + PDP on page-container
✓ CartData + ResolvedCartLineData (controller already passes DTO, not Eloquent)
✓ x-storefront.empty-state
✓ POST/PATCH/DELETE cart items, clear, coupon apply/remove
✓ GET storefront.checkout
```

v1.15 is not "recover an archive cart" and not a checkout rewrite. It is the current cart joining this release line's layout and chrome.

```text
git merge feat/commerce-framework-v1
git merge feat/homepage-cms-preservation
git merge feat/barcode-stock-recovery
git checkout 84e905c -- .
```

Do not path-extract wholesale cart markup from archive. Current storefront on `main` is ahead of that snapshot (Header, Shop, Blog, PDP).

Implementation spec: `docs/superpowers/specs/2026-09-04-ws002-cart-v1-implementation.md`  
Branch after both specs are committed on `main`: `feat/ws002-cart-v1`

---

## 1. Layout inventory (from `main`, not archive)

Sources:

```text
modules/Cart/resources/views/storefront/cart.blade.php
modules/Cart/src/Http/Controllers/StorefrontCartController.php  (index)
modules/Cart/src/DTO/CartData.php
modules/Cart/src/DTO/ResolvedCartLineData.php
modules/Cart/resources/views/layouts/storefront.blade.php
```

Header and Footer are already the shared primitives. The cart view must not render `site-header` / `site-footer`.

### Width today

Cart does **not** set `@section('main_class')`. It inherits the layout default:

```text
main.mx-auto.w-full.max-w-5xl.px-6.py-8
```

Homepage / Shop / Header inner / Blog / PDP already use `--store-max-width: 80rem` + `--store-gutter`. Cart is still **64rem / `max-w-5xl`**.

### Structure today

```text
h1 "Cart"  (hardcoded; @section('title', 'Cart'))
optional DELETE storefront.cart.clear   "Clear cart"
cf-flash success (session status)
cf-flash danger  ($errors->first())

empty ($cart->lines === []):
  <p> Your cart is empty.
      <a storefront.shop.index> Continue shopping

lines:
  Tailwind table (Product / Price / Qty / Total / Actions)
    name, sku|uuid, "Only N available" when available < quantity
    unitPrice / lineTotal via number_format(/100)
    PATCH storefront.cart.items.update   cf-input qty + "Update"
    DELETE storefront.cart.items.destroy "Remove"
  summary band: Subtotal (N items), currency, discount line, cf-btn Checkout
  Promotion code:
    if coupon: "{code} applied" + DELETE coupon.remove
    else: POST coupon.apply  cf-input + cf-btn Apply
```

Controller (`StorefrontCartController::index`) already passes only `'cart' => $this->cartService->get()` (`CartData` with `ResolvedCartLineData[]`). No Eloquent in the Blade. Amounts are already in the cart currency — Blade `number_format` is display only, not a converter.

### What is missing vs the rest of WS-002

```text
page-container          no
full-bleed main         no (max-w-5xl)
storefront tokens       Tailwind utilities + cf-btn / cf-input / cf-flash
i18n                    hardcoded English (Cart, Clear cart, empty copy, table headers, Checkout, Promotion code)
empty primitive         <p>, not x-storefront.empty-state
line images             none (ResolvedCartLineData has no imageUrl — keep out)
```

---

## 2. What this is

```text
Homepage  page-container
Shop      page-container     (v1.11)
Header    page-container     (v1.12)
Blog      page-container     (v1.13)
PDP       page-container     (v1.14)
Cart      page-container     ← this tag
```

Same move Shop / Blog / PDP already made: full-bleed `<main>`, content in `x-storefront.layout.page-container`. Keep cart/coupon/checkout **behavior**. Do not restyle checkout, confirmation, or account. Do not change Header, Shop listing, Blog, PDP, or the product card.

---

## 3. Definition of Done (v1.15)

```text
Layout        @section('main_class') full-bleed (not max-w-5xl)
              Content wrapped in x-storefront.layout.page-container
DTO           Keep CartData / ResolvedCartLineData (no Eloquent, no CurrencyConverter in the view)
Empty         x-storefront.empty-state + Continue shopping action (not a bare <p>)
Cart          POST/PATCH/DELETE items, clear, coupon apply/remove unchanged
Checkout CTA  still GET storefront.checkout (chrome only; checkout page unchanged)
Copy          storefront:: lang keys (not hardcoded English)
Chrome        no cf-btn / cf-input / cf-flash / x-admin.*
Header        unchanged (layout site-header)
```

---

## 4. Owner

```text
WS-002     page-container (already), cart.css
Cart       StorefrontCartController::index (already DTO), cart.blade.php, lang keys
```

---

## 5. Out of scope

```text
Header / Footer / Shop listing / Product card / Blog / PDP
Checkout page, confirmation, payment
Account / login / register
Cart line images / mini-cart drawer / qty stepper JS
New CartViewData (CartData is enough)
Coupon engine / promotion rules
Appearance / theme engine
Archive merge / git checkout 84e905c -- .
```

---

## 6. Sequence

```text
Cart feature spec             ← this file
↓
Cart implementation spec
↓
commit docs on main
↓
feat/ws002-cart-v1
↓
Cart isolation tests (red)
↓
layout + page-container
↓
token CSS / storefront chrome / empty-state / i18n
↓
PR
↓
v1.15.0
```

Do not implement on `main`. Isolation tests before production classes.
