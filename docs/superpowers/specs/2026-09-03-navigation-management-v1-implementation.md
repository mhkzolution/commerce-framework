# Navigation Management v1 — Implementation Spec (locked)

**Date:** 2026-09-03  
**Status:** Locked — do not branch until this file is the contract  
**Milestone:** v1.7.x on `main`  
**Mode:** Feature on the release line (not recovery)

```text
Navigation owns menus
Settings owns branding/configuration     (v1.8.x)
CMS owns content
Catalog owns category trees              (Homepage arrival tabs)
Footer owns composition                  (v1.6.0, do not retouch)
```

Base: current `main`. Do not branch from `84e905c`. Do not merge archive branches.

```text
git merge feat/homepage-cms-preservation
git merge feat/barcode-stock-recovery
git merge feat/commerce-framework-v1
git checkout 84e905c -- .
```

Path-extract from archive only if a single file is still the best source, then rewrite forbidden types in the same commit.

Feature overview: `docs/superpowers/specs/2026-09-03-navigation-management-feature.md`

---

## 1. Owner

**Locked: `modules/Navigation`.**

Settings must not grow a menu store. Website Settings is v1.8 and must stay the branding/config owner. Putting menus in Settings would make it a god module.

| Domain | Owner |
|---|---|
| Named menus + items | `modules/Navigation` |
| Footer section order / layout | Settings Footer (`footer.config`) |
| Brand, logo, social, SEO defaults | Website Settings (v1.8 — out) |
| CMS pages | CMS |
| Category tabs on homepage | Catalog via `HomepageNavigationQuery` |

Admin chrome already has **Website → Navigation** pointing at `admin.storefront.navigation.show` under `module: settings`. v1.7 rebinds that link to the Navigation module (new route + IAM). Do not implement the archive Appearance/Storefront navigation controller.

---

## 2. Named menu contract

Public read API lives in `packages/commerce/contracts` so Footer does not import the Navigation module.

```php
namespace Commerce\Contracts\Navigation;

interface NavigationQueryServiceInterface
{
    /**
     * @return list<NavigationLinkData>
     */
    public function links(string $source): array;
}
```

```php
$navigation->links('main');
$navigation->links('footer');
$navigation->links('account');
```

- `$source` is a menu **handle** (`^[a-z][a-z0-9-]*$`).
- Return type is `list<NavigationLinkData>` — never Eloquent, never a raw array, never a config bag.
- Unknown handle, disabled module, empty menu, or any exception → `[]`. Never throws.

### `NavigationLinkData`

Lives in `packages/commerce/contracts` so the query interface is fully typed and Footer never imports `Commerce\Navigation\*`.

```text
Commerce\Contracts\Navigation\NavigationLinkData

label: string
url: string
key: ?string          // optional handle for CSS / tests; unused by Footer v1
footerEnabled: bool   // default true — Footer visibility_mode footer_enabled_only
```

Module maps Eloquent → `NavigationLinkData`. `FooterNavigationQuery` maps `NavigationLinkData` → the driver array `{label, url, footer_enabled}` already consumed by `NavigationSectionDriver`. Do not put a second DTO in the Navigation module.

v1.7 seeded handles:

```text
main
footer
```

`account` is a reserved handle in the query API. Do not require an account menu row in v1.7. Calling `links('account')` returns `[]` until a later spec seeds it.

---

## 3. Footer integration boundary

Today:

```text
FooterNavigationQuery::links($source) → []
NavigationSectionDriver uses that list (or FooterBuildContext meta in tests)
```

v1.7:

```text
FooterNavigationQuery
  → NavigationQueryServiceInterface::links($source)
  → map NavigationLinkData → array{label, url, footer_enabled}
  → [] on missing binding / empty / throw
```

`$source` is the Footer section setting (default in `footer.config` is `main`). Do **not** hardcode `links('footer')` inside `FooterNavigationQuery`. A footer section whose source is `footer` calls `links('footer')`; one whose source is `main` calls `links('main')`.

The only Settings production files that should change for this seam:

```text
modules/Settings/src/Services/FooterNavigationQuery.php
modules/Settings/src/SettingsServiceProvider.php   (optional: type-hint the contract)
```

Do not change:

```text
site-footer.blade.php
FooterPageData / FooterSectionData / FooterBrandData / FooterLinkData
POST /admin/settings/footer/preview JSON shape
footer.css
Cart storefront footer mount
NavigationSectionDriver mapping rules (max_links, visibility_mode)
```

Keep `FooterBuildContext` meta `footer_navigation` as a test override in the driver. Production path is the query.

---

## 4. Homepage boundary

Do not merge these:

```text
HomepageNavigationQuery     Catalog categories → arrival tabs
NavigationQueryService      named menus
```

`HomepageNavigationQuery` stays a Catalog adapter. v1.7 does not feed menus into homepage sections, and does not read categories into menus unless a later spec says so.

---

## 5. v1.7 scope (M1 of Navigation = the whole tag)

v1.7 is one milestone. There is no storefront-chrome M2.

```text
Navigation Management v1

✓ modules/Navigation
✓ Admin CRUD for menus + ordered items
✓ Named menus main, footer (seeded)
✓ NavigationQueryServiceInterface + NavigationLinkData
✓ FooterNavigationQuery integration
✓ Fail-soft when menu missing / module disabled
✓ Isolation test (no StorefrontNavigationConfig / SiteIdentity / WebsiteSettings)

✗ Mega menu / nested children
✗ Per-item IAM / permissions menu
✗ Mobile menu builder
✗ Header redesign / storefront header mount
✗ WS-002
✗ Website Settings / social / logo
✗ account menu editor (handle reserved only)
✗ HomepageNavigationQuery rewrite
```

Value path:

```text
Admin creates/edits footer or main menu
        ↓
Navigation owns rows
        ↓
FooterNavigationQuery reads links($source)
        ↓
Footer navigation section renders FooterLinkData
```

---

## Data model

Two tables, owned by `modules/Navigation`. No Settings JSON bag.

### `navigation_menus`

| Column | Notes |
|---|---|
| `id` | bigint PK |
| `uuid` | unique, public |
| `handle` | unique, `main` / `footer` / later `account` |
| `name` | admin label |
| `created_at` / `updated_at` | |

### `navigation_menu_items`

| Column | Notes |
|---|---|
| `id` | bigint PK |
| `uuid` | unique, public |
| `menu_id` | FK `navigation_menus.id` cascade |
| `label` | required |
| `url` | required (path or absolute URL) |
| `position` | unsigned int, order |
| `is_visible` | bool, default true — hidden items omitted from `links()` |
| `footer_enabled` | bool, default true |
| `created_at` / `updated_at` | |

No `parent_id` in v1.7 (that is mega/nested, out of scope).

Seeder: insert `main` and `footer` menus with **zero items**. Empty is valid and fail-soft. Do not invent default Shop/About links.

---

## Module surface

```text
modules/Navigation/
  module.json                 alias: navigation
  src/NavigationServiceProvider.php
  src/Services/NavigationQueryService.php
  src/Http/Controllers/Admin/...
  src/Models/NavigationMenu.php
  src/Models/NavigationMenuItem.php
  database/migrations/...
  resources/views/admin/...
```

Catalog: add `navigation` (or `navigation-management`) to `SystemModuleCatalog`. Not core. Default ENABLED so Footer can read after seed.

Composer path repo + `modules/Navigation/module.json` like Barcode.

### Admin

- Reuse sidebar **Website → Navigation**
- Route: `admin.navigation.show` (or `admin.navigation.menus.index`) at `/admin/navigation`
- Permission: `navigation.menu.view` / `navigation.menu.update` (do not reuse `settings.setting.view`)
- Middleware: `module:navigation`
- UI: list seeded menus, edit items (label, url, order, visible, footer_enabled). No drag-tree, no mega columns.

Update `config/admin.php` Navigation child: `module` → `navigation`, `permission` → `navigation.menu.view`, `route` → the new route. Dead `admin.storefront.navigation.show` stays unused.

### IAM

Register permissions in `module.json` like Barcode. Seed via existing IAM patterns. Superadmin gets them.

---

## Isolation

`tests/Unit/Navigation/NavigationIsolationTest.php` written **before** production classes (red: files missing).

Forbidden in `modules/Navigation` and in the Footer seam file:

```text
StorefrontNavigationConfig
SiteIdentityServiceInterface
SiteIdentityService
WebsiteSettingsService
AppearanceController
CustomerExperienceController
CustomerExperienceConfig
```

Forbidden recoveries:

```text
WS-002 / whole components.css
Header storefront partials
Mega menu from archive
git merge of archive branches
```

`FooterViewIsolationTest` still forbids `FooterNavigationQuery` in Blade — do not put the query into `site-footer`.

`FooterIsolationTest` already requires `FooterNavigationQuery` on the driver. Extend it so `FooterNavigationQuery` may inject `NavigationQueryServiceInterface` and must not inject Eloquent models.

---

## Branch / test / release

```text
1. This spec locked
2. Branch feat/navigation-management-v1 from main (after docs commit if desired)
3. Isolation test red
4. Module + tables + admin CRUD
5. NavigationQueryService + FooterNavigationQuery
6. Feature tests: admin save, links('footer') DTO list, shop footer shows items, missing menu → []
7. Full suite
8. PR → squash merge → tag v1.7.0
```

Do not start Website Settings (v1.8.x) or WS-002 (v1.9.x) on this branch.
