# Website Settings v1 — Implementation Spec (locked)

**Date:** 2026-09-03  
**Status:** Locked — do not branch until this file is the contract  
**Milestone:** v1.8.x on `main`  
**Mode:** Feature on the release line (not recovery)

```text
Navigation owns menus                 (v1.7.0, do not retouch)
Settings owns branding/configuration  (this spec)
CMS owns content
Catalog owns category trees
Footer owns composition               (v1.6.0, do not retouch Blade/DTOs/preview)
```

Base: current `main`. Do not branch from `84e905c`. Do not merge archive branches.

```text
git merge feat/homepage-cms-preservation
git merge feat/barcode-stock-recovery
git merge feat/commerce-framework-v1
git checkout 84e905c -- .
```

Path-extract from archive only if a single file is still the best source, then rewrite forbidden types in the same commit.

Feature overview: `docs/superpowers/specs/2026-09-03-website-settings-feature.md`

---

## 1. Owner

**Locked: `modules/Settings`.**

Do not add `modules/Website`. Settings already owns configuration; v1.8 is the dedicated admin + keys for public site identity.

| Domain | Owner |
|---|---|
| Brand, contact, social, SEO defaults | Settings (`store.*` / `website.*` keys) |
| Named menus | Navigation |
| Footer section order / layout | Settings Footer (`footer.config`) |
| Per-page / per-post SEO rows | CMS via `SeoServiceInterface` |
| Category tabs | Catalog via `HomepageNavigationQuery` |

Admin chrome already has **Settings → Website Settings** pointing at dead `admin.settings.site-identity.show`. v1.8 binds that surface to a real controller. Do not implement archive Appearance / Site Identity / Customer Experience controllers.

Dead **Website → Storefront** (`admin.settings.appearance.show`) and **Customer Experience** stay unused.

---

## 2. Query contract

Public read API lives in `packages/commerce/contracts`.

```php
namespace Commerce\Contracts\Settings;

interface WebsiteSettingsQueryServiceInterface
{
    public function brand(): WebsiteBrandData;

    /**
     * @return list<WebsiteSocialLinkData>
     */
    public function socialLinks(): array;

    public function contact(): WebsiteContactData;

    public function seoDefaults(): WebsiteSeoDefaultsData;
}
```

Never return Eloquent, a raw config bag, or archive `WebsiteSettingsService`.

Unknown / empty / throw → empty strings, `null` URLs, or `[]`. Never throws.

### DTOs (contracts package)

```text
WebsiteBrandData
  name: string
  logoUrl: ?string
  description: ?string

WebsiteSocialLinkData
  key: string          // facebook | instagram | tiktok | line
  label: string
  url: string

WebsiteContactData
  email: ?string
  phone: ?string

WebsiteSeoDefaultsData
  titleSuffix: ?string
  defaultDescription: ?string
  defaultOgImageUrl: ?string
```

`FooterSocialQuery` maps `socialLinks()` → `{label, url, key}` for `SocialSectionDriver`. Footer does not import a Settings website namespace beyond the query it already has.

Brand readers that already work stay on their current keys in v1.8:

```text
FooterBrandingQuery
HomepageBrandingQuery
BarcodeOwnerResolver
```

They keep reading `store.name` / `store.logo_media_uuid` / `store.description` via `SettingQueryServiceInterface`. Do not rewrite them onto the new query in this milestone. The Website Settings admin **writes those same keys** so existing readers light up without a consumer rewrite.

`WebsiteSettingsQueryServiceInterface::brand()` exists so later consumers (header, emails) have one contract. Footer branding does not have to switch in v1.8.

---

## 3. Footer integration boundary

Today:

```text
FooterSocialQuery::links() → []
SocialSectionDriver uses that list
Footer admin socialSources() reports all networks as missing
```

v1.8:

```text
FooterSocialQuery
  → WebsiteSettingsQueryServiceInterface::socialLinks()
  → map WebsiteSocialLinkData → array{label, url, key}
  → [] on missing binding / empty / throw
```

`$source` is not a parameter. Social is one site-wide list, not named menus.

The Settings production files that should change for this seam:

```text
modules/Settings/src/Services/FooterSocialQuery.php
modules/Settings/src/SettingsServiceProvider.php
modules/Settings/src/Http/Controllers/Admin/WebsiteSettingsController.php   (new)
modules/Settings/routes/web.php
```

Optional, not required for storefront value: `FooterController::socialSources()` may call `socialLinks()` so the Footer editor shows configured vs missing. Do not change preview JSON or Blade to do this.

Do not change:

```text
site-footer.blade.php
FooterPageData / FooterSectionData / FooterBrandData / FooterLinkData
POST /admin/settings/footer/preview JSON shape
footer.css
Cart storefront footer mount
SocialSectionDriver mapping rules
FooterBrandingQuery
```

Register route name `admin.settings.website.show`. Also alias `admin.settings.site-identity.show` to the same action so Footer admin `Route::has('admin.settings.site-identity.show')` already works without editing `FooterController`.

---

## 4. Other boundaries

```text
HomepageBrandingQuery     keep store.* keys; do not merge with social
Navigation                named menus; do not store social URLs in menus
SeoServiceInterface       per-entity rows; v1.8 does not rewrite CMS SEO resolution
WS-002                    out
```

SEO defaults are stored and readable via `seoDefaults()`. CMS / blog templates do not have to consume them in v1.8.

---

## 5. v1.8 scope (M1 = the whole tag)

```text
Website Settings v1

✓ Dedicated admin at /admin/settings/website
✓ Register missing store/website keys
✓ Brand fields write store.name, store.logo_media_uuid, store.description
✓ Contact fields write store.email, store.phone
✓ Social URL fields (facebook, instagram, tiktok, line)
✓ SEO default fields (stored only)
✓ WebsiteSettingsQueryServiceInterface + DTOs
✓ FooterSocialQuery integration
✓ Fail-soft when URLs missing
✓ Isolation test (no SiteIdentity / WebsiteSettingsService / Appearance / CX / WS-002)

✗ Appearance / theme / components.css
✗ SiteIdentityServiceInterface recovery
✗ Customer Experience / quick view
✗ Header storefront mount
✗ New Footer contact section
✗ Extra networks beyond Footer's four
✗ Rewiring HomepageBrandingQuery / BarcodeOwnerResolver onto the new contract
✗ Per-page SEO editor (already CMS)
```

Value path:

```text
Admin saves Facebook / LINE URLs
        ↓
Settings owns social.* keys
        ↓
FooterSocialQuery reads socialLinks()
        ↓
Footer social section renders FooterLinkData
```

Brand value path (no Footer query rewrite):

```text
Admin saves name + logo
        ↓
store.name / store.logo_media_uuid
        ↓
FooterBrandingQuery / HomepageBrandingQuery already read them
```

---

## Setting keys

Register in Settings (seeder + `config/settings.php` defaults where useful). Group `store` for identity already used by Footer/Homepage; group `social` for network URLs.

| Key | Group | Notes |
|---|---|---|
| `store.name` | store | exists |
| `store.email` | store | exists |
| `store.logo_media_uuid` | store | already read, not registered — register |
| `store.description` | store | already read, not registered — register |
| `store.phone` | store | new |
| `social.facebook` | social | URL or empty |
| `social.instagram` | social | |
| `social.tiktok` | social | |
| `social.line` | social | |
| `website.seo.title_suffix` | website | |
| `website.seo.default_description` | website | |
| `website.seo.default_og_image_media_uuid` | website | Media UUID, resolve URL in query |

No new tables. No `modules/Website`. Empty string = unset = omit from `socialLinks()`.

Social keys match `FooterController::socialSources()` (`facebook`, `instagram`, `tiktok`, `line`). Do not add X/YouTube in v1.8.

---

## Admin

- Reuse sidebar **Settings → Website Settings**
- Path: `/admin/settings/website`
- Names: `admin.settings.website.show` / `admin.settings.website.update`
- Alias: `admin.settings.site-identity.show` → same show action
- Permission: `settings.setting.view` / `settings.setting.update` (same as Footer)
- Middleware: existing settings auth; no new system module
- UI: one form — brand, contact, social, SEO defaults. Logo via existing Media picker if one exists; otherwise UUID text field. No WS-002 layout, no appearance theme editor.

Update `config/admin.php` Website Settings child: `route` → `admin.settings.website.show`. Keep `module` → `settings`.

Generic `/admin/settings` index may still show the `store` group. That is acceptable duplication for v1.8. Do not remove currency/timezone from System Settings.

---

## Isolation

`tests/Unit/Settings/WebsiteSettingsIsolationTest.php` written **before** production classes (red: files missing).

Forbidden in the new admin/query files and in `FooterSocialQuery`:

```text
SiteIdentityServiceInterface
SiteIdentityService
WebsiteSettingsService
AppearanceController
CustomerExperienceController
CustomerExperienceConfig
StorefrontNavigationConfig
```

Forbidden recoveries:

```text
WS-002 / whole components.css
Header storefront partials
git merge of archive branches
modules/Website/**
```

`FooterViewIsolationTest` still forbids `FooterSocialQuery` in Blade.

`FooterIsolationTest` already requires `FooterSocialQuery` on the social driver. Extend so `FooterSocialQuery` may inject `WebsiteSettingsQueryServiceInterface` and must not inject Eloquent.

---

## Branch / test / release

```text
1. This spec locked
2. Branch feat/website-settings-v1 from main (after docs commit if desired)
3. Isolation test red
4. Keys + admin form + query implementation
5. FooterSocialQuery
6. Feature tests: admin save, socialLinks() DTO list, shop footer shows social, empty → []
7. Full suite
8. PR → squash merge → tag v1.8.0
```

Do not start WS-002 (v1.9.x) on this branch.
