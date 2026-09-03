# Navigation Management — Feature (v1.7.x)

**Date:** 2026-09-03  
**Status:** Locked as next milestone on `main`  
**Owner:** `modules/Navigation` (implementation spec locked)  
**Mode:** Feature development (not recovery)

```text
main = active development line
v1.6.0 = last recovery-era tag (Footer)
v1.7.x = Navigation Management as a new feature
```

Do not merge:

```text
feat/homepage-cms-preservation
feat/barcode-stock-recovery
feat/commerce-framework-v1
```

Archive branches stay forensic. If archive files are useful, path-extract into this feature the same way Barcode / Homepage / Footer did — still not a merge.

---

## Why Navigation is next

Homepage and Footer already have empty or substitute consumers. Navigation is the missing owner of storefront links.

```text
Homepage
  └─ HomepageNavigationQuery   (catalog categories for arrival tabs — not menus)

Footer
  └─ FooterNavigationQuery::links($source)   currently []

v1.7
  └─ Navigation owns named menus
       FooterNavigationQuery reads them
```

`HomepageNavigationQuery` stays a Catalog adapter. v1.7 does not replace category tabs with menus unless a later spec says so.

---

## What Navigation owns

```text
Named menus (e.g. main, footer)
Ordered items: label, url, visibility
footer_enabled (or equivalent) for Footer sections
```

What it does not own:

```text
Brand / logo / social URLs          → Website Settings (v1.8.x)
CMS page bodies                     → CMS
Category trees                      → Catalog (Homepage arrival tabs)
Header/footer chrome, WS-002 tokens → WS-002 (v1.9.x)
```

Footer still owns composition (which section, order, layout). Navigation owns the link list a `navigation` section displays.

---

## Contract with Footer v1.6

`FooterNavigationQuery` is the seam. v1.7 fills it in place.

Today:

```php
public function links(string $source): array
{
    return [];
}
```

v1.7:

```text
links('main' | named source) → list of { label, url, ... }
empty / missing menu         → []  (fail-soft, same as v1.6)
never throws
never imports StorefrontNavigationConfig from the archive
```

Do not change:

```text
site-footer Blade
FooterPageData / FooterSectionData / FooterLinkData
preview JSON shape
```

The Footer driver already maps query items onto `FooterLinkData`. v1.7 should make `links()` return real rows, not invent a second footer renderer.

---

## Isolation (same rule as Barcode / Homepage / Footer)

Forbidden:

```text
StorefrontNavigationConfig     (archive type — rewrite onto current main)
SiteIdentityServiceInterface
WebsiteSettingsService
Appearance / Customer Experience controllers
WS-002 wholesale extract
git merge of archive branches
```

Allowed owners:

```text
modules/Navigation
NavigationQueryServiceInterface
FooterNavigationQuery (Footer read adapter only)
```

Implementation spec (locked before branch): `docs/superpowers/specs/2026-09-03-navigation-management-v1-implementation.md`

Write an isolation test before the first production commit, same pattern as `FooterIsolationTest`.

---

## Owner (locked)

```text
modules/Navigation
```

Settings does not store menus. Website Settings (v1.8) stays branding/config.

v1.7 visible outcome:

```text
Admin can edit named menus (main, footer)
FooterNavigationQuery::links($source) returns those items
GET / and GET /shop footer navigation section renders when the menu is non-empty
Empty menu still fail-soft (no 500, no archive types)
```

Out of v1.7:

```text
Website Settings identity / social
Header chrome / WS-002
Scanner, POS, Inventory, Marketplace
Merging feat/commerce-framework-v1
```

---

## Sequence

```text
1. Feature lock (this file)                         ✓
2. Implementation spec                              ✓  2026-09-03-navigation-management-v1-implementation.md
3. Isolation test (red) then branch from main
4. Build on main contracts; path-extract only if needed
5. Fill FooterNavigationQuery via NavigationQueryService
6. PR → squash merge → tag v1.7.0
```

Do not start Website Settings (v1.8.x) or WS-002 (v1.9.x) until Navigation is on the release line.
