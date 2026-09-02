# Feature Flags Framework Phase 2 Design

**Branch:** `feat/feature-flags` (from `main` at `v1.0.0-module-management`)  
**Date:** 2026-09-02  
**Status:** Ready for spec review (architecture approved in chat; four review notes applied)

## Goal

Ship a **centralized feature enable/disable registry** that Super Admins can manage, that other code can query later, and that is automatically off when the parent module is DISABLED.

This is an **infrastructure release**, not a behavior-change release. After merge, the running product must stay **100% backward compatible** because no product code calls `feature()` yet.

## Principle

Feature Flags v1 defaults are **ENABLED**. New flags may set `default_enabled = false` in the catalog without changing seeder logic.

```text
Catalog = source of defaults
DB      = source of truth for status
```

## Current state

- Module Management v1.0 is on `main` (`system_modules`, `ModuleService`, `module_*` helpers, `module:{code}` middleware, System → Modules, IAM audit).
- No `system_features` table, no `FeatureService`, no System → Features page.
- CMS scheduled publishing, advanced SEO, review monitor, and AI writer **must not** be wired in this branch.
- Unrelated WIP lives in stash `wip: homepage/cms on feat/commerce-framework-v1` and on `feat/cms-scheduled-publishing`. Do not mix those into this PR.

## Out of scope

- CMS / Reviews / SEO / AI Writer product integration
- Percentage rollout, user targeting, environment overrides, remote config
- Feature scheduling, experimentation, A/B testing
- Module dependency graphs or health checks
- Demo / probe HTTP routes in the application (`/feature-demo` and similar)
- Creating or deleting features from the admin UI
- Changing a feature's `module_code` from the admin UI

Integration is a later PR per flag (scheduled publishing, advanced SEO, review monitor, AI writer).

## Architecture

Mirror Module Management v1.0. Do not introduce Pennant, Spatie, or JSON-on-module storage.

```text
Catalog     SystemFeatureCatalog   codes, names, module_code, default_enabled, is_core
Storage     system_features        status is source of truth
Runtime     FeatureService         cached, memoized, never throws
Helpers     feature()              alias of feature_enabled()
Middleware  feature:{code}         abort 404 when not enabled
Admin       System → Features      table: Feature, Module, Status
Audit       SystemFeatureStatusChanged  iam_audit_logs
```

Cache key `commerce.system_features` is independent of `commerce.system_modules`. TTL 3600 seconds. Forget cache on save/delete like `SystemModule`.

## Data model

Table `system_features`:

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `code` | string unique | kebab-case |
| `name` | string | admin primary label |
| `description` | text nullable | |
| `module_code` | string | parent `system_modules.code`, no FK |
| `status` | string(20) | `ENABLED` or `DISABLED` |
| `is_core` | boolean | column present; Phase 2 catalog all `false` |
| `sort_order` | unsigned int | catalog order for `all()` |
| `created_at` / `updated_at` | timestamps | |

`default_enabled` is **catalog-only**. It is not a database column. The seeder maps `true → ENABLED`, `false → DISABLED` **only when inserting a new row**.

### Enum

```php
enum FeatureStatus: string
{
    case Enabled = 'ENABLED';
    case Disabled = 'DISABLED';
}
```

`FeatureService` and the Form Request compare `FeatureStatus` cases only. Do not compare raw strings in service logic.

Phase 2 does not add `BETA` or `DEPRECATED`. The enum exists so those can be added later without rewriting call sites.

### Initial catalog

All four flags: `is_core = false`, `default_enabled = true`.

| sort_order | code | name | module_code |
|---|---|---|---|
| 10 | `scheduled-publishing` | Scheduled Publishing | `cms` |
| 20 | `advanced-seo` | Advanced SEO | `cms` |
| 30 | `ai-writer` | AI Writer | `cms` |
| 40 | `review-monitor` | Review Monitor | `reviews` |

Seeder `SystemFeatureSeeder` runs after `SystemModuleSeeder`. For an existing `code` it updates name, description, module_code, sort_order, and is_core. It **does not** overwrite `status`.

## Evaluation

Public API:

```php
FeatureService::enabled(string $code): bool
FeatureService::get(string $code): ?SystemFeature   // silent, no unknown warning
FeatureService::all(): Collection                   // silent
FeatureService::clearCache(): void
```

Helpers (identical):

```php
feature('advanced-seo')
feature_enabled('advanced-seo')
```

`feature()` is an alias of `feature_enabled()`. Both call `FeatureService::enabled()`.

### Order of checks for `enabled()`

1. Table missing or unexpected error → `false`, never throw, log registry unavailable. Features fail **closed** (not enabled). This differs from module status helpers, where a registry error must not take the storefront down.
2. Unknown feature code → `false`. Log **once per process per code** only from `enabled()`, not from `get()` / `all()`.
3. Parent `module_code` absent from the module registry → `false`. Log **once per process per feature code** from `enabled()` only.
4. Parent module **DISABLED** → `false` (ignore feature status).
5. Parent module **ACTIVE** or **HIDDEN** → `true` iff `status === FeatureStatus::Enabled`.
6. Future `is_core`: treat as ENABLED after the parent-DISABLED check (no core rows in Phase 2).

HIDDEN is navigation-only. It must not force features off.

### Logging

Status evaluation warnings use distinct `warning_code` values. Catalog lookup is silent.

Unknown feature (`enabled('foobar')`):

```text
message: Unknown system feature requested.
context: warning_code=feature_unknown, code=foobar
```

Parent missing (`enabled('advanced-seo')` when `cms` is not in the module registry):

```text
message: System feature parent module is missing.
context: warning_code=feature_parent_missing, code=advanced-seo, module_code=cms
```

`get('foobar')` returns `null` and does **not** log.

## Middleware

Alias `feature` → `EnsureFeatureEnabled`.

Usage: `feature:ai-writer`.

If `FeatureService::enabled($code)` is false → `abort(404)` (not 403). `abort(404)` must sit **outside** `catch (Throwable)` so `HttpException` is not swallowed (same bug Module Management already fixed).

If `enabled()` throws unexpectedly, catch, log, fail closed as not enabled (404). Do not add application demo routes; tests register a one-off route in the test case.

## Admin UI

Nav: System group, after Modules:

```text
System
├── Modules   admin.system.modules.index
└── Features  admin.system.features.index   permission system.feature.view
```

One index table. Search on name, code, description, module_code.

| Column | Display |
|---|---|
| Feature | **name** primary; `code` as muted secondary (mono) |
| Module | **parent module name** from `ModuleService::get(module_code)?->name` (e.g. `CMS`, `Reviews`); `module_code` as muted secondary. If the parent row is missing, show the code only. |
| Status | badge ENABLED / DISABLED |
| Updated | timestamp |
| Actions | select ENABLED/DISABLED + Save (confirm), same pattern as Modules |

Parent DISABLED while feature status is ENABLED: keep the stored status badge, and show an extra badge/hint:

```text
INACTIVE (MODULE DISABLED)
Parent module is disabled.
```

This documents the runtime `false` without rewriting DB status.

No create/delete. Phase 2 has no locked core features in the UI.

Copy lives in `commerce::admin` (en + th), including breadcrumbs.

## Permissions and routes

```text
system.feature.view     View system features
system.feature.update   Update system feature status
```

Register through `commerce.permissions` with `module => system` (same `system.` prefix rule as modules).

```text
GET  /admin/system/features                  admin.system.features.index
PUT  /admin/system/features/{systemFeature}  admin.system.features.update
```

`index` requires `system.feature.view`. `update` requires `system.feature.update`. Policy `SystemFeaturePolicy` mirrors `SystemModulePolicy`. Form Request rejects invalid enum values. Core lock after-hook exists for future `is_core` but no Phase 2 row is core.

## Audit

Event `SystemFeatureStatusChanged` implements `DomainEventInterface`.

```text
action: system.feature.status_changed
subject: SystemFeature
```

Meta / payload:

```json
{
  "code": "advanced-seo",
  "feature_name": "Advanced SEO",
  "module_code": "cms",
  "old_status": "ENABLED",
  "new_status": "DISABLED"
}
```

IAM listener `LogSystemFeatureStatusChanged` registered next to the module listener. No-op when status is unchanged (do not write an audit row).

## Tests

No product CMS/Reviews assertions. Middleware uses a route registered inside the test.

### Unit `tests/Unit/Features/FeatureServiceTest.php`

- Seeded catalog: four codes, all ENABLED when parent modules are ACTIVE
- `all()` order is catalog `sort_order` then name
- `get()` unknown → null, no warning
- `enabled()` unknown → false, one `feature_unknown` warning, no throw
- Parent missing → false, `feature_parent_missing`
- Parent DISABLED + feature ENABLED → false
- Parent HIDDEN + feature ENABLED → **true** (`it_keeps_feature_enabled_when_parent_module_is_hidden`)
- Parent HIDDEN + feature DISABLED → false
- Table missing → false, no throw
- Cache remember / clear after `updateStatus`
- `feature()` and `feature_enabled()` match `FeatureService::enabled()`
- `updateStatus` uses `FeatureStatus` enum

### Feature

- `SystemFeatureAdminTest`: index visible; search; ENABLED→DISABLED persists; invalid status rejected; audit row includes `code`, `feature_name`, `module_code`, statuses
- `EnsureFeatureEnabledTest`: test route 200 when enabled; 404 not 403 when feature DISABLED; 404 when parent DISABLED even if feature ENABLED; 200 when parent HIDDEN and feature ENABLED
- `SystemFeatureRouteCacheTest`: alias registered; cached routes contain `feature:ai-writer`; enforcement still 404 after disable
- `AdminNavigationIaTest`: System children are Modules then Features; English label `Features`; Thai label `ฟีเจอร์`

## Files (expected)

Create under `packages/commerce/core` (model, enum, catalog, service, middleware, controller, request, policy, event, views, lang, migrations, seeder) and `modules/Iam` (listener + `Event::listen`). Helpers in `packages/commerce/core/src/helpers.php`. Nav in `config/admin.php` and `resources/lang/{en,th}/nav.php`.

## Success criteria

- `vendor/bin/phpunit tests/Unit/Features tests/Feature/Features tests/Feature/Admin/AdminNavigationIaTest.php` green
- No CMS/Reviews/SEO/AI product files changed except if a shared nav/IA test needs the Features link
- `php artisan route:cache` still succeeds
- Turning a flag off in admin does not change storefront/CMS behavior in this PR (nothing calls `feature()` yet)

## Related WIP (do not merge here)

```text
stash@{0}: wip: homepage/cms on feat/commerce-framework-v1
feat/cms-scheduled-publishing   (product integration later via feature('scheduled-publishing'))
```
