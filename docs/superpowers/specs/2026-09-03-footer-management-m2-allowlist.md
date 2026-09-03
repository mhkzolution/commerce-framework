# Footer Management — M2 Allowlist (locked)

**Date:** 2026-09-03  
**Status:** Implemented on `feat/footer-management-v1` (not pushed, no PR)  
**Applies to:** M2 Storefront Render  
**Does not start:** M3 query adapters, PR, tag

```text
M1 = admin can save footer.config
M2 = storefront and admin preview render the same footer HTML from DTOs
M2 ≠ Website Settings
M2 ≠ Navigation Recovery
M2 ≠ WS-002
M2 ≠ FooterBrandingQuery / FooterNavigationQuery / FooterSocialQuery
```

Branch: `feat/footer-management-v1` @ `79cf274` (M1). Source CSS/Blade: path extract from `84e905c` only.

Forbidden:

```text
git merge feat/commerce-framework-v1
git merge feat/barcode-stock-recovery
git checkout 84e905c -- .
```

---

## Preview contract

`POST /admin/settings/footer/preview` keeps the M1 JSON shape. Only `html` changes.

M1 (current):

```json
{
  "html": "",
  "meta": {
    "total_sections": 0,
    "visible_sections": 0,
    "hidden_sections": 0,
    "hidden_reasons": []
  }
}
```

M2:

```json
{
  "html": "<footer class=\"storefront-site-footer ...\">...</footer>",
  "meta": {
    "total_sections": 6,
    "visible_sections": 2,
    "hidden_sections": 4,
    "hidden_reasons": []
  }
}
```

Rules:

- Same Blade renders admin preview and storefront. No second markup tree.
- If footer is disabled or every section is empty, `html` may be `""`. That is still a successful M2 render, not a 500.
- `meta` stays a counts object. Do not put Settings/Page rows in `meta`.

---

## Storefront DTO contract

Archive `site-footer` reads a loosely typed `$viewModel` array (`meta.logo_url`, `items[].label`). M2 must not keep that. Homepage already consumes typed DTOs; Footer follows that.

Driver-internal `FooterSection` (id / type / titleKey / items / meta) stays behind the builder. Blade never receives it.

New storefront DTOs (write on this branch — they do not exist on `84e905c`):

```text
Commerce\Settings\Footer\DTO\FooterPageData
Commerce\Settings\Footer\DTO\FooterSectionData
Commerce\Settings\Footer\DTO\FooterBrandData
Commerce\Settings\Footer\DTO\FooterLinkData
```

### `FooterPageData`

```text
enabled: bool
className: string          // joined layout token classes, already resolved
sections: list<FooterSectionData>
```

### `FooterSectionData`

```text
id: string
type: string               // brand | navigation | cms | social | marketplace | copyright | powered_by
title: ?string             // already translated; null means no heading
ariaLabel: string
brand: ?FooterBrandData    // type=brand only
links: list<FooterLinkData> // navigation, cms, social, marketplace
text: ?string              // copyright / powered_by, placeholders already resolved
```

### `FooterBrandData`

```text
displayName: ?string
logoUrl: ?string
description: ?string
```

### `FooterLinkData`

```text
label: string
url: string
key: ?string               // social network key; unused for CMS/nav
```

Mapper (M2 rewrite of `FooterViewModelBuilder` or a thin `FooterPagePresenter`):

```text
FooterSection (driver)  →  FooterSectionData
array layout tokens     →  FooterPageData.className
titleKey                →  title / ariaLabel via __()
{year} {store_name}     →  resolved strings (M1 SettingQuery fallback)
```

Blade may use `$footer->sections`, `$section->brand->logoUrl`, `$link->url`. Blade may not use `$section['meta']`, `title_key`, `Page`, `Setting`, or `defaultVariant`.

---

## Isolation rules (M2 adds Blade)

Forbidden in PHP `use`, Blade, and CSS comments:

```text
SiteIdentityServiceInterface
SiteIdentityService
StorefrontNavigationConfig
WebsiteSettingsService
AppearanceController
CustomerExperienceController
CustomerExperienceConfig
Commerce\Cms\Models\Page          (Blade and presenter only; Page stays in FooterController CMS picker)
```

`Page` remains allowed in `FooterController::cmsPages()` (M1). It is forbidden in `site-footer` and in the presenter that builds `FooterPageData`.

Forbidden recoveries (unchanged):

```text
Appearance / Site Identity / Customer Experience
Navigation Recovery
WS-002 / whole components.css
auth-footer / checkout footer
modules/Footer/**
FooterBrandingQuery / FooterNavigationQuery / FooterSocialQuery
```

Extend `FooterIsolationTest` in the same M2 commit:

- Scan `site-footer` Blade for forbidden tokens and for `['meta']` / `title_key`
- Assert presenter / builder do not type-hint `Page` or SiteIdentity
- Keep M1 constructor-graph assertions
- Provider **must** bind the site-footer composer (M1 forbade this; M2 requires it)
- `site-footer` + `footer.css` **must** exist; auth-footer / checkout footer / `modules/Footer` still must not

Add `tests/Unit/Settings/FooterViewIsolationTest.php` in the same commit (Homepage blade lock, Footer-shaped):

```text
Forbidden in storefront footer views:

- SettingQueryServiceInterface
- Page
- FooterSection          (driver DTO — not FooterSectionData)
- FooterConfig / FooterConfigService
- Model access (::query, Eloquent)
- title_key, ['meta'], $section[
- SiteIdentity, StorefrontNavigationConfig, WebsiteSettingsService
- FooterBrandingQuery, FooterNavigationQuery, FooterSocialQuery

Allowed:

- FooterPageData
- FooterSectionData
- FooterBrandData
- FooterLinkData
```

---

## M2 path extract

From `84e905c`, then rewrite Blade onto DTOs in the same commit:

```text
resources/views/components/storefront/layout/partials/site-footer.blade.php
```

CSS is a **range extract**, not the host file:

```text
84e905c  resources/css/storefront/components.css  lines 1352–1600
→        resources/css/storefront/footer.css
```

Do not add `resources/css/storefront/components.css`.

Tests to extract and rewrite (`saveSiteIdentity` must go):

```text
tests/Feature/Storefront/FooterRenderingTest.php
tests/Feature/Storefront/FooterPreviewParityTest.php
tests/Feature/Storefront/FooterGracefulFailureTest.php
tests/Feature/Settings/FooterPreviewStatelessTest.php
```

Rewrite those tests to seed `store.name` via `SettingQueryServiceInterface` / Settings seeder, and to pass `FooterPageData` into the view (or hit `/` / shop layout). Do not restore SiteIdentity to make them green.

---

## M2 splices

```text
modules/Cart/resources/views/layouts/storefront.blade.php
  + <x-storefront.layout.partials.site-footer />
  + @vite(['resources/css/storefront/footer.css'])  (layout-level so every storefront page gets it)

modules/Settings/src/SettingsServiceProvider.php
  + View composer for components.storefront.layout.partials.site-footer
  + composer builds FooterPageData; if already present, do not rebuild (preview injects it)
  + no SiteIdentity bind

vite.config.js
  + resources/css/storefront/footer.css

FooterController::preview
  + render the shared Blade with FooterPageData
  + html is that render, not ""
```

Already on the branch from M1 — do not revert:

```text
/admin/settings/footer
footer.config save
FooterIsolationTest (extend, do not delete)
```

---

## M2 done when

```text
M2 Storefront Render

✓ POST /admin/settings/footer/preview
  returns { html: "<footer ...>" }

✓ storefront-site-footer blade exists

✓ Cart layout (หรือ storefront layout ปัจจุบัน)
  render footer component

✓ FooterPageData only
✓ FooterSectionData only
✓ FooterBrandData only
✓ FooterLinkData only

✓ brand section render
✓ copyright render
✓ powered_by render

✓ nav section fail-soft
✓ social section fail-soft

✗ SiteIdentity
✗ StorefrontNavigationConfig
✗ WebsiteSettingsService
✗ FooterBrandingQuery
✗ FooterNavigationQuery
✗ FooterSocialQuery
```

## Explicitly not M2

```text
FooterBrandingQuery / FooterNavigationQuery / FooterSocialQuery   (M3)
Navigation menus with real items                                 (empty until v1.7)
Social URLs from Website Settings                                (empty until M3/v1.7)
WS-002 primitives, auth-footer, checkout footer
Push / PR / v1.6.0 tag
```

M2 may show brand (from `store.name`), copyright, and powered_by. Navigation/social/marketplace sections stay fail-soft empty. That is enough for a visible storefront footer on the v1.5.0 layout.
