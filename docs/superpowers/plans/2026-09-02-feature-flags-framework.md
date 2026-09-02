# Feature Flags Framework Phase 2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a Module-Management-shaped `system_features` registry so Super Admins can enable/disable four catalog flags, and so later PRs can call `feature('scheduled-publishing')` without this PR touching CMS, SEO, Reviews, or AI Writer.

**Architecture:** `SystemFeatureCatalog` seeds defaults; `system_features.status` is source of truth; cached `FeatureService::enabled()` fails closed and returns false when the parent module is DISABLED; `feature:` middleware abort(404); System → Features admin; IAM audit on status change.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, existing `packages/commerce/core` + IAM audit patterns from Module Management v1.0.

## Global Constraints

- Branch: `feat/feature-flags` only. Do not mix stash `wip: homepage/cms on feat/commerce-framework-v1` or `feat/cms-scheduled-publishing`.
- Do not `git add .`. Never add `Archive.zip`, `plugins/widget*`, `public/wp-content`, homepage/CMS untracked files.
- Infrastructure only: no CMS/Reviews/SEO/AI product `feature()` call sites.
- No rollout %, targeting, remote config, A/B, demo `/feature-demo` product page.
- Catalog = defaults. DB status = source of truth. Seeder must not overwrite `status`.
- No FK from `system_features.module_code` to `system_modules`.
- `FeatureService` compares `FeatureStatus` enum cases, never raw status strings.
- `enabled()` logs; `get()` / `all()` are silent. Warning codes: `feature_unknown`, `feature_parent_missing`.
- Features fail **closed** (`false`, never throw) if the registry is unavailable.
- Parent ACTIVE or HIDDEN → feature status decides. Parent DISABLED → always false.
- `abort(404)` for middleware must sit outside `catch (Throwable)`.
- Spec: `docs/superpowers/specs/2026-09-02-feature-flags-framework-design.md`.
- TDD: write the failing test first, watch it fail, then implement.

## File map

- Create: `packages/commerce/core/src/Enums/FeatureStatus.php`
- Create: `packages/commerce/core/src/Features/SystemFeatureCatalog.php`
- Create: `packages/commerce/core/src/Features/FeatureService.php`
- Create: `packages/commerce/core/src/Models/SystemFeature.php`
- Create: `packages/commerce/core/database/migrations/2026_09_02_160000_create_system_features_table.php`
- Create: `packages/commerce/core/src/Database/Seeders/SystemFeatureSeeder.php`
- Create: `packages/commerce/core/src/Http/Middleware/EnsureFeatureEnabled.php`
- Create: `packages/commerce/core/src/Http/Controllers/Admin/SystemFeatureController.php`
- Create: `packages/commerce/core/src/Http/Requests/UpdateSystemFeatureStatusRequest.php`
- Create: `packages/commerce/core/src/Policies/SystemFeaturePolicy.php`
- Create: `packages/commerce/core/src/Events/SystemFeatureStatusChanged.php`
- Create: `packages/commerce/core/resources/views/admin/features/index.blade.php`
- Create: `modules/Iam/src/Listeners/LogSystemFeatureStatusChanged.php`
- Create: `tests/Unit/Features/FeatureServiceTest.php`
- Create: `tests/Feature/Features/EnsureFeatureEnabledTest.php`
- Create: `tests/Feature/Features/SystemFeatureAdminTest.php`
- Create: `tests/Feature/Features/SystemFeatureRouteCacheTest.php`
- Modify: `packages/commerce/core/src/helpers.php`
- Modify: `packages/commerce/core/src/CommerceServiceProvider.php`
- Modify: `packages/commerce/core/config/commerce.php`
- Modify: `packages/commerce/core/routes/web.php`
- Modify: `packages/commerce/core/resources/lang/en/admin.php`
- Modify: `packages/commerce/core/resources/lang/th/admin.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `modules/Iam/src/IamServiceProvider.php`
- Modify: `config/admin.php`
- Modify: `resources/lang/en/nav.php`, `resources/lang/th/nav.php`
- Modify: `tests/Feature/Admin/AdminNavigationIaTest.php`

---

### Task 1: Persistence — enum, catalog, table, model, seeder

**Files:**
- Create: `packages/commerce/core/src/Enums/FeatureStatus.php`
- Create: `packages/commerce/core/src/Features/SystemFeatureCatalog.php`
- Create: `packages/commerce/core/src/Models/SystemFeature.php`
- Create: `packages/commerce/core/database/migrations/2026_09_02_160000_create_system_features_table.php`
- Create: `packages/commerce/core/src/Database/Seeders/SystemFeatureSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Unit/Features/FeatureServiceTest.php` (first methods only)

**Interfaces:**
- Consumes: `system_modules` already created by Module Management migrations
- Produces: `FeatureStatus::{Enabled,Disabled}`; `SystemFeatureCatalog::defaults()`; table `system_features` with no FK; `SystemFeature` model

- [ ] **Step 1: Write the failing catalog test**

Create `tests/Unit/Features/FeatureServiceTest.php` with `RefreshDatabase`. Assert four codes exist after migrate:

```php
public function test_seeded_features_are_enabled_by_default(): void
{
    $this->assertSame(
        ['scheduled-publishing', 'advanced-seo', 'ai-writer', 'review-monitor'],
        SystemFeature::query()->orderBy('sort_order')->orderBy('name')->pluck('code')->all(),
    );

    foreach (['scheduled-publishing', 'advanced-seo', 'ai-writer', 'review-monitor'] as $code) {
        $row = SystemFeature::query()->where('code', $code)->first();
        $this->assertNotNull($row);
        $this->assertSame(FeatureStatus::Enabled, $row->status);
        $this->assertFalse($row->is_core);
    }
}
```

- [ ] **Step 2: Run the test and confirm it fails**

Run: `vendor/bin/phpunit tests/Unit/Features/FeatureServiceTest.php --filter test_seeded_features_are_enabled_by_default`

Expected: FAIL (`SystemFeature` / table missing).

- [ ] **Step 3: Implement enum, catalog, migration, model, seeder**

`FeatureStatus`:

```php
enum FeatureStatus: string
{
    case Enabled = 'ENABLED';
    case Disabled = 'DISABLED';

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Enabled => 'success',
            self::Disabled => 'danger',
        };
    }
}
```

`SystemFeatureCatalog::defaults()` returns four arrays: `code`, `name`, `description`, `module_code`, `default_enabled` (bool true), `sort_order` (10/20/30/40), `is_core` (false). Names: Scheduled Publishing, Advanced SEO, AI Writer, Review Monitor. Module codes: `cms`, `cms`, `cms`, `reviews`.

Migration `2026_09_02_160000_create_system_features_table.php`: `code` unique; `module_code` string **without** `constrained()`; `status` string 20; `is_core` boolean default false; `sort_order`; timestamps; indexes on `status`, `module_code`, `sort_order`. Insert from catalog mapping `default_enabled` to `FeatureStatus::Enabled->value` or `Disabled->value`. Do not store `default_enabled` as a column.

`SystemFeature` fillable: `code`, `name`, `description`, `module_code`, `status`, `is_core`, `sort_order`. Casts: `status` => `FeatureStatus::class`, `is_core` => boolean, `sort_order` => integer. `booted` saved/deleted: `Cache::forget('commerce.system_features')` until Task 2 switches to `FeatureService::clearCache()`.

`SystemFeatureSeeder`: same loop as `SystemModuleSeeder` — on existing rows update name, description, module_code, sort_order, is_core only (**not** status); on create map `default_enabled` to status; then forget/clear cache.

`DatabaseSeeder`: call `SystemFeatureSeeder::class` immediately after `SystemModuleSeeder::class`.

- [ ] **Step 4: Re-run the catalog test**

Run: `vendor/bin/phpunit tests/Unit/Features/FeatureServiceTest.php --filter test_seeded_features_are_enabled_by_default`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/commerce/core/src/Enums/FeatureStatus.php \
  packages/commerce/core/src/Features/SystemFeatureCatalog.php \
  packages/commerce/core/src/Models/SystemFeature.php \
  packages/commerce/core/database/migrations/2026_09_02_160000_create_system_features_table.php \
  packages/commerce/core/src/Database/Seeders/SystemFeatureSeeder.php \
  database/seeders/DatabaseSeeder.php \
  tests/Unit/Features/FeatureServiceTest.php
git commit -m "feat: add system_features catalog and table"
```

---

### Task 2: FeatureService, helpers, evaluation rules

**Files:**
- Create: `packages/commerce/core/src/Features/FeatureService.php`
- Modify: `packages/commerce/core/src/helpers.php`
- Modify: `packages/commerce/core/src/CommerceServiceProvider.php` (`singleton(FeatureService::class)`)
- Modify: `packages/commerce/core/src/Models/SystemFeature.php` (`FeatureService::clearCache()`)
- Test: `tests/Unit/Features/FeatureServiceTest.php`

**Interfaces:**
- Consumes: `SystemFeature`, `ModuleService`, `ModuleStatus`, `FeatureStatus`
- Produces:
  - `FeatureService::CACHE_KEY = 'commerce.system_features'`
  - `FeatureService::CACHE_TTL_SECONDS = 3600`
  - `enabled(string $code): bool`
  - `get(string $code): ?SystemFeature` — hydrate from cache/memo only; null if missing; never queries DB itself; never logs unknown
  - `all(): Collection`
  - `clearCache(): void`
  - `updateStatus(SystemFeature $feature, FeatureStatus $status): SystemFeature`
  - `feature(string $code): bool` and `feature_enabled(string $code): bool`

Copy `ModuleService`: memoized collection; `Cache::remember` stores attribute arrays; hydrate with `setRawAttributes` + `exists = true`. `get()` calls `find($code, warnUnknown: false)`. `enabled()` calls `find($code, warnUnknown: true)` then parent checks.

`enabled()` order:

1. Table missing / throwable in `definitions()` → log `System feature registry unavailable.` → `false`
2. Unknown code → `false` + once-per-code `Log::warning('Unknown system feature requested.', ['warning_code' => 'feature_unknown', 'code' => $code])`
3. `ModuleService::get($feature->module_code) === null` → `false` + once-per-feature-code `Log::warning('System feature parent module is missing.', ['warning_code' => 'feature_parent_missing', 'code' => $code, 'module_code' => $feature->module_code])`
4. `ModuleService::isDisabled($feature->module_code)` → `false`
5. If `$feature->is_core` → `true`
6. Return `$feature->status === FeatureStatus::Enabled`

Never compare `'ENABLED'` strings in `FeatureService`. `updateStatus` on `is_core` throws `ValidationException` on `status`. Unchanged status returns early (no event until Task 5). Model `saved` clears cache.

- [ ] **Step 1: Write failing evaluation tests** in `FeatureServiceTest`

- `test_all_returns_catalog_codes_in_sort_order`
- `test_get_unknown_code_is_silent_and_null` — fake `MessageLogged`; `get('foobar')` null; assert not dispatched
- `test_enabled_unknown_code_logs_feature_unknown_once`
- `test_enabled_logs_feature_parent_missing_when_module_absent` — delete `cms` module, `ModuleService::clearCache()`, `enabled('advanced-seo')` false, context `warning_code=feature_parent_missing`
- `test_parent_disabled_forces_feature_false_when_enabled`
- `test_it_keeps_feature_enabled_when_parent_module_is_hidden` — `cms` → `ModuleStatus::Hidden`; `assertTrue(FeatureService::enabled('scheduled-publishing'))`
- `test_parent_hidden_still_respects_disabled_feature`
- `test_status_checks_stay_safe_when_system_features_table_is_missing`
- `test_helpers_match_enabled`
- `test_definitions_are_cached_and_get_does_not_query` — after `all()`, query log must stay empty for `get`/`enabled`/`all`
- `test_cache_is_cleared_after_status_update`

- [ ] **Step 2: Run unit file, confirm RED**

Run: `vendor/bin/phpunit tests/Unit/Features/FeatureServiceTest.php`

Expected: FAIL on missing `FeatureService` / helpers.

- [ ] **Step 3: Implement FeatureService + helpers + singleton**

Register singleton next to `ModuleService`. Helpers after `module_disabled`: `feature_enabled()` calls `FeatureService::enabled()`; `feature()` calls `feature_enabled()`.

- [ ] **Step 4: Run unit tests GREEN**

Run: `vendor/bin/phpunit tests/Unit/Features/FeatureServiceTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/commerce/core/src/Features/FeatureService.php \
  packages/commerce/core/src/helpers.php \
  packages/commerce/core/src/CommerceServiceProvider.php \
  packages/commerce/core/src/Models/SystemFeature.php \
  tests/Unit/Features/FeatureServiceTest.php
git commit -m "feat: add FeatureService evaluation and helpers"
```

---

### Task 3: Middleware `feature:{code}` and route-cache enforcement

**Files:**
- Create: `packages/commerce/core/src/Http/Middleware/EnsureFeatureEnabled.php`
- Modify: `packages/commerce/core/src/CommerceServiceProvider.php`
- Create: `tests/Feature/Features/EnsureFeatureEnabledTest.php`
- Create: `tests/Feature/Features/SystemFeatureRouteCacheTest.php`

**Interfaces:**
- Consumes: `FeatureService::enabled(string $code): bool`
- Produces: alias `feature`; abort 404 when not enabled; testing-only route `testing.feature.probe` (`GET /__testing/feature-probe`) registered **only** when `$this->app->runningUnitTests()`

After `aliasMiddleware('feature', EnsureFeatureEnabled::class)`:

```php
if ($this->app->runningUnitTests()) {
    $router->get('/__testing/feature-probe', static fn () => response('ok', 200))
        ->middleware(['web', 'feature:ai-writer'])
        ->name('testing.feature.probe');
}
```

Middleware: try `FeatureService::enabled($code)`; on `Throwable` log `Feature middleware failed closed.` and leave `$enabled = false`. **Then** `if (! $enabled) { abort(404); }` **outside** the catch. Return `$next($request)` if enabled.

- [ ] **Step 1: Write EnsureFeatureEnabledTest**

Seed `IamSeeder`. Hit `route('testing.feature.probe')`.

- ENABLED → 200 / `ok`
- disable `ai-writer` → 404, not 403
- parent `cms` DISABLED, feature still ENABLED → 404
- parent `cms` HIDDEN, feature ENABLED → 200

- [ ] **Step 2: Run RED**

Run: `vendor/bin/phpunit tests/Feature/Features/EnsureFeatureEnabledTest.php`

Expected: FAIL (alias / route missing).

- [ ] **Step 3: Implement middleware + alias + probe**

- [ ] **Step 4: GREEN middleware tests**

Run: `vendor/bin/phpunit tests/Feature/Features/EnsureFeatureEnabledTest.php`

Expected: PASS.

- [ ] **Step 5: Write SystemFeatureRouteCacheTest**

`tearDown`: `Artisan::call('route:clear')` if routes are cached.

1. `$router->getMiddleware()['feature'] === EnsureFeatureEnabled::class`
2. `$this->artisan('route:cache')->assertSuccessful()` and compiled file contains `feature:ai-writer`
3. After cache, GET probe is 200; disable `ai-writer`; GET is 404 not 403. If the process still uses in-memory routes, `$this->refreshApplication()` after `route:cache` so Laravel loads the compiled file (Module Management regression: cache file OK, middleware dead).

- [ ] **Step 6: Run route cache tests GREEN**

Run: `vendor/bin/phpunit tests/Feature/Features/SystemFeatureRouteCacheTest.php`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/commerce/core/src/Http/Middleware/EnsureFeatureEnabled.php \
  packages/commerce/core/src/CommerceServiceProvider.php \
  tests/Feature/Features/EnsureFeatureEnabledTest.php \
  tests/Feature/Features/SystemFeatureRouteCacheTest.php
git commit -m "feat: add feature middleware with cached-route enforcement"
```

---

### Task 4: Admin UI, permissions, nav

**Files:**
- Create: `packages/commerce/core/src/Http/Controllers/Admin/SystemFeatureController.php`
- Create: `packages/commerce/core/src/Http/Requests/UpdateSystemFeatureStatusRequest.php`
- Create: `packages/commerce/core/src/Policies/SystemFeaturePolicy.php`
- Create: `packages/commerce/core/resources/views/admin/features/index.blade.php`
- Modify: `packages/commerce/core/routes/web.php`
- Modify: `packages/commerce/core/config/commerce.php`
- Modify: `packages/commerce/core/src/CommerceServiceProvider.php` (`Gate::policy`)
- Modify: `packages/commerce/core/resources/lang/en/admin.php`, `th/admin.php`
- Modify: `config/admin.php`
- Modify: `resources/lang/en/nav.php`, `th/nav.php`
- Modify: `tests/Feature/Admin/AdminNavigationIaTest.php`
- Create: `tests/Feature/Features/SystemFeatureAdminTest.php`

**Interfaces:**
- Consumes: `FeatureService` definitions, `ModuleService::get` for parent **name**, `ModuleService::isDisabled` for hint
- Produces: `system.feature.view` / `system.feature.update`; `admin.system.features.index` / `.update`

Add to `commerce.permissions`:

```php
'system.feature.view' => 'View system features',
'system.feature.update' => 'Update system feature status',
```

Routes: prefix `admin/system/features`, names `admin.system.features.`, `auth` + `permission:system.feature.view`; PUT also `permission:system.feature.update`. Bind `{systemFeature}`.

Controller mirrors `SystemModuleController`. Search name/code/description/module_code.

Form Request: `Rule::enum(FeatureStatus::class)`; reject `is_core` updates.

Policy mirrors `SystemModulePolicy`.

Index: Feature = **name** + muted code; Module = **parent name** (CMS / Reviews) + muted `module_code`; Status badge; Updated; select+Save+confirm. If parent DISABLED and status Enabled, badge `INACTIVE (MODULE DISABLED)` and hint `Parent module is disabled.`

Nav: System children Modules then Features. English `Features`, Thai `ฟีเจอร์`. Route key `admin_system_features_index`.

IA: `assertSame(['Modules', 'Features'], array_column($byId['system']['children'], 'label'))`; second child route `admin.system.features.index`, permission `system.feature.view`; Thai child `[1]` label `ฟีเจอร์`.

- [ ] **Step 1: Extend AdminNavigationIaTest so it fails, then add nav config + lang**

- [ ] **Step 2: RED** `vendor/bin/phpunit tests/Feature/Admin/AdminNavigationIaTest.php`

- [ ] **Step 3: Implement permissions, routes, policy, request, controller, view, Gate::policy, translations**

- [ ] **Step 4: Write SystemFeatureAdminTest** — index shows `Scheduled Publishing` and `CMS` (not code-only); search `seo`; invalid `ARCHIVED` errors; PUT DISABLED persists enum

- [ ] **Step 5: GREEN**

Run: `vendor/bin/phpunit tests/Feature/Admin/AdminNavigationIaTest.php tests/Feature/Features/SystemFeatureAdminTest.php`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/commerce/core/src/Http/Controllers/Admin/SystemFeatureController.php \
  packages/commerce/core/src/Http/Requests/UpdateSystemFeatureStatusRequest.php \
  packages/commerce/core/src/Policies/SystemFeaturePolicy.php \
  packages/commerce/core/resources/views/admin/features/index.blade.php \
  packages/commerce/core/routes/web.php \
  packages/commerce/core/config/commerce.php \
  packages/commerce/core/src/CommerceServiceProvider.php \
  packages/commerce/core/resources/lang \
  config/admin.php \
  resources/lang/en/nav.php \
  resources/lang/th/nav.php \
  tests/Feature/Admin/AdminNavigationIaTest.php \
  tests/Feature/Features/SystemFeatureAdminTest.php
git commit -m "feat: add Super Admin system features page"
```

---

### Task 5: Audit event + listener

**Files:**
- Create: `packages/commerce/core/src/Events/SystemFeatureStatusChanged.php`
- Create: `modules/Iam/src/Listeners/LogSystemFeatureStatusChanged.php`
- Modify: `packages/commerce/core/src/Features/FeatureService.php`
- Modify: `modules/Iam/src/IamServiceProvider.php`
- Modify: `tests/Feature/Features/SystemFeatureAdminTest.php`

**Interfaces:**
- Consumes: `IamAuditServiceInterface::log`
- Produces: `system.feature.status_changed`; meta `code`, `feature_name`, `module_code`, `module_name` (`ModuleService::get($code)?->name` or null), `old_status`, `new_status` (enum values); skip audit when status unchanged

Event implements `DomainEventInterface`. Constructor includes `SystemFeature $feature`, `FeatureStatus $oldStatus`, `FeatureStatus $newStatus`, `?string $moduleName`, `?int $userId`, `?int $tenantId`.

`Event::listen(SystemFeatureStatusChanged::class, LogSystemFeatureStatusChanged::class)` beside the module listener.

- [ ] **Step 1: Add failing `test_status_change_writes_an_audit_log`** toggling `advanced-seo`; assert action, subject `SystemFeature`, `feature_name` Advanced SEO, `module_name` CMS

- [ ] **Step 2: RED then implement event, listener, dispatch, register**

- [ ] **Step 3: GREEN**

Run: `vendor/bin/phpunit tests/Unit/Features tests/Feature/Features tests/Feature/Admin/AdminNavigationIaTest.php`

Expected: all PASS.

- [ ] **Step 4: Pint**

Run: `vendor/bin/pint --dirty`

- [ ] **Step 5: Re-run the same PHPUnit command**

Expected: all PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/commerce/core/src/Events/SystemFeatureStatusChanged.php \
  packages/commerce/core/src/Features/FeatureService.php \
  modules/Iam/src/Listeners/LogSystemFeatureStatusChanged.php \
  modules/Iam/src/IamServiceProvider.php \
  tests/Feature/Features/SystemFeatureAdminTest.php
git commit -m "feat: audit system feature status changes"
```

Do not commit untracked homepage/CMS/plugin files.

---

## Spec coverage

| Spec item | Task |
|---|---|
| Table, no FK, catalog, `default_enabled`, seeder does not overwrite status | 1 |
| FeatureStatus enum | 1 |
| FeatureService enabled/get/all/cache, fail closed, parent HIDDEN/DISABLED, warning codes | 2 |
| Helpers `feature` / `feature_enabled` | 2 |
| Middleware 404, abort outside catch | 3 |
| Testing probe + cache file + 200/404 after cache | 3 |
| Admin table, module name, parent-disabled hint | 4 |
| Permissions, routes, nav, IA | 4 |
| Audit + `feature_name` + `module_name` | 5 |
| No CMS product integration | all |
