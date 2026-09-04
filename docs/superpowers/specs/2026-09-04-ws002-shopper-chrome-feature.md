# WS-002 Shopper Chrome — Feature (v1.15.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Owner:** Cart + Customers storefront views + `resources/css/storefront/shopper.css`  
**Mode:** Feature development on the current release line (not recovery)

Supersedes the Cart-only lock in `2026-09-04-ws002-cart-refresh-feature.md`. v1.15.0 is the remaining shopper path in one tag.

```text
v1.14.0 PDP Refresh             ← done
v1.15.0 Shopper Chrome          ← this file (Cart → pay → Account)
later   Appearance/Theming, Scanner, POS, Inventory, Marketplace
```

```text
git merge feat/commerce-framework-v1
git checkout 84e905c -- .
```

Do not path-extract archive cart/checkout/account. Design from current `main` (Shop / PDP chrome).

Implementation spec: `docs/superpowers/specs/2026-09-04-ws002-shopper-chrome-v1-implementation.md`  
Branch: `feat/ws002-shopper-chrome-v1`

---

## 1. Surfaces (this tag)

```text
modules/Cart/resources/views/storefront/cart.blade.php
modules/Cart/resources/views/storefront/checkout.blade.php
modules/Cart/resources/views/storefront/_checkout_address_fields.blade.php
modules/Cart/resources/views/storefront/confirmation.blade.php
modules/Payment/resources/views/storefront/pay.blade.php
modules/Customers/resources/views/storefront/account.blade.php
modules/Customers/resources/views/storefront/order.blade.php
modules/Customers/resources/views/storefront/login.blade.php
modules/Customers/resources/views/storefront/register.blade.php
```

## 2. Definition of Done

```text
Layout     each view: storefront-shopper-main + x-storefront.layout.page-container
Chrome     shared shopper.css (btn / input / flash / panel / table wrap)
           no cf-btn / cf-input / cf-flash / cf-badge / x-admin.*
Copy       storefront:: lang keys (en + th)
Empty      cart + empty checkout: x-storefront.empty-state
Account    no customers::admin._address_form
Checkout   two columns via CSS at 64rem, not lg:grid-cols-2
Behavior   routes, Stripe inline script, billing-same-as-shipping JS unchanged
Header     layout site-header unchanged
```

## 3. Out of scope

```text
mega-menu / header.js / Appearance / wishlist
archive merge
new DTOs (keep CartData, existing checkout/account models)
layout default max-w-5xl (pages opt in via main_class)
CMS page.blade.php
```
