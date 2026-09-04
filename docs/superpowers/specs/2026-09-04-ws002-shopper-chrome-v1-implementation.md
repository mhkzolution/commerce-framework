# WS-002 Shopper Chrome v1 — Implementation Spec (v1.15.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Tag:** v1.15.0  
**Branch:** `feat/ws002-shopper-chrome-v1`

Feature: `docs/superpowers/specs/2026-09-04-ws002-shopper-chrome-feature.md`

---

## 1. Shared CSS

Create `resources/css/storefront/shopper.css`. Register in `vite.config.js`. Each listed view `@vite`s that one file (no second JS `@vite`).

```blade
@section('main_class', 'storefront-shopper-main')
```

```blade
<x-storefront.layout.page-container class="storefront-shopper storefront-{page}">
```

Login / register / confirmation / pay may use `variant="narrow"` on page-container.

Do not change layout default `<main>`. Classes: `.storefront-btn`, `.storefront-input`, `.storefront-flash`, `.storefront-panel`, `.storefront-field`, `.storefront-table-wrap`. Checkout columns: `.storefront-checkout__layout` at `64rem`, not `lg:grid-cols-2`.

## 2. Keep behavior

Cart: `CartData` only; PATCH/DELETE/coupon/clear/checkout CTA.  
Checkout: same POST fields, address include API (`prefix` / `legend`), inline billing JS, shipping totals JS.  
Pay: Stripe `js.stripe.com` + simulated pay/fail forms.  
Account: profile PUT, address POST/DELETE, orders table. Replace admin address include with storefront partial using the **same input names**.  
Order show: same data, storefront chrome.

## 3. Isolation / contract

```text
tests/Unit/Storefront/Ws002ShopperChromeIsolationTest.php
tests/Feature/Storefront/Ws002ShopperChromeContractTest.php
```

Isolation red until production lands: every listed Blade has page-container + shopper-main + shopper.css vite; none contain `cf-btn` / `cf-input` / `cf-flash` / `cf-badge` / `customers::admin._address_form` / `lg:grid-cols-2` / `x-admin.` / `site-header`. CSS uses tokens, not 77.5rem / 87.5rem. Shop / Blog / PDP / Header do not embed `storefront-shopper-main`.

Contract: empty cart empty-state; cart with a line has qty + checkout link; login/account/checkout HTML has no `cf-btn`. Update `CheckoutFlowTest` empty-cart assertion to lang output.

## 4. Sequence

```text
isolation tests red
↓
shopper.css + vite
↓
lang keys
↓
rewrite listed blades
↓
contract + CheckoutFlowTest
↓
full suite + runtime (375/768/1024 no overflowX)
↓
PR squash → tag v1.15.0
```
