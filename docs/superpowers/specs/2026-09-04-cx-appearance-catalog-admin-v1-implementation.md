# CX / Appearance / Header / Workspace / Media Bulk — Implementation Spec (v1.17.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Branch:** `feat/pos-warehouse-scanner-v1` (same working tree as POS until user commits)

Feature: `docs/superpowers/specs/2026-09-04-cx-appearance-catalog-admin-feature.md`

---

## 1. Isolation

```text
Header blade / HeaderViewModelBuilder
  no SiteIdentityServiceInterface
  no AppearanceController / CustomerExperienceController
  no x-site.logo / x-site.favicon
  keep HeaderViewData + storefront-site-header
  keep Chromium ::details-content { content-visibility: visible; display: block; }

POS / WarehouseScanner isolation tests unchanged
```

---

## 2. Settings

- Copy CX + Appearance controllers, requests, views, `CustomerExperienceConfig`, `ThemeDesignTokens`
- `module.json` settings: `theme.*` color keys + `customer_experience.config`
- Routes: `module:customer-experience` on CX; appearance stays under settings (nav already `module => settings`)
- `<x-admin.design-tokens />` merges `ThemeDesignTokens::resolve()`

---

## 3. Media

- `MediaUploadService::upload()` accepts URL string (no TenantContext unless current tree already injects it)
- `MediaService::deleteMany` / `moveMany`
- Admin routes `import`, `bulk-move`, `bulk-delete`
- Library blade: import dialog + bulk bar from old (keep `@endif` on own lines)

---

## 4. Storefront CX

- Layout stacks `site-overlays` after footer
- Overlay blade receives resolved config from a view composer (not `app(CustomerExperienceConfig)` in WS-002 homepage/header isolation files)
- Quick view API from old Cart (`StorefrontQuickViewService`) adapted to current product/media contracts
- Skip overlays when `module:customer-experience` is disabled

---

## 5. Header

- Enrich current DTO header markup/CSS (search, account, cart)
- Do not restore mega-menu composer arrays
- Layout `<main>` default stays `max-w-5xl` until a page sets `main_class`

---

## 6. Product workspace

- Path-extract workspace views/services/API/import/settings from old
- Replace `ProductImageResolver` with `MediaQueryServiceInterface`
- Variant `price` is already minor units
- `feature:product-workspace` on workspace API if a flag is added; otherwise always on when product module is on

---

## 7. Tests

```text
tests/Unit/Settings/CustomerExperienceConfigTest.php
tests/Feature/Settings/CustomerExperienceSettingsTest.php
tests/Feature/Settings/AppearanceSettingsTest.php
tests/Feature/Media/MediaLibraryAdminTest.php  (extend: import + bulk)
tests/Feature/Product/ProductWorkspaceApiTest.php (ported, cents)
existing Ws002HeaderIsolationTest must stay green
```
