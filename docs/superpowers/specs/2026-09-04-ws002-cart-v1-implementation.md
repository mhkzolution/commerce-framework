# WS-002 Cart Refresh v1 — Implementation Spec (v1.15.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Tag:** v1.15.0  
**Branch:** `feat/ws002-cart-v1`

Feature: `docs/superpowers/specs/2026-09-04-ws002-cart-refresh-feature.md`

Inventory is in that feature spec (section 1). Design from current `main`, not archive.

```text
git merge feat/commerce-framework-v1
git merge feat/homepage-cms-preservation
git merge feat/barcode-stock-recovery
git checkout 84e905c -- .
```

Do not extract archive cart markup. Rewrite onto current tokens if a single archive file is still the best *reference*.

---

## 1. Layout

Mirror Shop / Blog / PDP:

```blade
@section('main_class', 'storefront-cart-main')
```

```blade
<x-storefront.layout.page-container class="storefront-cart">
```

Do not change `layouts/storefront.blade.php` default `<main>` (`max-w-5xl` stays for checkout / account / login). Do not render `site-header` from the cart view.

Register `resources/css/storefront/cart.css` in Vite and `@vite` it from the cart view the same way Shop / PDP load page CSS (one extra CSS entry is fine; do not add a second JS `@vite` client).

Line list may stay a `<table>` (accessible columns). Style it with `cart.css` + tokens, not a Tailwind utility soup / `cf-*`. At 375px the **page** must not `overflowX`; the table may scroll inside its own wrapper.

---

## 2. Keep the existing DTO

`StorefrontCartController::index` already passes `CartData`. Do **not** add `CartViewData` / a mapper unless a field is missing for the DoD.

Blade may read:

```text
$cart->lines            ResolvedCartLineData[]
  purchasableUuid, quantity, name, sku, unitPrice, lineTotal, available, isPurchasable
$cart->currency
$cart->subtotal
$cart->itemCount
$cart->discountTotal
$cart->couponCode
$cart->promotionName
```

Money display may stay `number_format($cents / 100, 2)` in the Blade (values are already in cart currency). Do not inject `CurrencyConverterInterface`. Do not load Eloquent Product in the view or controller `index`.

Do not add line images. `ResolvedCartLineData` has no `imageUrl`; that is a later tag if needed.

---

## 3. Empty state

When `$cart->lines === []`, use `x-storefront.empty-state` (title + Continue shopping in the slot linking to `storefront.shop.index`). Do not keep the bare `<p>`.

`CheckoutFlowTest` currently `assertSee('Your cart is empty')` after a completed order. Update it to the lang output when copy moves to `storefront::`.

---

## 4. Forms (behavior unchanged)

Keep these routes and methods:

```text
DELETE  storefront.cart.clear
PATCH   storefront.cart.items.update   {purchasableUuid}  quantity
DELETE  storefront.cart.items.destroy  {purchasableUuid}
GET     storefront.checkout            (CTA only)
POST    storefront.cart.coupon.apply   code
DELETE  storefront.cart.coupon.remove
```

Qty input: storefront classes, `name="quantity"`, min 0 (current contract). Buttons: storefront classes, not `cf-btn`.

Flash: session `status` and `$errors` use storefront flash classes in `cart.css`, not `cf-flash`.

Checkout CTA is still a link to `storefront.checkout`. Do not restyle `checkout.blade.php`.

---

## 5. Copy

Add keys under `modules/Cart/resources/lang/{en,th}/storefront.php` (and use `storefront::storefront.*`). Title, clear, empty, continue shopping, table headers, update/remove, subtotal, checkout, promotion, apply, coupon applied, only-N-available.

Do not hardcode English in the Blade.

---

## 6. Isolation / contract tests

Add:

```text
tests/Unit/Storefront/Ws002CartIsolationTest.php
tests/Feature/Storefront/Ws002CartContractTest.php
```

Isolation must fail (red) until production changes land:

```text
cart.blade.php uses x-storefront.layout.page-container
cart.blade.php uses storefront-cart-main
cart.blade.php uses x-storefront.empty-state
cart.blade.php does not contain cf-btn, cf-input, cf-flash, x-admin.
cart.blade.php does not contain site-header / Setting:: / @auth
cart.blade.php copy goes through storefront:: (no hardcoded "Your cart is empty" / "Clear cart" / "Checkout" as visible English source)
cart.css exists, uses tokens, does not set 77.5rem / 87.5rem
Shop / Blog / PDP / Header / checkout.blade.php do not embed storefront-cart-main
```

Contract: empty cart renders empty-state; with a line, qty PATCH form + checkout link present; no `cf-btn` in HTML; page-container class in HTML. Runtime: no `overflowX` at 375 / 768 / 1024.

Existing WS-002 isolation tests that keep layout default `max-w-5xl` stay — v1.15 overrides via `main_class` like PDP. Product card / Header / PDP isolation stay.

---

## 7. Out of this branch

```text
HeaderViewData / site-header markup
Shop listing / blog / PDP views
Product card primitive
checkout.blade.php / confirmation / payment
account / login / register
CartService / coupon engine
line images / drawer mini-cart
page.blade.php
Appearance
```

---

## 8. Sequence

```text
isolation tests red
↓
main_class + page-container
↓
cart.css + vite entry
↓
empty-state + storefront chrome (no cf-*)
↓
storefront:: copy
↓
update CheckoutFlowTest empty-cart assertion
↓
contract tests
↓
PR squash → tag v1.15.0
```
