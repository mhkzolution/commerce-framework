# Website Settings — Feature (v1.8.x)

**Date:** 2026-09-03  
**Status:** Locked as next milestone on `main`  
**Owner:** `modules/Settings` (branding/configuration — already locked in Navigation v1.7)  
**Mode:** Feature development (not recovery)

```text
main = active development line
v1.7.0 = Navigation Management
v1.8.x = Website Settings as a new feature
```

Do not merge:

```text
feat/homepage-cms-preservation
feat/barcode-stock-recovery
feat/commerce-framework-v1
```

---

## Why Website Settings is next

Footer, Homepage, and Barcode already read store identity through settings keys. Footer social is still empty. Those consumers need a data owner and an admin, not a design system.

```text
Footer
  ├─ FooterBrandingQuery     store.name / logo / description   (already reads keys)
  └─ FooterSocialQuery       [] until v1.8

Homepage
  └─ HomepageBrandingQuery   store.name / logo

Barcode
  └─ BarcodeOwnerResolver    store.name

v1.8
  └─ Settings owns brand, contact, social, SEO defaults
       FooterSocialQuery reads social links
```

WS-002 stays v1.9. Do not recover Appearance / Site Identity / Customer Experience controllers.

---

## What Website Settings owns

```text
Brand: name, logo, tagline/description
Contact: email, phone
Social URLs: facebook, instagram, tiktok, line
SEO defaults: title suffix, default meta description, default OG image
```

What it does not own:

```text
Named menus                         → Navigation (v1.7.0)
Footer section order / layout       → Footer composition (v1.6.0)
CMS page bodies / per-page SEO      → CMS (SeoServiceInterface per entity)
Category trees                      → Catalog
Header/footer chrome, tokens        → WS-002 (v1.9.x)
Mail / auth / currency / timezone   → existing Settings groups
```

---

## Owner (locked)

```text
modules/Settings
```

Not a new module. Navigation v1.7 already locked:

```text
Navigation     -> named menus
Footer         -> composition
CMS            -> content
Settings       -> branding/configuration
Homepage       -> catalog category tabs
```

A `modules/Website` package would split the settings domain for no gain. Settings is already core.

Storage: registered setting keys (same `settings` table), not a new Website table, not archive `WebsiteSettingsService`.

---

## Contract with Footer v1.6

`FooterSocialQuery` is the empty seam. v1.8 fills it in place.

Today:

```php
public function links(): array
{
    return [];
}
```

v1.8:

```text
links() → list of { label, url, key }
empty / missing URLs → []
never throws
never imports WebsiteSettingsService / SiteIdentityServiceInterface
```

Do not change:

```text
site-footer Blade
FooterPageData / FooterSectionData / FooterBrandData / FooterLinkData
preview JSON shape
footer.css
```

`FooterBrandingQuery` already reads `store.name` / `store.logo_media_uuid` / `store.description`. v1.8 writes those keys from the Website Settings admin. Do not invent a second brand store.

---

## Isolation

Forbidden:

```text
SiteIdentityServiceInterface
SiteIdentityService
WebsiteSettingsService          (archive type — do not revive the name)
AppearanceController
CustomerExperienceController
CustomerExperienceConfig
WS-002 wholesale extract
git merge of archive branches
```

Allowed owners:

```text
modules/Settings (website admin + registered keys)
WebsiteSettingsQueryServiceInterface
FooterSocialQuery (Footer read adapter only)
```

Implementation spec: `docs/superpowers/specs/2026-09-03-website-settings-v1-implementation.md`

Write an isolation test before the first production commit.

---

## Sequence

```text
1. Feature lock (this file)
2. Implementation spec
3. Isolation test (red) then branch from main
4. Admin + keys + query contract + FooterSocialQuery
5. PR → squash merge → tag v1.8.0
```

Do not start WS-002 (v1.9.x) on this branch.
