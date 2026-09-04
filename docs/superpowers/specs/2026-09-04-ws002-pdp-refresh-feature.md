# WS-002 PDP Refresh — Feature (v1.14.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Owner:** `modules/Cart/resources/views/storefront/product.blade.php` + `resources/css/storefront` + Cart PDP builder  
**Mode:** Feature development on the current release line (not recovery)

```text
v1.12.0 Header Foundation       ← done
v1.13.0 Blog Refresh            ← done
v1.14.0 PDP Refresh             ← this file
later   Appearance/Theming
```

Dependencies PDP needs are already on `main`:

```text
✓ Design tokens (--store-max-width 80rem, --store-gutter)
✓ page-container
✓ site-header / site-footer
✓ ProductCardData + ProductCardMapper (image, default variant, inventory)
✓ Shop listing + Blog on page-container
✓ POST storefront.cart.items.store
```

v1.14 is not "recover an archive PDP". It is the current product detail joining this release line's layout and DTO rules.

```text
git merge feat/commerce-framework-v1
git merge feat/homepage-cms-preservation
git merge feat/barcode-stock-recovery
git checkout 84e905c -- .
```

Do not path-extract wholesale PDP markup from archive. Current storefront on `main` is ahead of that snapshot (Header, Shop, Blog, product card).

Implementation spec: `docs/superpowers/specs/2026-09-04-ws002-pdp-v1-implementation.md`  
Branch after both specs are committed on `main`: `feat/ws002-pdp-v1`

---

## 1. Layout inventory (from `main`, not archive)

Sources:

```text
modules/Cart/resources/views/storefront/product.blade.php
modules/Cart/src/Http/Controllers/ShopController.php  (show)
modules/Cart/resources/views/layouts/storefront.blade.php
```

Header and Footer are already the shared primitives. The PDP view must not render `site-header` / `site-footer`.

### Width today

PDP does **not** set `@section('main_class')`. It inherits the layout default:

```text
main.mx-auto.w-full.max-w-5xl.px-6.py-8
```

Homepage / Shop / Header inner / Blog already use `--store-max-width: 80rem` + `--store-gutter`. PDP is still **64rem / `max-w-5xl`**.

### Structure today

```text
nav.mb-6.text-sm          Shop / {name}     (raw links, not x-storefront.breadcrumb)
div.grid.gap-8.lg:grid-cols-2
  left   media card
           if media->isNotEmpty(): empty aspect-square  (no <img>)
           else: "No image"
  right  h1 $product->name
         description: nl2br(e($product->description))
         if $variant:
           price: Blade calls $currencyConverter->convert(...)
           stock + SKU
           if available > 0:
             POST cart form  (cf-input + cf-btn cf-btn--primary, "Add to cart" hardcoded)
         else: "This product is not available."
```

Controller (`ShopController::show`) loads Eloquent `Product`, `$product->defaultVariant()`, inventory `getAvailable`, and passes `currencyConverter` into the view.

### What is missing vs the rest of WS-002

```text
page-container          no
full-bleed main         no (max-w-5xl)
DTO / mapper            no (Blade reads Eloquent + converter)
primary image           media rows exist but the template never outputs src
storefront tokens       Tailwind utilities + cf-* admin chrome
i18n                    hardcoded English (Add to cart, in stock, Out of stock, No image)
variant picker          none — default variant only
gallery / zoom          none
related products        none
```

---

## 2. What this is

```text
Homepage  page-container
Shop      page-container     (v1.11)
Header    page-container     (v1.12)
Blog      page-container     (v1.13)
PDP       page-container     ← this tag
```

Same move Shop and Blog already made: full-bleed `<main>`, content in `x-storefront.layout.page-container`. Buy box stays two columns on large screens. Product card, Shop listing, Header, and Blog stay unchanged.

---

## 3. Definition of Done (v1.14)

```text
Layout        @section('main_class') full-bleed (not max-w-5xl)
              Content wrapped in x-storefront.layout.page-container
DTO           Blade consumes ProductDetailData only (no Eloquent, no defaultVariant, no CurrencyConverter)
Image         primary imageUrl renders as <img> when present; placeholder when not
Cart          POST storefront.cart.items.store with purchasable_uuid + quantity (default variant)
Copy          storefront:: lang keys (not hardcoded English)
Chrome        no cf-btn / cf-input / x-admin.*
Header        unchanged (layout site-header)
Card          x-storefront.cards.product unchanged
Variant       still the default variant (no option matrix)
```

---

## 4. Owner

```text
WS-002     page-container (already), pdp.css, product detail primitive
Cart       ShopController::show, ProductDetail builder/mapper, product.blade.php
Catalog    product + media records (existing)
Inventory  availability via query service in the builder, not the Blade
Media      URL via MediaQueryServiceInterface (same as ProductCardMapper)
```

---

## 5. Out of scope

```text
Header / Footer / Shop listing / Product card / Blog
Variant option matrix / swatches
Image gallery, zoom, video
Related products / recently viewed
Reviews / ratings / wishlist
Cart, checkout, account page restyle (max-w-5xl stays)
CMS static page.blade.php
Appearance / theme engine
Archive merge / git checkout 84e905c -- .
```

---

## 6. Sequence

```text
PDP feature spec             ← this file
↓
PDP implementation spec
↓
commit docs on main
↓
feat/ws002-pdp-v1
↓
PDP isolation tests (red)
↓
DTO + builder
↓
layout + page-container + image
↓
token CSS / storefront form chrome
↓
PR
↓
v1.14.0
```

Do not implement on `main`. Isolation tests before production classes.
