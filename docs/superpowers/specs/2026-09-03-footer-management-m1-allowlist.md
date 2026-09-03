# Footer Management — M1 Allowlist (locked)

**Date:** 2026-09-03  
**Status:** Locked  
**Applies to:** `feat/footer-management-v1` when M1 starts  
**Does not start:** extraction, M2 storefront, M3 query adapters

```text
Footer owns composition.
Footer does not own Website Settings, Navigation, or WS-002.

M1 = /admin/settings/footer boots and saves footer.config
M1 ≠ storefront render
M1 ≠ SiteIdentityServiceInterface
```

Base: `main@6b63a8d`. Source: path extract from `84e905c` only.

Forbidden:

```text
git merge feat/commerce-framework-v1
git merge feat/barcode-stock-recovery
git checkout 84e905c -- .
```

---

## Isolation rules (gate)

Forbidden imports (PHP `use` and Blade):

```text
SiteIdentityServiceInterface
SiteIdentityService
StorefrontNavigationConfig
WebsiteSettingsService
AppearanceController
CustomerExperienceController
CustomerExperienceConfig
```

Forbidden recoveries:

```text
Appearance
Site Identity
Customer Experience
Navigation Recovery
WS-002
modules/Footer/**          (does not exist)
auth-footer / checkout footer
resources/css/storefront/components.css (whole file)
```

Allowed owners in M1:

```text
FooterConfigService            (footer.config)
SettingQueryServiceInterface
SettingRegistryServiceInterface
SettingServiceInterface
Commerce\Cms\Models\Page       (CMS page picker only)
ModuleRegistry                 (marketplace fail-soft)
```

M3 (not M1) introduces:

```text
FooterBrandingQuery
FooterNavigationQuery          (empty collection on main)
FooterSocialQuery
```

---

## M1 path extract (24 files)

Copy from `84e905c`, then strip forbidden imports in the same milestone.

### `modules/Settings/src/Footer/**` (12)

```text
src/Footer/Contracts/FooterSectionDriver.php
src/Footer/DTO/FooterBuildContext.php
src/Footer/DTO/FooterSection.php
src/Footer/DTO/FooterSectionConfig.php
src/Footer/Registry/FooterSectionRegistry.php
src/Footer/Drivers/BrandSectionDriver.php
src/Footer/Drivers/CmsSectionDriver.php
src/Footer/Drivers/CopyrightSectionDriver.php
src/Footer/Drivers/MarketplaceSectionDriver.php
src/Footer/Drivers/NavigationSectionDriver.php
src/Footer/Drivers/PoweredBySectionDriver.php
src/Footer/Drivers/SocialSectionDriver.php
```

### Services (3)

```text
modules/Settings/src/Services/FooterConfigService.php
modules/Settings/src/Services/FooterSectionManager.php
modules/Settings/src/Services/FooterViewModelBuilder.php
```

### Admin HTTP (4)

```text
modules/Settings/src/Http/Controllers/Admin/FooterController.php
modules/Settings/src/Http/Requests/Concerns/NormalizesFooterConfig.php
modules/Settings/src/Http/Requests/PreviewFooterRequest.php
modules/Settings/src/Http/Requests/UpdateFooterRequest.php
```

### Admin view + assets (3)

```text
modules/Settings/resources/views/admin/footer/index.blade.php
resources/css/admin/footer-settings.css
resources/js/admin/footer-settings.js
```

### Lang — relocate, do not keep host default-namespace files

Archive `resources/lang/{en,th}/footer.php` lands as:

```text
modules/Settings/resources/lang/en/footer.php
modules/Settings/resources/lang/th/footer.php
```

Registry `label_key` values become `settings::footer.section.*` so they match `loadTranslationsFrom(..., 'settings')`. Do not add Website Settings translations.

---

## M1 splices (edit existing files)

```text
modules/Settings/routes/web.php
  + GET    /admin/settings/footer
  + PUT    /admin/settings/footer
  + POST   /admin/settings/footer/preview
  middleware: auth, permission:settings.setting.view|update, module:footer-management
  Do not copy appearance / site-identity / customer-experience / translations routes.

modules/Settings/src/SettingsServiceProvider.php
  + singleton FooterConfigService, FooterSectionRegistry, FooterViewModelBuilder
  Do not bind SiteIdentityServiceInterface.
  Do not add site-footer View composer (that is M2).

resources/css/admin.css
  + @import './admin/footer-settings.css';

resources/js/admin.js
  + import './admin/footer-settings.js';
```

Already on `main` — verify only, do not pull archive versions:

```text
config/admin.php                         Website → Footer → admin.settings.footer.show
packages/.../SystemModuleCatalog.php     footer-management
resources/lang/{en,th}/nav.php           admin_settings_footer_show
```

---

## M1 rewrites (required — verbatim archive will not boot on main)

| File | Strip | M1 behavior |
|---|---|---|
| `BrandSectionDriver` | `SiteIdentityServiceInterface` | Read `store.name` via `SettingQueryServiceInterface` or return null |
| `SocialSectionDriver` | `SiteIdentityServiceInterface` | Return null (empty). Settings-key adapter is M3 |
| `NavigationSectionDriver` | `StorefrontNavigationConfig` | Return null / empty items |
| `FooterViewModelBuilder` | `SiteIdentityServiceInterface` | `{store_name}` from `SettingQueryService` / `config('app.name')` |
| `FooterController` | `StorefrontNavigationConfig`, `SiteIdentityService` | `navigationSources()` / `socialSources()` empty arrays. Keep `Page` for CMS picker. `Route::has` for identity/nav links already returns null |
| `FooterController::preview` | `view('components.storefront.layout.partials.site-footer')` | Return `html: ''` plus meta until M2 extracts `site-footer` |
| `SettingsServiceProvider` | `SiteIdentityService`, View composer | Footer singletons only |

`ensureRegistered()` already no-ops when `footer.config` exists, so save is not wiped on the next GET.

---

## M1 tests

Extract:

```text
tests/Feature/Settings/FooterSettingsTest.php
tests/Unit/Settings/FooterConfigServiceTest.php
tests/Unit/Footer/FooterSectionRegistryTest.php
tests/Unit/Footer/FooterSectionManagerTest.php
```

Add (Homepage pattern):

```text
tests/Unit/Settings/FooterIsolationTest.php
```

Must fail if any M1 PHP/Blade file contains a forbidden import string.

Defer:

```text
tests/Feature/Settings/FooterPreviewStatelessTest.php     (needs site-footer HTML)
tests/Feature/Storefront/Footer*.php                      (M2)
tests/Unit/Footer/Drivers/V1DriversTest.php               (mocks SiteIdentity — M3)
tests/Unit/Footer/FooterViewModelBuilderTest.php          (if it still constructs SiteIdentity)
```

---

## M1 done when

```text
GET  /admin/settings/footer     200 (auth + settings.setting.view)
PUT  /admin/settings/footer     persists footer.config
POST /admin/settings/footer/preview  200 with empty html (no 500)

php artisan test  covers FooterSettingsTest + FooterIsolationTest

No SiteIdentityServiceInterface
No StorefrontNavigationConfig
No Appearance / CX / Navigation controllers
No Cart storefront layout change
```

## Explicitly not M1

```text
site-footer.blade.php
resources/css/storefront/footer.css
Cart storefront layout splice
FooterBrandingQuery / FooterNavigationQuery / FooterSocialQuery
Website Settings
Navigation Recovery
```
