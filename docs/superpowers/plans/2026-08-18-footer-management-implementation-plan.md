# Footer Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a config-driven Footer Management module with Customer Experience-style admin UX and storefront/preview parity.

**Architecture:** Footer is a renderer-only composition layer. A versioned `footer` config stores intent (layout tokens + ordered section instances). Registered section drivers resolve source data and build a versioned `FooterViewModel`. A shared storefront Blade footer component renders the view model; admin preview must call a stateless preview endpoint that uses the exact same view-model pipeline and shared component tree.

**Tech Stack:** PHP (Laravel), Blade, vanilla JS admin preview/editor, existing `modules/Settings` persistence model (`SettingServiceInterface->updateGroup`), PHPUnit + Laravel feature tests.

## Global Constraints
- Follow the frozen design in `docs/superpowers/specs/2026-08-18-footer-management-design.md`.
- Footer is renderer-only: config stores intent, drivers resolve data, Blade renders only.
- Unknown section types must be ignored safely.
- Supports multiplicity via `supportsMultiple()`; section `id` must be unique and follow lowercase `kebab-case`.
- Preview endpoint must be stateless and must not save to DB.
- Preview and storefront must use the same Blade footer component + same view-model builder pipeline.
- Footer must fail gracefully: a broken section driver must never break the whole storefront render.

---

## Phase 1 — Domain & Runtime

### Task 1: Create Footer Config Service (settings registration + defaults + merge)

**Files:**
- Create: `modules/Settings/src/Services/FooterConfigService.php`

**Interfaces:**
- Consumes: `Commerce\Contracts\Settings\SettingQueryServiceInterface`, `Commerce\Contracts\Settings\SettingRegistryServiceInterface`
- Produces: `FooterConfigService->ensureRegistered()`, `FooterConfigService->resolve()`, `FooterConfigService->merge(array $overrides)`, `FooterConfigService->defaults()`, `FooterConfigService->previewCatalog()`

- [ ] Implement `FooterConfigService` similar to `CustomerExperienceConfig`, including:
  - Setting key (public json): `footer.config` (name can be aligned with repo conventions; must be consistent across controller + request + builder)
  - Dedicated settings group name: `footer`
  - `defaults()` seeds v1 default config (brand, navigation, cms, social, copyright, powered_by)
  - Config uses `schema_version` (not `version`) and stores `enabled`, `layout`, and `sections[]`
  - `resolve()` loads stored config and calls `merge()`
  - `merge()` overlays validated overrides into defaults (cast primitives where reasonable)
- [ ] Add unit tests:
  - Create: `tests/Unit/Settings/FooterConfigServiceTest.php`
  - Verify defaults shape, schema_version presence, merge overlay behavior, and that invalid override types do not crash (they should be ignored or cast safely).
- [ ] Run:
  - `phpunit tests/Unit/Settings/FooterConfigServiceTest.php -v`

### Task 2: Create Footer Section Contracts + Registry + Driver registration

**Files:**
- Create: `modules/Settings/src/Footer/Contracts/FooterSectionDriver.php`
- Create: `modules/Settings/src/Footer/Registry/FooterSectionRegistry.php`

**Interfaces:**
- Produces: `FooterSectionDriver::build(FooterSectionConfig $config): ?FooterSection` and `FooterSectionDriver::supportsMultiple(): bool`
- Produces: `FooterSectionRegistry->drivers()` and `FooterSectionRegistry->templates()` (exact names can follow existing patterns, but must be consistent)

- [ ] Implement `FooterSectionDriver` contract (includes `supportsMultiple()`).
- [ ] Implement `FooterSectionRegistry` as the single source of truth for:
  - template catalog used by admin “Add Section” UI
  - default per-template settings (`default_settings`)
  - supported multiplicity (`supports_multiple`)
  - driver type mapping (`type` -> driver class)
- [ ] Add unit tests:
  - Create: `tests/Unit/Footer/FooterSectionRegistryTest.php`
  - Verify that registry returns required driver entries and template defaults.
- [ ] Run:
  - `phpunit tests/Unit/Footer/FooterSectionRegistryTest.php -v`

### Task 3: Implement Footer Section Manager (validation + composition + graceful failure)

**Files:**
- Create: `modules/Settings/src/Services/FooterSectionManager.php`

**Interfaces:**
- Consumes: `FooterSectionRegistry`, section drivers
- Produces: `FooterSectionManager->buildSections(array $configSections, FooterBuildContext $ctx): array`

- [ ] Define a minimal `FooterBuildContext` (can be an array DTO) that contains what drivers need (request device, plan tier/service flags, marketplace availability, etc.).
- [ ] Implement config validation for section instances:
  - malformed section object must not invalidate entire footer
  - unknown `type` ignored
  - `id` must be lowercase kebab-case and unique within config (skip invalid section instances)
  - enforce multiplicity:
    - if `supportsMultiple() === false`, only allow the first instance for that `type` and skip subsequent ones
  - call the registered driver `build()` for each valid section instance
  - if driver throws: catch, log, and skip that section
- [ ] Implement structured logging on skips/failures (example key in spec: `footer.section.cms.failed`, and include `section_id` and `type`).
- [ ] Add unit tests:
  - Create: `tests/Unit/Footer/FooterSectionManagerTest.php`
  - Cases to cover:
    - unknown type is ignored
    - duplicate ids skip and continue
    - supportsMultiple=false prevents multiple instances
    - driver exception is caught, logged, and other sections still build
- [ ] Run:
  - `phpunit tests/Unit/Footer/FooterSectionManagerTest.php -v`

### Task 4: Implement Footer ViewModel Builder (versioned output + layout token mapping)

**Files:**
- Create: `modules/Settings/src/Services/FooterViewModelBuilder.php`

**Interfaces:**
- Consumes: `FooterSectionManager`
- Produces: `FooterViewModelBuilder->build(array $config, FooterBuildContext $ctx): array` returning versioned view model with `schema_version`.

- [ ] Implement mapping of config layout tokens to view model:
  - `layout.columns` into grid class/metadata
  - `layout.color_scheme/surface/variant` into theme tokens (do not store raw hex)
  - divider_style/padding/spacing into normalized tokens for CSS
- [ ] Resolve placeholders for copyright:
  - `{year}` and `{store_name}`
  - placeholders must be resolved in the view-model layer
- [ ] Ensure view model schema includes:
  - `schema_version`
  - `layout` (normalized presentation tokens)
  - `sections[]` (built sections, only those that are non-empty)
- [ ] Add unit tests:
  - Create: `tests/Unit/Footer/FooterViewModelBuilderTest.php`
  - Verify placeholder resolution and that empty sections are removed.
- [ ] Run:
  - `phpunit tests/Unit/Footer/FooterViewModelBuilderTest.php -v`

### Task 5: Implement V1 Footer Section Drivers

**Files (create):**
- Create: `modules/Settings/src/Footer/Drivers/BrandSectionDriver.php`
- Create: `modules/Settings/src/Footer/Drivers/NavigationSectionDriver.php`
- Create: `modules/Settings/src/Footer/Drivers/CmsSectionDriver.php`
- Create: `modules/Settings/src/Footer/Drivers/SocialSectionDriver.php`
- Create: `modules/Settings/src/Footer/Drivers/MarketplaceSectionDriver.php`
- Create: `modules/Settings/src/Footer/Drivers/CopyrightSectionDriver.php`
- Create: `modules/Settings/src/Footer/Drivers/PoweredBySectionDriver.php`

**Interfaces:**
- Each driver implements `FooterSectionDriver`
- Each driver returns `null` when the section must be skipped (disabled/empty/module missing)

- [ ] Implement each driver using existing source-of-truth services/models:
  - Brand: from existing site/store identity services (logo/name/description)
  - Navigation: from Navigation module; apply filtering options (max_links + footer-enabled/public options)
  - CMS: use CMS page sources; return ordered list from selected `page_ids`
  - Social: resolve only populated URLs from Website Settings (hide empty automatically)
  - Marketplace: show only when marketplace module/capability is enabled
  - Copyright: build display text with placeholder resolution
  - Powered By: enforce plan rules server-side (free required, paid optional, enterprise hidden)
- [ ] Each driver must:
  - validate its own `settings` shape (driver-owned settings)
  - never throw on missing optional data; return `null` for empty sections
  - provide translation keys/metadata (labels not stored in config)
- [ ] Add unit tests per driver:
  - Create: `tests/Unit/Footer/Drivers/*SectionDriverTest.php` (or group into one test class if repo convention prefers)
  - Minimal coverage:
    - returns null on missing sources
    - returns correct structured payload when sources exist
    - Marketplace/PoweredBy respects module/plan availability
- [ ] Run:
  - `phpunit tests/Unit/Footer/Drivers -v`

### Task 6: Update Shared Storefront Footer Component to use ViewModel

**Files:**
- Modify: `resources/views/components/storefront/layout/partials/site-footer.blade.php`
- (May create/modify) shared footer Blade partials if current footer markup is not view-model friendly.

- [ ] Refactor storefront footer to accept/require `FooterViewModel` data only.
- [ ] Ensure Blade does not query DB or resolve settings.
- [ ] Update styles or classes to align with tokenized layout metadata from view model.
- [ ] Add storefront rendering feature tests:
  - Create: `tests/Feature/Storefront/FooterRenderingTest.php`
  - Verify:
    - enabled/disabled sections respected
    - empty sections auto-drop
    - graceful failure: if one section driver fails, other sections still appear and response is not 500.
- [ ] Run:
  - `phpunit tests/Feature/Storefront/FooterRenderingTest.php -v`

---

## Phase 2 — Admin Experience

### Task 7: Add Admin Routes + Controller + Request + Save Model (persist config)

**Files:**
- Modify: `modules/Settings/routes/web.php`
- Create: `modules/Settings/src/Http/Controllers/Admin/FooterController.php`
- Create: `modules/Settings/src/Http/Requests/UpdateFooterRequest.php`

**Interfaces:**
- Controller uses `FooterConfigService` + `SettingServiceInterface`
- Request decodes and validates `config` payload

- [ ] Add routes:
  - `GET /admin/settings/footer` -> `admin.settings.footer.show`
  - `PUT /admin/settings/footer` -> `admin.settings.footer.update` (with update permission middleware mirroring CX)
- [ ] Implement `FooterController`:
  - `show()` calls `FooterConfigService->ensureRegistered()` and returns admin blade with:
    - resolved config
    - preview metadata catalog if needed (similar to CX)
- [ ] Implement `UpdateFooterRequest`:
  - must parse JSON config (mirroring CX `UpdateCustomerExperienceRequest::configPayload()`)
  - validate structural requirements (schema_version supported, layout tokens allowed, section ids unique + kebab-case, driver setting shapes)
  - invalid/malformed section objects must not invalidate the entire config; invalid sections skipped with warnings (or treated as empty).
- [ ] Implement save:
  - Controller calls `SettingServiceInterface->updateGroup()` with group `footer` and value `config` equal to merged config.
- [ ] Add tests:
  - Create: `tests/Feature/Settings/FooterSettingsTest.php`
  - Verify:
    - admin page loads and seeds defaults
    - admin save persists updated config
    - invalid payload is rejected (or safely normalized, matching spec).
- [ ] Run:
  - `phpunit tests/Feature/Settings/FooterSettingsTest.php -v`

### Task 8: Implement Preview Endpoint (stateless contract)

**Files:**
- Modify or Create: `modules/Settings/src/Http/Controllers/Admin/FooterController.php` (add preview action)
- Create: `modules/Settings/src/Http/Requests/PreviewFooterRequest.php` (optional) for validation

**Contract (must match spec):**
```json
{
  "html": "<rendered footer html>",
  "meta": {
    "total_sections": 6,
    "visible_sections": 4,
    "hidden_sections": 2,
    "hidden_reasons": [
      { "section_id": "help-pages", "reason": "empty_cms_selection" }
    ]
  }
}
```

- [ ] Add route:
  - `POST /admin/settings/footer/preview`
- [ ] Implement stateless behavior:
  - accept transient `config`
  - validate + normalize (same normalization rules as save)
  - build view model using the same `FooterViewModelBuilder` + shared Blade component
  - return JSON with `html` + `meta`
  - absolutely no DB writes, no temporary settings creation
- [ ] Add concurrency safety test:
  - Create: `tests/Feature/Settings/FooterPreviewStatelessTest.php`
  - Simulate two different preview payloads in one test process and ensure no persistence occurs.
- [ ] Run:
  - `phpunit tests/Feature/Settings/FooterPreviewStatelessTest.php -v`

### Task 9: Build Admin UI (split layout, section block library, driver-owned detail panels)

**Files:**
- Create: `modules/Settings/resources/views/admin/footer/index.blade.php`
- Create: `modules/Settings/resources/views/admin/footer/_preview-shell.blade.php` (if mirroring CX preview shell)
- Create: `resources/js/admin/footer-settings.js`
- Modify: admin bundle entry if needed (similar to `resources/js/admin.js` wiring)

- [ ] UI layout:
  - left: config + section list + detail panel
  - right: preview with device modes
  - dirty state visible + explicit Save/Discard
- [ ] Section list:
  - show default v1 block instances as draggable cards
  - include `Add Section` button at top
  - “Add Section” modal uses templates from `FooterSectionRegistry`
- [ ] Section detail panel:
  - do not hardcode all forms in the main editor
  - implement driver-owned detail UI (front-end can be modular by template per section type, but still use driver metadata contract)
- [ ] Social section panel is read-only and links to Website Settings
- [ ] Navigation UX includes link-count and 0-public-links warning
- [ ] CMS page picker supports reorder via drag & drop in selected list
- [ ] Preview refresh:
  - debounce 300–500ms for config changes
  - refresh on selection changes and drag finished, not every keystroke
- [ ] Preview metadata bar displays meta from endpoint:
  - total sections, visible/hidden counts, hidden reasons
- [ ] Run:
  - `phpunit tests/Feature/Settings/FooterSettingsTest.php -v` (ensures endpoint + save wiring works)
  - plus manual UI verification steps in development environment (ensure shared component tree parity)

---

## Phase 3 — Storefront Integration + Parity + Tests

### Task 10: Ensure Storefront Uses Same ViewModel Pipeline and Shared Component Tree

**Files:**
- Modify: existing footer render integration points
  - (Likely) `resources/views/components/storefront/layout/store-layout.blade.php`
  - and/or `modules/Cart/resources/views/layouts/storefront.blade.php`

- [ ] Update the storefront layout footer include so it:
  - loads saved footer config using `FooterConfigService->resolve()`
  - builds `FooterViewModel` using `FooterViewModelBuilder`
  - passes view model to the shared footer Blade component
- [ ] Add parity tests:
  - Create: `tests/Feature/Storefront/FooterPreviewParityTest.php`
  - Ensure preview endpoint HTML equals storefront rendered HTML for the same transient config (within stable HTML normalization if needed).
- [ ] Run:
  - `phpunit tests/Feature/Storefront/FooterPreviewParityTest.php -v`

### Task 11: Theme Token Wiring + Localization + Accessibility

**Files:**
- Modify: `resources/css/storefront/components.css` or dedicated footer CSS (if exists)
- Modify drivers / view model shaping for localization keys and placeholders if needed

- [ ] Theme tokens:
  - map `layout.color_scheme/surface/variant` into CSS variables or classnames used by the theme system
  - ensure divider style/padding/spacing are responsive and consistent
- [ ] Localization:
  - drivers return `title_key` / stable translation keys, and storefront Blade uses `__()` with those keys
- [ ] Accessibility:
  - ensure footer sections use semantic HTML and accessible link/button markup
- [ ] Run:
  - `phpunit tests/Feature/Storefront/FooterRenderingTest.php -v`
  - manual checks for responsive layout + keyboard navigation

### Task 12: Migration Safety and Graceful Failure Hardening

**Files:**
- Modify: `FooterConfigService`, `FooterSectionManager`, `FooterViewModelBuilder`

- [ ] Ensure unknown fields in stored config do not break parsing.
- [ ] Ensure malformed sections are skipped without invalidating the entire footer.
- [ ] Ensure driver exceptions do not bubble to storefront.
- [ ] Add regression tests:
  - Create: `tests/Feature/Storefront/FooterGracefulFailureTest.php`
  - Provide a config containing:
    - unknown section type
    - malformed section object
    - driver set to throw (use test double/mocking if framework supports)
  - Verify storefront response is 200 and at least one non-failing section renders.
- [ ] Run:
  - `phpunit tests/Feature/Storefront/FooterGracefulFailureTest.php -v`

---

## Self-Review Checklist (against spec)
- [ ] Domain boundary: renderer-only, config intent only
- [ ] Extensibility: section ids + type, driver architecture, registry-based Add Section
- [ ] Preview: stateless endpoint + shared component tree
- [ ] Production safety: supportsMultiple, schema_version, config integrity, graceful failure
- [ ] Admin UX: split layout, dirty state, drag-and-drop, debounced preview
- [ ] Tests: unit + feature + parity tests

