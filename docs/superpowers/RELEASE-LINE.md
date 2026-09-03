# Release Line

Baseline of tagged releases on `main` as of 2026-09-03 (`6b63a8d`).

```text
v1.0.0-module-management
v1.1.0-feature-flags
v1.2.0-cms-scheduled-publishing
v1.3.0-blog-ui-refresh
v1.4.0
v1.5.0
```

v1.2.0 / v1.3.0 feature branches were deleted after merge. v1.4.0 and v1.5.0 were squash-merged as PR #1 and PR #2. The tags on `main` are the source of history.

```text
main = stable
v1.4.0  Barcode Management   (9cf699f)
v1.5.0  Homepage CMS         (6b63a8d)
```

## Tags

| Tag | Commit | Concern | What a rollback undoes |
|---|---|---|---|
| `v1.0.0-module-management` | `0a2c3ca` | Infrastructure | Module registry / admin System → Modules |
| `v1.1.0-feature-flags` | `0f55100` | Infrastructure | Feature registry / `feature()` |
| `v1.2.0-cms-scheduled-publishing` | `79d5338` | Behavior | Scheduler, resolver, publish timestamps |
| `v1.3.0-blog-ui-refresh` | `8ce3e65` | UI | Blog archive, single, editor, adapters |
| `v1.4.0` | `9cf699f` | Catalog | Barcode Center, templates, print queue |
| `v1.5.0` | `6b63a8d` | Storefront / CMS | Homepage sections, hero/promo/FAQ, `/` |

v1.2.0 and v1.3.0 are separate so a scheduler defect rolls back v1.2.0 and a blog layout defect rolls back v1.3.0. v1.4.0 and v1.5.0 are separate so a barcode defect does not roll back homepage, and vice versa.

## v1.0.0 — Module Management

- `system_modules` registry and `ModuleService`
- `module()` / `module_enabled()` helpers and `module:{code}` middleware
- Admin **System → Modules**
- IAM audit for module status changes

## v1.1.0 — Feature Flags

- `system_features` registry and `FeatureService`
- `feature()` / `feature_enabled()` and `feature:{code}` middleware
- Admin **System → Features**
- Flag defaults ENABLED; parent module DISABLED turns child flags off
- This tag does not wire product behavior; scheduled publishing lands in v1.2.0

Design: `docs/superpowers/specs/2026-09-02-feature-flags-framework-design.md`

## v1.2.0 — CMS Scheduled Publishing

- `scheduled` status, `published_at` / `unpublish_at`
- `PublishStateResolver` and `CmsPublishScheduler` (minute command)
- `ConstrainsScheduledPublishing` on post/page requests
- Storefront visibility = published only
- Editor, API, scheduler, and resolver gated with `feature('scheduled-publishing')`
- Existing scheduled rows and timestamps are preserved when the flag is off

Design: `docs/superpowers/specs/2026-09-01-cms-scheduled-publishing-design.md`

## v1.3.0 — Blog UI Refresh

- Storefront archive and single-post redesign (`blog.css` / `blog.js`)
- Admin post/page editor workspace, slash menu, inspector, media JSON upload
- Related posts and latest/popular sort
- Local thin adapters: breadcrumb, empty-state, layout.grid, blog.share, forms.sort-dropdown
- No `commerce-framework-v1` backport, no homepage, no new migrations
- Admin post/page forms keep `@if (feature('scheduled-publishing'))`

Design: `docs/superpowers/specs/2026-09-02-blog-ui-refresh-thin-adapters-design.md`

## v1.4.0 — Barcode Management

- Admin **Catalog → Barcode Center** at `/admin/barcode`
- Template presets, print queue, PDF preview
- Path-extracted from archive `84e905c`; squash-merged as PR #1
- Product is a narrow read. Media via `MediaQueryServiceInterface::getUrl()`. No `SiteIdentityServiceInterface`
- Recovery tag: `recovery/barcode-v1.4`

Design: `docs/superpowers/specs/2026-09-02-barcode-template-layout-contract.md`

## v1.5.0 — Homepage CMS

- Admin **Content → Homepage / Hero Banners / Promotion Banners / FAQ**
- Storefront `GET /` and `GET /home/arrivals`
- Query adapters on current main (`HomepageNavigationQuery`, `HomepageProductQuery`, `HomepageBrandingQuery`)
- Isolation lock: no archive `Storefront*Catalog`, `ProductImageResolver`, or `SiteIdentityServiceInterface`
- Vault (source snapshot, not integrated): `feat/homepage-cms-preservation` / `recovery/homepage-cms`
- Squash-merged as PR #2

## Next candidate

Footer Management is **not tagged**. Source of truth is archive commit `84e905c` (path extract only).

M1 allowlist is locked: `docs/superpowers/specs/2026-09-03-footer-management-m1-allowlist.md`. Do not open `feat/footer-management-v1` until extract follows that list. Isolation forbids `SiteIdentityServiceInterface` and `StorefrontNavigationConfig`.

Proposed later tags (not started):

```text
v1.6.0  Footer Management
v1.7.0  Website Settings + Navigation
v1.8.x  Warehouse Scanner
v1.9.x  POS Terminal Expansion
v2.x    WS-002 Design System
```

## Not included

These are outside the tagged history on `main`:

- Footer Management (recoverable from `84e905c`; do not merge archive branches)
- Website Settings / Appearance / Site Identity / Customer Experience
- Navigation Recovery
- Warehouse Scanner, POS barcode expansion, Inventory expansion, Marketplace payouts
- Storefront primitives / `feat/commerce-framework-v1` design system / `stash@{0}`
- UI-TECH-001 (`@vite/client` injected twice on blog pages)

Safety net (do not delete):

```text
recovery/barcode-v1.4
recovery/homepage-cms
feat/homepage-cms-preservation
feat/barcode-stock-recovery      (archive, read-only)
feat/commerce-framework-v1       (archive, read-only)
```
