# Footer Management — M3 Allowlist (locked)

**Date:** 2026-09-03  
**Status:** Implemented on `feat/footer-management-v1` (not pushed)  
**Does not start:** Website Settings, Navigation Recovery, WS-002, PR, tag

```text
M2 = Rendering Layer   (locked @ a7a43b0 — do not retouch)
M3 = Adapter Layer
```

Do not change:

```text
site-footer.blade.php
FooterPageData / FooterSectionData / FooterBrandData / FooterLinkData
POST /admin/settings/footer/preview JSON shape
footer.css
Cart storefront footer mount
```

Forbidden:

```text
git merge feat/commerce-framework-v1
git merge feat/barcode-stock-recovery
git checkout 84e905c -- .
SiteIdentityServiceInterface / SiteIdentityService
StorefrontNavigationConfig
WebsiteSettingsService
Appearance / Customer Experience controllers
```

---

## Adapters

Homepage pattern: thin query objects on current main. Footer does the same.

```text
Commerce\Settings\Services\FooterBrandingQuery
Commerce\Settings\Services\FooterNavigationQuery
Commerce\Settings\Services\FooterSocialQuery
```

### `FooterBrandingQuery`

Owns store identity reads that Brand + `{store_name}` placeholders need.

```text
current(): displayName, logoUrl, description
```

- `displayName`: `store.name` via `SettingQueryServiceInterface`, then `app.name`, then `Commerce Framework`
- `logoUrl`: fail-soft `store.logo_media_uuid` + `MediaQueryServiceInterface` (Homepage branding). Null if missing
- `description`: `store.description` / `site.description` if present, else null
- Never throws. Never imports SiteIdentity

### `FooterNavigationQuery`

```text
links(string $source): list  // always [] until v1.7
```

No `StorefrontNavigationConfig`. NavigationSectionDriver may still honor `FooterBuildContext` meta `footer_navigation` for tests; production path is the empty query.

### `FooterSocialQuery`

```text
links(): list  // always [] until Website Settings / v1.7
```

No SiteIdentity `socialLinks()`. SocialSectionDriver returns null when the list is empty (same visible result as M2).

---

## Wiring (drivers only)

```text
BrandSectionDriver        ← FooterBrandingQuery     (not SettingQueryServiceInterface)
NavigationSectionDriver   ← FooterNavigationQuery
SocialSectionDriver       ← FooterSocialQuery
FooterViewModelBuilder    ← FooterBrandingQuery     (for {store_name}; not SettingQuery)
```

`FooterConfigService` keeps `SettingQueryServiceInterface` — it owns `footer.config`, not identity.

`CmsSectionDriver` keeps `Page` — content owner is CMS, not Footer.

---

## Isolation

`FooterIsolationTest` M2 forbade adapter class names so they could not leak into rendering. M3 inverts that for PHP adapters only:

```text
Blade (FooterViewIsolationTest)     still forbids FooterBrandingQuery / Navigation / Social
Drivers                             must inject the matching query; must not import SettingQuery / SiteIdentity
Query classes                       may import SettingQuery + MediaQuery; must not import SiteIdentity / StorefrontNavigationConfig
Provider                            binds the three queries; still no SiteIdentity bind
```

Rewrite archive `V1DriversTest` off `SiteIdentityServiceInterface`. Do not copy the archive test verbatim.

---

## M3 done when

```text
✓ FooterBrandingQuery
✓ FooterNavigationQuery   (empty)
✓ FooterSocialQuery       (empty)
✓ Brand / nav / social drivers consume adapters
✓ {store_name} via FooterBrandingQuery
✓ fail-soft: missing settings / media / menus never 500
✓ FooterIsolationTest + FooterViewIsolationTest green
✓ existing M2 storefront/preview tests still green

✗ SiteIdentity
✗ StorefrontNavigationConfig
✗ Website Settings recovery
✗ Navigation Recovery
✗ WS-002
✗ Blade / storefront DTO / preview contract edits
```

Push / PR / `v1.6.0` happen after this commit, not during it.
