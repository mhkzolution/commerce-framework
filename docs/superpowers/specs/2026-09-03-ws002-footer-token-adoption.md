# WS-002 Phase 2 — Footer Token Adoption (v1.9.1)

**Date:** 2026-09-03  
**Status:** Locked  
**Milestone:** v1.9.1 on `main`  
**Owner:** `resources/css/storefront/footer.css` consumes tokens owned by WS-002  
**Does not own:** Footer composition, Footer DTOs, `site-footer` Blade

```text
v1.9.0  tokens.css + page-container + Homepage
v1.9.1  Footer CSS uses the same spacing / radius / container / type tokens
```

```text
git merge feat/commerce-framework-v1
git merge feat/barcode-stock-recovery
git checkout 84e905c -- .
```

---

## Problem

v1.9.0 defined storefront tokens. Footer already uses `--store-max-width` and `--store-gutter`. The rest of `footer.css` still hardcodes rem gaps, padding, a 4px radius, and `--color-text-muted` (not a semantic token).

---

## In scope

CSS-only. Map values that already match the v1.9.0 scale:

```text
Container     keep var(--store-max-width) / var(--store-gutter)
Spacing       1rem → --space-16
              1.5rem → --space-24
              2rem → --space-32
              3rem → --space-48
              0.5rem → --space-8
              0.75rem → --space-12
Radius        focus ring 0.25rem → --radius-sm (admin :root, reuse)
Typography    font-family: var(--font-store)
Color         --color-text-muted → --color-muted
```

Leave off-scale locals as-is (do not invent `--space-40` / `--space-10`):

```text
0.625rem   list gap
1.25rem    link row gap
2.5rem     padding-xl / logo max-height
10rem      logo max-width
```

---

## Out of scope

```text
site-footer.blade.php rewrite
x-storefront.layout.page-container on the footer
FooterPageData / preview JSON
Header extract / Navigation chrome
Shop / Blog
Prompt font
Appearance / SiteIdentity
tokens.css scale expansion
```

---

## Isolation

`tests/Unit/Storefront/Ws002FooterTokenAdoptionTest.php` first (red), then `footer.css` only.

Forbidden in this change:

```text
SiteIdentityServiceInterface
WebsiteSettingsService
AppearanceController
feat/commerce-framework-v1
packages/storefront-design-system
modules/Footer/**
```

Blade still: `FooterPageData`, no `page-container`.
