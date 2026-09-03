# WS-002 Design System — Feature (v1.9.x)

**Date:** 2026-09-03  
**Status:** Locked as next milestone on `main`  
**Owner:** `resources/css/storefront` + `resources/views/components/storefront`  
**Mode:** Feature development (not recovery)

```text
main = active development line
v1.8.0 = Website Settings
v1.9.x = WS-002 Design System
```

Do not merge:

```text
feat/homepage-cms-preservation
feat/barcode-stock-recovery
feat/commerce-framework-v1
```

Archive `feat/commerce-framework-v1` is forensic. `DESIGN.md` describes a larger library than `main` has. WS-002 builds the storefront language **on current main**, path-extracting only when a single file is still the best source.

---

## What WS-002 is

WS-002 is the **storefront design language**: shared tokens and Blade primitives that Homepage, Footer, header chrome, Shop, and Blog compose.

It is not:

```text
a new business module
an admin theme rewrite
Website Settings (brand data)
Navigation (named menus)
Footer composition (footer.config + DTOs)
a wholesale merge of feat/commerce-framework-v1
```

---

## Problem

Data owners are now stable:

```text
Homepage CMS        v1.5.0
Footer composition  v1.6.0
Navigation menus    v1.7.0
Website Settings    v1.8.0
```

The storefront still does not share one visual contract:

```text
Cart layout header     inline Tailwind + shell.css (max-w-5xl)
Homepage               home.css (max-width 77.5rem)
Footer                 footer.css (var(--store-max-width) — token file missing)
Blog                   blog.css + thin adapters from v1.3.0
Shop                   ad-hoc Tailwind cards in shop.blade.php
```

`DESIGN.md` points at `resources/css/storefront/tokens.css` and `--store-max-width` / `--store-gutter` / `--radius-store`. Those variables are **used** on `main` and **not defined**. That is the first WS-002 gap.

Blog v1.3.0 adapters were explicit placeholders: replace them when the real design system lands. That is this milestone, done in phases, not a big-bang replace.

---

## Owner (locked)

```text
resources/css/storefront/
resources/views/components/storefront/
```

Not `packages/storefront-design-system`. Packages in this repo are kernel infrastructure (`FRAMEWORK.md`). Storefront chrome already lives in the trees above (Footer M2, Blog adapters, `shell.css`).

Not Homepage / Footer / Navigation / Settings modules. Those own **data and page sections**. They consume `x-storefront.*` and storefront CSS.

| Concern | Owner |
|---|---|
| Tokens, primitives, shared chrome CSS | WS-002 (`resources/css/storefront`, `resources/views/components/storefront`) |
| Homepage sections / CMS content | CMS + Cart storefront views |
| Footer composition + Footer DTOs | Settings Footer |
| Named menus | Navigation |
| Brand name / logo / social URLs | Website Settings |
| Admin UI tokens | existing `resources/css/tokens/*` (do not rewrite as the storefront source) |

Admin already has semantic tokens (`semantic-light.css`). Storefront may **reuse** color/text/border variables. It must **own** store-scoped layout tokens (`--store-*`, `--radius-store*`) so admin sidebar widths never leak into the shop.

---

## Out of scope (all of v1.9.x unless a later spec says otherwise)

```text
git merge feat/commerce-framework-v1
Whole archive components.css
Appearance / Customer Experience admin
Mega menu / mobile menu builder
Checkout / auth footer revival
Barcode / POS / Scanner UI
Rewriting FooterPageData or preview JSON
Moving menus out of Navigation
Moving brand keys out of Settings
```

---

## Implementation spec

`docs/superpowers/specs/2026-09-03-ws002-design-system-v1-implementation.md`

Do not branch until that file is the contract.
