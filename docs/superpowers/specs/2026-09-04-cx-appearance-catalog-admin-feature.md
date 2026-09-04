# Customer Experience, Appearance, Header, Workspace, Media Bulk — Feature (v1.17.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Mode:** Path-extract from `commerce-framework-old/` onto current `main`. Not archive merge. Not wholesale copy.

```text
v1.16.0 POS + Warehouse Scanner   ← previous slice (uncommitted on feat/pos-warehouse-scanner-v1)
v1.17.0 CX / Appearance / header / workspace / media bulk  ← this file
```

Implementation: `docs/superpowers/specs/2026-09-04-cx-appearance-catalog-admin-v1-implementation.md`

Do not commit `commerce-framework-old/`.

---

## 1. Surfaces

```text
GET/PUT  /admin/settings/customer-experience   module:customer-experience
GET/PUT  /admin/settings/appearance            module:settings
GET      /shop (header chrome)                 HeaderViewData only
GET/PUT  /admin/products/{uuid}/edit           product workspace
POST     /admin/media/import                   media upload
POST     /admin/media/bulk-move|bulk-delete    media update/delete
```

---

## 2. Definition of Done

```text
CX admin     Customer Experience page saves JSON config (quick view, notifications, back-to-top)
Appearance   Theme color admin writes theme.* settings; design-tokens consume ThemeDesignTokens
Header       Keep storefront-site-header + HeaderViewData; no SiteIdentity, no mega-menu arrays
Overlays     Quick view / toasts / back-to-top on storefront when CX module is on
Workspace    Product create/edit uses workspace UI + workspace API
Media        Import from URL; bulk move/delete on library
Gates        Disabled customer-experience module → CX admin 404
Identity     Header blade stays DTO-only (existing Ws002HeaderIsolationTest)
```

---

## 3. Out of scope

```text
Translation manager / mail / auth settings restore
Mega-menu + x-site.logo / SiteIdentityServiceInterface
feat/commerce-framework-v1 merge
commerce-framework-old commit
```
