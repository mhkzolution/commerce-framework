# WS-002 Header Foundation — Feature (v1.12.0)

**Date:** 2026-09-03  
**Status:** Locked as next storefront milestone  
**Owner:** `resources/views/components/storefront/layout` + `resources/css/storefront`  
**Mode:** Feature development (not recovery)

```text
v1.9.0  Foundations
v1.9.1  Footer Token Adoption
v1.10.0 Product Card System
v1.11.0 Shop Listing Layout     ← done (PR #10)
v1.12.0 Header Foundation       ← this file
v1.13.0 Blog Refresh
```

WS-002 listing chrome through Shop is closed. Header is the next UX change.

---

## Source of truth

Design from **current `main` storefront**, not the archive.

```text
✓ tokens + page-container (80rem / 24px gutter)
✓ Footer composition
✓ NavigationQueryServiceInterface::links('main')
✓ Website Settings brand keys
✓ x-storefront.cards.product
✓ Shop listing layout
```

Do not merge and do not path-extract a wholesale header from:

```text
feat/homepage-cms-preservation
feat/barcode-stock-recovery
feat/commerce-framework-v1
84e905c
```

If a single archive file is still the best *reference*, rewrite it onto current tokens in the same commit. Never `git checkout 84e905c -- .`.

---

## What this is

Replace the inline Cart layout header (`max-w-5xl`, hardcoded Shop / Blog / Cart) with a shared primitive that matches the rest of WS-002.

```blade
<x-storefront.layout.partials.site-header />
```

Width and gutter follow `--store-max-width` / `--store-gutter`, same as Homepage inner and Shop listing. Brand from Settings. Main nav from Navigation `links('main')`.

---

## Out of scope

```text
Mega menu
Mobile drawer as a product requirement (keep a simple collapse if needed)
PDP restyle
Blog redesign
Appearance / theme engine
Product card changes
```

---

## Implementation spec

To be written on `main` before branching `feat/ws002-header-v1`. Do not start Header until that spec is locked.
